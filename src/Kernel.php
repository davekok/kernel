<?php

declare(strict_types=1);

namespace davekok\kernel;

use davekok\wiring\Runnable;

class Kernel implements Runnable
{
    public const CHUNK_SIZE = 1400;

    private bool         $running    = false;
    private TimeOut|null $timeOut    = null;
    private array        $activities = [];
    private array        $active     = [];

    public function run(): never
    {
        if ($this->running === true) {
            throw new KernelException("Already running.");
        }

        $this->running = true;

        while ($this->running) {
            $readableSelectors   = [];
            $writableSelectors   = [];
            $exceptableSelectors = [];
            $actions             = [];
            $timeout             = $this->timeOut();

            // if no more active activities then exit
            if (count($this->active) === 0) {
                exit(1);
            }

            // loop through all active activities
            // get current action and either execute or add to select
            foreach ($this->active as $activity) {
                if ($activity->valid() === false) {
                    $this->suspend($activity);
                    continue;
                }
                $action = $activity->current();
                if ($action instanceof ReadableAction) {
                    $selector = $action->readableSelector();
                    $actions[get_resource_id($selector)] = $action;
                    $readableSelectors[] = $selector;
                    continue;
                }
                if ($action instanceof WritableAction) {
                    $selector = $action->writableSelector();
                    $actions[get_resource_id($selector)] = $action;
                    $writableSelectors[] = $selector;
                    continue;
                }
                $action->execute();
            }

            // if nothing is selected then do next pass
            if (count($readableSelectors) === 0 && count($writableSelectors) === 0) {
                continue;
            }

            $ret = stream_select($readableSelectors, $writableSelectors, $exceptableSelectors, $timeout);
            if ($ret === false) {
                continue;
            }
            if ($ret === 0) {
                isset($this->timeOut) && $this->timeOut->timeOut();
                continue;
            }

            foreach ($readableSelectors as $selector) {
                $actions[get_resource_id($selector)]->execute();
            }

            foreach ($writableSelectors as $selector) {
                $actions[get_resource_id($selector)]->execute();
            }
        }
        exit();
    }

    public function createActivity(): Activity
    {
        $id = (time() << 32) | random_int(0, 4294967295); // create a 64-bit unique id of time and a random int
        $activity = new Activity($this, $id);
        $this->activities[$id] = $activity;
        $this->active[$id]     = $activity;
        return $activity;
    }

    public function getActivity(int $id): Activity
    {
        return $this->activities[$id] ?? throw new NotFoundException("Not found: activity:#$id");
    }

    public function suspendActivity(Activity $activity): void
    {
        unset($this->active[$activity->id]);
    }

    public function resumeActivity(Activity $activity): void
    {
        $this->active[$activity->id] = $activity;
    }

    public function stopActivity(Activity $activity): void
    {
        unset($this->activities[$activity->id]);
        unset($this->active[$activity->id]);
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
