<?php

declare(strict_types=1);

namespace davekok\stream;

use Stringable;

/**
 * This interface represents an activity. Each activity has its own standard input, output and
 * log stream.
 *
 * Activities are setup with a layered looped cooperative threading model in mind. Threads are thus
 * build up from small loops. The small loops are entangled together through this interface and are
 * also looped. So if we have the small loops A, B, C and D. A can get entangled to B, B to C, C to
 * D and D back to A. Even higher level loops can be build over the network.
 *
 * Looped versus linear threading model.
 *
 * In the linear threading model each thread gets its own call-stack. While in the looped threading
 * model all threads share the same call-stack. In the linear model state may be preserved on the
 * call-stack. In the looped model all state must be encapsulated in an object. The linear model
 * has a request/response feel, while the looped model as a more message or event feel.
 *
 * The linear model seems to break the open/close principle. As the thread itself is not closed to
 * modification. This is mostly noticeble when strictly defining exceptions in function signatures.
 * A function may need to change its signature if lower functions start throwing exceptions of a
 * different type. Or if exceptions are strictly handled it could break single responsibility as
 * functions must now deal with foreign exceptions of lower functions to prevent signature change.
 * The looped model does not seem to have this problem.
 *
 * The looped model can be implemented without special support of a language. Except that back
 * traces of exceptions are rather meaningless. However, it seems to require thinking more in terms
 * of space and time rather than just space as with the linear model.
 */
interface Activity
{
    /**
     * Get info about the underlying stream.
     */
    public function getStreamInfo(): StreamInfo;

    /**
     * Adds an abritrary action to the activity. Please note that the user must handle these actions or
     * else the activity breaks and an exception (StreamError) is thrown.
     *
     * Use push to push something into the activity which will trigger an arbitrary action.
     */
    public function add(callable $next): self;

    /**
     * Push something into the activity triggering a previously added arbritray action. Please
     * note that the next action must be an arbritrary action. Otherwise an exception is thrown.
     */
    public function push(mixed ...$args): self;

    /**
     * Adds a read action to the activity.
     */
    public function addRead(Reader $reader): self;

    /**
     * Adds a write action to the activity.
     */
    public function addWrite(Writer $writer): self;

    /**
     * Adds a enable crypto action to the activity.
     */
    public function addEnableCrypto(bool $enable, int|null $cryptoType = null): self;

    /**
     * Adds a close action to the activity.
     */
    public function addClose(): self;

    /**
     * Add a emergency log action to the activity.
     */
    public function addEmergency(string|Stringable $message): self;

    /**
     * Add a alert log action to the activity.
     */
    public function addAlert(string|Stringable $message): self;

    /**
     * Add a critical log action to the activity.
     */
    public function addCritical(string|Stringable $message): self;

    /**
     * Add a error log action to the activity.
     */
    public function addError(string|Stringable $message): self;

    /**
     * Add a warning log action to the activity.
     */
    public function addWarning(string|Stringable $message): self;

    /**
     * Add a notice log action to the activity.
     */
    public function addNotice(string|Stringable $message): self;

    /**
     * Add a info log action to the activity.
     */
    public function addInfo(string|Stringable $message): self;

    /**
     * Add a debug log action to the activity.
     */
    public function addDebug(string|Stringable $message): self;

    /**
     * Add a log action to the activity.
     */
    public function addLog(LogLevel $level, string|Stringable $message): self;

    /**
     * Set the filter level for log messages.
     */
    public function setLogFilterLevel(LogLevel $level): self;

    /**
     * Get the filter level for log messages.
     */
    public function getLogFilterLevel(): LogLevel;

    /**
     * Clears the actions currently planned.
     */
    public function clear(): self;

    /**
     * Repeat the current action. Useful when a reader or writer is not ready yet and needs
     * another pass.
     */
    public function repeat(): self;
}
