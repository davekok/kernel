<?php

declare(strict_types=1);

namespace DaveKok\Stream;

enaum IOReadyState
{
    /**
     * Indicate protocol is ready for a push of input.
     * ReadReady is removed after every push and must be rearmed.
     */
    case ReadReady;

    /**
     * Indicate protocol is ready for a pull of output.
     * WriteReady is removed after every pull and must be rearmed.
     */
    case WriteReady;

    /**
     * Indicate protocol is for both ready and writing.
     * ReadReady and WriteReady are removed afterwards and must be rearmed.
     */
    case BothReady;

    /**
     * Indicate protocol is finished and stream can be closed.
     */
    case Close;
}
