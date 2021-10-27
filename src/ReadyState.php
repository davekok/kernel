<?php

declare(strict_types=1);

namespace davekok\stream;

enum ReadyState
{
    /**
     * Indicate protocol is not ready for reading or writing.
     */
    case NotReady;

    /**
     * Indicate protocol is ready for a push of input.
     * Once new data has arrived ready state is reset to NotReady and pushInput is called of the protocol.
     */
    case ReadReady;

    /**
     * Indicate protocol is ready for a pull of output.
     * Once stream is ready for writing pullOutput is called and written and ready state is reset to NotReady.
     */
    case WriteReady;

    /**
     * Indicate protocol is finished and stream can be closed.
     */
    case Close;
}
