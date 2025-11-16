<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Config\VariableDebugConfig;
use lightla\VariableDebugger\Config\VariableDebugConfigArrayShowMode;

class VariablePendingDebug
{
    public function __construct(
        private array $backtrace,
        private VariableDebugger $variableDebugger,
        private readonly \Closure $pendingDebugAction,
    )
    {

    }

    public function __destruct()
    {
        ($this->pendingDebugAction)($this->backtrace);
    }

    public function on(VariableDebugConfig $config): void
    {
        $this->variableDebugger->setConfig(
            $config->merge(VariableDebugConfig::getGlobalConfig())
        );
    }

    public function onShort(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): void
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->withMaxDepth($maxDepth)
                ->withShowArrayMode($showArrayOnlyFirstElement
                    ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                    : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                )
                ->withShowDetailAccessModifiers(false)
                ->withShowValueType(false)
                ->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );
    }

    public function onFull(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): void
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->withMaxDepth($maxDepth)
                ->withShowArrayMode($showArrayOnlyFirstElement
                    ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                    : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                )
                ->withShowDetailAccessModifiers(true)
                ->withShowValueType(true)
                ->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );
    }
}
