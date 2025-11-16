<?php

use lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder;
use lightla\VariableDebugger\DebugStrategy\VariableDebugViaHtmlStrategy;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;
use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugger;
use lightla\VariableDebugger\VariablePendingDebug;

function v_gl_config(): VariableDebugConfigGlobalPendingBuilder
{
    return new VariableDebugConfigGlobalPendingBuilder();
}

function v_dump(...$vars): VariablePendingDebug
{
    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger = new VariableDebugger(
        $globalConfig?->merge($config) ?? $config,
            null
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
    $globalConfig = VariableDebugConfig::getGlobalConfig();
    $config = VariableDebugConfig::builder()->withMaxDepth(10)->build();

    $variableDebugger = new VariableDebugger(
        $globalConfig?->merge($config) ?? $config,
        null,
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
        if ($exception instanceof VariableDebugGracefulExitException) {
            http_response_code(200);

            return;
        }

        restore_exception_handler();

        throw $exception;
    });
}