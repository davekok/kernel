<?php

declare(strict_types=1);

namespace davekok\kernel;

trait FileTrait
{
    /**
     * Move offset relative to current offset.
     */
    public function moveOffset(int $offset): void
    {
        fseek($this->handle, $offset, SEEK_CUR) ?: throw new KernelException(__FUNCTION__ . " failed.");
    }

    /**
     * Set offset in file.
     *
     * Negative offsets are counted from the end of the file.
     */
    public function setOffset(int $offset): void
    {
        if ($offset > 0) {
            fseek($this->handle, $offset, SEEK_SET) ?: throw new KernelException(__FUNCTION__ . " failed.");
        } else {
            fseek($this->handle, $offset, SEEK_END) ?: throw new KernelException(__FUNCTION__ . " failed.");
        }
    }

    /**
     * Get offset in file.
     */
    public function getOffset(): int
    {
        $ret = ftell($this->handle);
        if ($ret === false) throw new KernelException(__FUNCTION__ . " failed.");
        return $ret;
    }

    /**
     * Set the size of the local file. If necessary truncate the file.
     */
    public function setSize(int $size): void
    {
        ftruncate($this->handle, $size) ?: throw new KernelException(__FUNCTION__ . " failed.");
    }

    /**
     * Get the size of the local file.
     */
    public function getSize(): int
    {
        $current = $this->getOffset();
        $this->setOffset(-1);
        $size = $this->getOffset();
        $this->setOffset($current);
        return $size;
    }

    /**
     * Synchronize state to disk. If requested also information about the file.
     */
    public function sync(bool $info = true): void
    {
        if ($info) {
            fsync($this->handle) ?: throw new KernelException(__FUNCTION__ . " failed.");
        } else {
            fdatasync($this->handle) ?: throw new KernelException(__FUNCTION__ . " failed.");
        }
    }

    /**
     * Try to get a shared lock. Returns false if the operation would pause the program/thread.
     * Shared locks can be used for reading.
     */
    public function trySharedLock(): bool
    {
        flock($this->handle, LOCK_SH | LOCK_NB, $wouldBlock) ?: throw new KernelException(__FUNCTION__ . " failed.");
        return $wouldBlock === 1;
    }

    /**
     * Try to get an exclusive lock. Returns false if the operation would pause the program/thread.
     * Exclusive locks can be used for writing.
     */
    public function tryExclusiveLock(): bool
    {
        flock($this->handle, LOCK_EX | LOCK_NB, $wouldBlock) ?: throw new KernelException(__FUNCTION__ . " failed.");
        return $wouldBlock === 1;
    }

    /**
     * Get a shared lock, pause the program/thread if necessary.
     * Shared locks can be used for reading.
     */
    public function sharedLock(): void
    {
        flock($this->handle, LOCK_SH) ?: throw new KernelException(__FUNCTION__ . " failed.");
    }

    /**
     * Get a exclusive lock, pause the program/thread if necessary.
     * Exclusive locks can be used for writing.
     */
    public function exclusiveLock(): void
    {
        flock($this->handle, LOCK_EX) ?: throw new KernelException(__FUNCTION__ . " failed.");
    }

    /**
     * Unlock.
     */
    public function unlock(): void
    {
        flock($this->handle, LOCK_UN) ?: throw new KernelException(__FUNCTION__ . " failed.");
    }
}
