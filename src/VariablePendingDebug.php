<?php

namespace lightla\VariableDebugger;

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

    public function onShort(?int $maxDepth = null, ?bool $showArrayOnlyFirstElement = null): static
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->configShort($maxDepth, $showArrayOnlyFirstElement)
                ->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );

        return $this;
    }

    public function onFull(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): void
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->configFull($maxDepth, $showArrayOnlyFirstElement)
                ->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );
    }
}
