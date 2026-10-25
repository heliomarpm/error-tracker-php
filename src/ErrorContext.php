<?php

namespace HeliomarPM\ErrorTracker;

use Throwable;

class ErrorContext
{
    public readonly Severity $severity;
    public readonly string $message;
    public readonly int|string $code;
    public readonly string $file;
    public readonly int $line;
    public readonly string $trace;
    public readonly array $requestData;
    public readonly string $hash;

    public function __construct(Throwable $e, array $sanitizedRequest, Severity $severity = Severity::CRITICAL)
    {
        $this->severity = $severity;
        $this->message = $e->getMessage();
        $this->code = $e->getCode();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
        $this->trace = $e->getTraceAsString();
        $this->requestData = $sanitizedRequest;

        // O segredo da deduplicação: Um hash único para este erro exato
        $this->hash = md5($this->file . $this->line . $this->message);
    }
}
