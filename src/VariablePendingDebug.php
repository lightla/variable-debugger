<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugConfigBuilder;
use lightla\VariableDebugger\Config\VariableDebugConfigTerminationMode;

class VariablePendingDebug
{
    private ?VariableDebugConfig $variableDebugConfig = null;

    public function __construct(
        private array                       $backtrace,
        private VariableDebugger            $variableDebugger,
        private readonly \Closure           $pendingDebugAction,
        private ?VariableDebugConfigBuilder $configBuilder = null,
    )
    {
        if (! $this->configBuilder) {
            $this->configBuilder = VariableDebugConfig::builder();
        }
    }

    public function __destruct()
    {
        $this->variableDebugger->setConfig(
        $this->variableDebugConfig ?:
            $this->configBuilder->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );

        ($this->pendingDebugAction)($this->backtrace, $this->variableDebugger);
    }

    public function on(VariableDebugConfig $config): void
    {
        $this->variableDebugConfig = $config;
    }

    public function runningInCli(bool $runningInCli = true): static
    {
        $this->configBuilder->runningInCli($runningInCli);

        return $this;
    }

    public function allowPrint(bool $allowPrint = true): static
    {
        $this->configBuilder->allowPrint($allowPrint);

        return $this;
    }

    public function presetCompact(?int $maxDepth = null, ?bool $showArrayOnlyFirstElement = null): static
    {
        $this->configBuilder->presetCompact($maxDepth, $showArrayOnlyFirstElement);

        return $this;
    }

    public function presetDetailed(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): static
    {
        $this->configBuilder->presetDetailed($maxDepth, $showArrayOnlyFirstElement);

        return $this;
    }

    public function useTerminationMode(VariableDebugConfigTerminationMode $terminationMode): static
    {
        $this->configBuilder->useTerminationMode($terminationMode);

        return $this;
    }

    public function useTerminationExitSuccess(): static
    {
        $this->configBuilder->useTerminationExitSuccess();

        return $this;
    }

    public function useTerminationThrowException(): static
    {
        $this->configBuilder->useTerminationThrowException();

        return $this;
    }

    public function showKeyOnly(array $ignoredShowKeyProperties = [], bool $showKeyOnly = true): static
    {
        $this->configBuilder
            ->withShowKeyOnly($showKeyOnly)
            ->withIgnoredShowKeyProperties($ignoredShowKeyProperties);

        return $this;
    }

    public function showExcludedCount(bool $showExcludedCount = true): static
    {
        $this->configBuilder->withShowExcludedCount($showExcludedCount);

        return $this;
    }

    public function withProperties(array $properties): static
    {
        $this->configBuilder->withProperties($properties);

        return $this;
    }

    public function withoutProperties(array $properties): static
    {
        $this->configBuilder->withoutProperties($properties);

        return $this;
    }

    public function classProperties(string $className, array $properties): static
    {
        $this->configBuilder->addClassProperties($className, $properties);

        return $this;
    }

    public function buildLaterClassProperties(string $className, callable $callback): static
    {
        $this->configBuilder->addBuildLaterClassProperties($className, $callback);

        return $this;
    }

    public function addClassPropertiesFromPlugin(VariableDebugClassPropertyPluginAdapter $plugin): static
    {
        $this->configBuilder->addClassPropertiesFromPlugin($plugin);

        return $this;
    }

    public function addClassPropertiesFromPluginLaravel(): static
    {
        $this->configBuilder->addClassPropertiesFromPluginLaravel();

        return $this;
    }

    public function addClassPropertiesFromPluginPDO(): static
    {
        $this->configBuilder->addClassPropertiesFromPluginPDO();

        return $this;
    }

    public function maxDepth(int $maxDepth): static
    {
        $this->configBuilder->withMaxDepth($maxDepth);

        return $this;
    }

    public function maxLine(int $maxLine): static
    {
        $this->configBuilder->withMaxLine($maxLine);

        return $this;
    }

    public function showValueType(bool $showValueType): static
    {
        $this->configBuilder->withShowValueType($showValueType);

        return $this;
    }
}
