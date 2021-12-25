<?php

declare(strict_types=1);

namespace davekok\kernel;

/**
 * A passive socket awaits incoming connections. Once a connection is made, accept
 * is called to get an active socket for the connection.
 * Reading and writing is not possible on passive socket.
 */
class PassiveSocket implements Actionable, Passive, Closable
{
    use ActionableTrait;
    use PassiveTrait;
    use ClosableTrait;

    public function __construct(
        public readonly Url      $url,
        public readonly Activity $activity,
        public readonly mixed    $handle,
    ) {}
}
