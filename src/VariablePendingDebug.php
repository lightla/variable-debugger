<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugConfigBuilder;

class VariablePendingDebug
{
    private ?VariableDebugConfig $variableDebugConfig = null;

    public function __construct(
        private array $backtrace,
        private VariableDebugger $variableDebugger,
        private readonly \Closure $pendingDebugAction,
        private ?VariableDebugConfigBuilder $variableDebugConfigBuilder = null,
    )
    {
        if (! $this->variableDebugConfigBuilder) {
            $this->variableDebugConfigBuilder = VariableDebugConfig::builder();
        }
    }

    public function __destruct()
    {
        $this->variableDebugger->setConfig(
        $this->variableDebugConfig ?:
            $this->variableDebugConfigBuilder->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );

        ($this->pendingDebugAction)($this->backtrace, $this->variableDebugger);
    }

    public function on(VariableDebugConfig $config): void
    {
        $this->variableDebugConfig = $config;
    }

    public function onShort(?int $maxDepth = null, ?bool $showArrayOnlyFirstElement = null): static
    {
        $this->variableDebugConfigBuilder->configShort($maxDepth, $showArrayOnlyFirstElement);

        return $this;
    }

    public function onFull(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): static
    {
        $this->variableDebugConfigBuilder->configFull($maxDepth, $showArrayOnlyFirstElement);

        return $this;
    }

    public function showKeyOnly(?bool $showKeyOnly, ?array $ignoredShowKeyProperties = null): static
    {
        $this->variableDebugConfigBuilder
            ->withShowKeyOnly($showKeyOnly)
            ->withIgnoredShowKeyProperties($ignoredShowKeyProperties);

        return $this;
    }

    public function includeProperties(array $properties): static
    {
        $this->variableDebugConfigBuilder->withProperties($properties);

        return $this;
    }

    public function addClassPropertiesFromPlugin(VariableDebugClassPropertyPluginAdapter $plugin): static
    {
        $this->variableDebugConfigBuilder->addClassPropertiesFromPlugin($plugin);

        return $this;
    }

    public function addClassPropertiesFromPluginLaravel(): static
    {
        $this->variableDebugConfigBuilder->addClassPropertiesFromPluginLaravel();

        return $this;
    }

    public function excludeProperties(array $properties): static
    {
        $this->variableDebugConfigBuilder->withoutProperties($properties);

        return $this;
    }
}
