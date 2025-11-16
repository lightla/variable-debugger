<?php

use lightla\VariableDebugger\Config\VariableDebugConfig;
use lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder;
use lightla\VariableDebugger\VariableDebugger;
use lightla\VariableDebugger\VariablePendingDebug;

function v_gl_config(): VariableDebugConfigGlobalPendingBuilder
{
    return new VariableDebugConfigGlobalPendingBuilder();
}

function v_dump(...$vars): VariablePendingDebug
{
    $variableDebugger = new VariableDebugger();

    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger->setConfig(
        $globalConfig?->merge($config) ?? $config
    );

    return new VariablePendingDebug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1),
        $variableDebugger,
        fn($backtrace) => $variableDebugger->dumpFromTrace(
            $backtrace, ...$vars
        )
    );
}

function v_dd(...$vars): VariablePendingDebug
{
    $variableDebugger = new VariableDebugger();

    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger->setConfig(
        $globalConfig?->merge($config) ?? $config
    );

    return new VariablePendingDebug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1),
        $variableDebugger,
        fn($backtrace) => $variableDebugger->ddFromTrace(
            $backtrace, ...$vars
        )
    );
}

/**
 * @return callable|null
 * @throws Throwable
 */
function v_register_exception_handler(): ?callable
{
    return set_exception_handler(function (Throwable $exception) {
        if ($exception instanceof \lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException) {
            http_response_code(200);

            return;
        }

        restore_exception_handler();

        throw $exception;
    });
}