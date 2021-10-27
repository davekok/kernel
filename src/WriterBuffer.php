<?php

declare(strict_types=1);

namespace davekok\stream;

class WriterBuffer
{
    public function __construct(
        private string $buffer = "",
        private int $mark      = 0,
        private int $offset    = 0
    ) {}

    /**
     * Reset buffer
     */
    public function reset(): void
    {
        $this->buffer = "";
        $this->mark   = 0;
        $this->offset = 0;
    }

    /**
     * Add output
     */
    public function add(string $output): self
    {
        // discard everthing before mark
        if ($this->mark > 0) {
            $this->buffer = substr($this->buffer, $this->mark);
            $this->offset -= $this->mark;
            $this->mark = 0;
        }
        // add output to buffer
        $this->buffer .= $output;
        return $this;
    }

    public function valid(): bool
    {
        return $this->mark < $this->offset;
    }

    public function getChunk(int $chunkSize): string
    {
        return substr($this->buffer, $this->mark, $chunkSize);
    }

    public function moveMarkBy(int $by): self
    {
        $this->mark += $by;
        return $this;
    }
}
