<?php

namespace lightla\VariableDebugger\Config;

enum VariableDebugConfigTerminationMode: string
{
    case EXIT_SUCCESS = 'exit_success';
    case THROW_EXCEPTION = 'throw_exception';

    public function isExitSuccess(): bool
    {
        return $this === self::EXIT_SUCCESS;
    }

    public function isThrowException(): bool
    {
        return $this === self::THROW_EXCEPTION;
    }
}
