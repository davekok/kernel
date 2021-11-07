<?php

declare(strict_types=1);

namespace davekok\stream;

/**
 * Usage:
 * Start or reset an activity with calling read, write, close or enableCrypto. Then add on to the
 * activity with the andThen* functions.
 *
 * Use push to push values into the activity. This will trigger arbritrary actions.
 *
 * Arbritrary actions are useful when working with readers/parsers that convert a raw byte stream
 * into something more useful and the call push to insert a message object into the activity.
 *
 * Example:
 *
 *     class HttpControllerFactory implements ControllerFactory
 *     {
 *         public function __construct(private HttpFactory $httpFactory) {}
 *
 *         public function createController(Activity $activity): HttpController
 *         {
 *             return new HttpController(
 *                 $activity,
 *                 $this->httpFactory->createReader($activity),
 *                 $this->httpFactory->createWriter($activity)
 *             );
 *         }
 *     }
 *
 *     class HttpController implements HttpRequestController
 *     {
 *         public function __construct(
 *             private Activity $activity,
 *             private HttpReader $reader,
 *             private HttpWriter $writer
 *         ) {
 *             $this->activity->enableCrypto(true, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);
 *             $this->reader->receive($this);
 *         }
 *
 *         public function handleRequest(HttpRequest|ParserException|ReaderException $request): void
 *         {
 *             switch ($request->path) {
 *                 case "/":
 *                     $this->writer->send(new HttpResponse(
 *                         status: HttpStatus::OK,
 *                         body: "Hello, world!"
 *                     ));
 *                     break;
 *                 default:
 *                     $this->writer->send(new HttpResponse(
 *                         status: HttpStatus::NOT_FOUND,
 *                         body: "Not found"
 *                     ));
 *                     break;
 *             }
 *             $this->reader->receive($this); // get ready for next request
 *         }
 *     }
 *
 */
interface Activity
{
    /**
     * Get info about the underlying stream.
     */
    public function getStreamInfo(): StreamInfo;

    /**
     * Starts or resets the activity, and adds a read action.
     */
    public function read(Reader $reader): self;

    /**
     * Starts or resets the activity, and adds a write action.
     */
    public function write(Writer $writer): self;

    /**
     * Starts or resets the activity, and adds a close action.
     */
    public function close(): self;

    /**
     * Starts or resets the activity, and adds a enable crypto action.
     */
    public function enableCrypto(bool $enable, int|null $cryptoType = null): self;

    /**
     * Adds an abritrary action to the activity. Please not that the user must handle this action or
     * else the activity breaks and an exception (StreamError) is thrown.
     *
     * Use push to push something into the activity which will trigger an arbitrary action.
     */
    public function andThen(callable $next): self;

    /**
     * Adds a read action to the activity.
     */
    public function andThenRead(Reader $reader): self;

    /**
     * Adds a write action to the activity.
     */
    public function andThenWrite(Writer $writer): self;

    /**
     * Adds a enable crypto action to the activity.
     */
    public function andThenEnableCrypto(bool $enable, int|null $cryptoType = null): self;

    /**
     * Adds a close action to the activity.
     */
    public function andThenClose(): self;

    /**
     * Push something into the activity triggering a previously added arbritray action. Please
     * not that the next action must be an arbritrary action. Otherwise an exception is thrown.
     */
    public function push(mixed ...$args): self;

    /**
     * The repeat current action. Useful when a reader or writer is not ready yet.
     */
    public function repeat(): self;
}
