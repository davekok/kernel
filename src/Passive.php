<?php

declare(strict_types=1);

namespace davekok\kernel;

interface Passive
{
    public function listen(Acceptor $acceptor): static;
    public function accept(): Actionable;
}
