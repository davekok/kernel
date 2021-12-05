<?php

declare(strict_types=1);

namespace davekok\kernel;

interface LocalFile
{
    /**
     * Move offset relative to current offset.
     */
    public function moveOffset(int $offset): void;

    /**
     * Set offset in file.
     *
     * Negative offsets are counted from the end of the file.
     */
    public function setOffset(int $offset): void;

    /**
     * Get offset in file.
     */
    public function getOffset(): int;

    /**
     * Set the size of the local file. If necessary truncate the file.
     */
    public function setSize(int $size): void;

    /**
     * Get the size of the local file.
     */
    public function getSize(): int;

    /**
     * Synchronize state to disk. If requested also information about the file.
     */
    public function sync(bool $info = true): void;

    /**
     * Try to get a shared lock. Returns false if the operation would pause the program/thread.
     */
    public function trySharedLock(): bool;

    /**
     * Try to get an exclusive lock. Returns false if the operation would pause the program/thread.
     */
    public function tryExclusiveLock(): bool;

    /**
     * Get a shared lock, pause the program/thread if necessary.
     */
    public function sharedLock(): void;

    /**
     * Get a exclusive lock, pause the program/thread if necessary.
     */
    public function exclusiveLock(): void;

    /**
     * Unlock a previous lock.
     */
    public function unlock(): void;
}
