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

    public function forLaravelModel(): static
    {
        $this->includeProperties([
            'connection',
            'table',
            'primaryKey',
            'keyType',
            'incrementing',
            'with',
            'withCount',
            'preventsLazyLoading',
            'perPage',
            'exists',
            'wasRecentlyCreated',
            'escapeWhenCastingToString',
            'attributes',
            'original',
            'changes',
            'previous',
            'casts',
            'classCastCache',
            'attributeCastCache',
            'dateFormat',
            'appends',
            'dispatchesEvents',
            'observables',
            'relations',
            'touches',
            'relationAutoloadCallback',
            'relationAutoloadContext',
            'timestamps',
            'usesUniqueIds',
            'hidden',
            'visible',
            'fillable',
            'guarded',
            'authPasswordName',
            'rememberTokenName',
        ]);

        return $this;
    }

    public function onFull(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): static
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->configFull($maxDepth, $showArrayOnlyFirstElement)
                ->build()
                ->merge(VariableDebugConfig::getGlobalConfig())
        );

        return $this;
    }

    public function showKeyOnly(?bool $showKeyOnly, ?array $ignoredShowKeyProperties = null): static
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->withShowKeyOnly($showKeyOnly)
                ->withIgnoredShowKeyProperties($ignoredShowKeyProperties)
                ->build()
                ->merge($this->variableDebugger->getConfig())
        );

        return $this;
    }

    public function includeProperties(array $properties): static
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->withProperties($properties)
                ->build()
                ->merge($this->variableDebugger->getConfig())
        );

        return $this;
    }

    public function excludeProperties(array $properties): static
    {
        $this->variableDebugger->setConfig(
            VariableDebugConfig::builder()
                ->withoutProperties($properties)
                ->build()
                ->merge($this->variableDebugger->getConfig())
        );

        return $this;
    }
}
