<?php

use lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;
use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugger;
use lightla\VariableDebugger\VariablePendingDebug;

function v_gl_config(): VariableDebugConfigGlobalPendingBuilder
{
    return VariableDebugConfigGlobalPendingBuilder::getInstance();
}

function v_dump(...$vars): VariablePendingDebug
{
    VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();

    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger = new VariableDebugger($globalConfig?->merge($config) ?? $config);

    return new VariablePendingDebug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1),
        $variableDebugger,
        static function (array $backtrace, VariableDebugger $debugger) use ($vars) {
            $debugger->dumpFromTrace($backtrace, ...$vars);
        }
    );
}

function v_dd(...$vars): VariablePendingDebug
{
    VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();

    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger = new VariableDebugger($globalConfig?->merge($config) ?? $config);

    return new VariablePendingDebug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1),
        $variableDebugger,
        static function (array $backtrace, VariableDebugger $debugger) use ($vars) {
            $debugger->ddFromTrace($backtrace, ...$vars);
        }
    );
}

function v_die(...$vars): VariablePendingDebug
{
    return v_dd(...$vars);
}

/**
 * @return callable|null
 * @throws Throwable
 */
function v_register_exception_handler(): ?callable
{
    return set_exception_handler(function (Throwable $exception) {
        if ($exception instanceof VariableDebugGracefulExitException) {
            http_response_code(200);

            return;
        }

        restore_exception_handler();

        throw $exception;
    });
}
