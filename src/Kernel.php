<?php

declare(strict_types=1,ticks=1);

namespace davekok\kernel;

use Psr\Log\LoggerInterface;

class Kernel implements Actionable
{
    public const CHUNK_SIZE = 1400;

    private bool         $running  = false;
    private TimeOut|null $timeOut  = null;
    private array        $active   = [];
    private array        $inactive = [];
    private Activity     $mainActivity;

    public function __construct(mixed $loggerHandle = STDERR): void
    {
        pcntl_signal(SIGINT , $this->quit(...));
        pcntl_signal(SIGQUIT, $this->quit(...));
        pcntl_signal(SIGTERM, $this->quit(...));

        $this->mainActivity = new Activity(0, $this, new Logger());
        $this->mainActivity->logger->setActionable(new WritablePipe($this->mainActivity, new Url("logger:"), $loggerHandle));
        $this->start($this->mainActivity);
    }

    public function activity(): Activity
    {
        return $this->mainActivity;
    }

    public function url(): Url
    {
        return new Url("Kernel:");
    }

    public function run(): never
    {
        if ($this->running === true) {
            throw new KernelException("Already running.");
        }

        $this->running = true;

        while ($this->running) {
            $selectRead   = [];
            $selectWrite  = [];
            $selectExcept = [];
            $actions      = [];
            $timeout      = $this->timeOut();

            // if no more active then exit
            if (count($this->active) === 0) {
                exit(1);
            }

            // loop through all active
            // get current action and either execute or add to select
            foreach ($this->active as $id => $activity) {
                if ($activity->valid() === false) {
                    $this->stop($id);
                    continue;
                }
                $action = $activity->current();
                if ($action instanceof Read || $action instanceof Accept) {
                    $handle = $action->actionable()->handle;
                    $actions[get_resource_id($handle)] = $action;
                    $selectRead[] = $handle;
                    continue;
                }
                if ($action instanceof Write) {
                    $handle = $action->actionable()->handle;
                    $actions[get_resource_id($handle)] = $action;
                    $selectWrite[] = $handle;
                    continue;
                }
                $action->execute();
            }

            // nothing selected then do next pass
            if (count($selectRead) === 0 && count($selectWrite) === 0) {
                continue;
            }

            $ret = stream_select($selectRead, $selectWrite, $selectExcept, $timeout);
            if ($ret === false) {
                continue;
            }
            if ($ret === 0) {
                isset($this->timeOut) && $this->timeOut->timeOut();
                continue;
            }

            foreach ($selectRead as $handle) {
                $actions[get_resource_id($handle)]->execute();
            }

            foreach ($selectWrite as $handle) {
                $actions[get_resource_id($handle)]->execute();
            }
        }
        exit();
    }

    public function start(Activity $activity): void
    {
        $this->active[$activity->id] = $activity;
    }

    public function suspend(Activity $activity): void
    {
        $this->inactive[$activity->id] = $this->active[$activity->id];
        unset($this->active[$activity->id]);
    }

    public function resume(Activity $activity): void
    {
        $this->active[$activity->id] = $this->inactive[$activity->id];
        unset($this->inactive[$activity->id]);
    }

    public function stop(Activity $activity): void
    {
        unset($this->active[$activity->id]);
        unset($this->inactive[$activity->id]);
        $activity->clear();
    }

    public function quit(): void
    {
        $this->running = false;
    }

    private function timeOut(): int|null
    {
        if (isset($this->timeOut) === false) {
            return null;
        }

        $timeOut = $this->timeOut->getNextTimeOut();

        // if time out is in the future or no time out then return time out
        if ($timeOut > 0 || $timeOut === null) {
            return $timeOut;
        }

        // if time out is now or in the past call the time out function
        $this->timeOut->timeOut();

        return null;
    }
}
