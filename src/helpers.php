<?php

use LightLa\VariableDebugger\Config\VariableDebugConfig;
use LightLa\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder;
use LightLa\VariableDebugger\VariableDebugger;
use LightLa\VariableDebugger\VariablePendingDebug;

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
