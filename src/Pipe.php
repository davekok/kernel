<?php

declare(strict_types=1);

namespace davekok\stream;

class Pipe implements Stream, BlockableStream, IOStream
{
    use StreamTrait;
    use BlockableStreamTrait;
    use IOStreamTrait;
}
