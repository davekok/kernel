<?php

declare(strict_types=1);

namespace DaveKok\Stream;

enum IOReady
{
    case Read;
    case Write;
    case Both;
    case Closed;
}
