<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Handlers\Traits;

use HeliomarPM\ErrorTracker\ErrorContext;
use Throwable;

/**
 * Trait para unificar a resolução do usuário (autenticado ou fallback) nos handlers.
 *
 * @internal Esta trait é de uso exclusivo interno dos handlers da biblioteca.
 */
trait ResolvesUserTrait
{
    /**
     * Resolve o identificador do usuário associado ao incidente.
     * Prioridade:
     * 1. Usuário já resolvido no ErrorContext
     * 2. Callback dinâmico ($this->userResolver) configurado no handler
     * 3. Nome estático ($this->userName) configurado no handler
     */
    private function resolveUser(ErrorContext $context): ?string
    {
        $userName = $context->userName;
        if ($userName !== null && $userName !== '') {
            return $userName;
        }

        if (isset($this->userResolver)) {
            try {
                $resolved = ($this->userResolver)();
                if ($resolved !== null && $resolved !== '') {
                    return (string)$resolved;
                }
            } catch (Throwable) {
                // Absorve exceções do resolver customizado para manter resiliência
            }
        }

        if (isset($this->userName) && $this->userName !== '') {
            return $this->userName;
        }

        return null;
    }
}
