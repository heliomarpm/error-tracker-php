<?php

namespace HeliomarPM\ErrorTracker\Contracts;

use HeliomarPM\ErrorTracker\ErrorContext;

interface HandlerInterface
{
    /**
     * @param ErrorContext $context Objeto contendo todos os dados mastigados e sanitizados.
     */
    public function report(ErrorContext $context): void;
}
