<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\Adapters\Laravel\VariableDebugClassPropertyPluginAdapterLaravel;
use lightla\VariableDebugger\Adapters\PDO\VariableDebugClassPropertyPluginAdapterPDO;
use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliColorTheme;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliPrintStrategy;
use lightla\VariableDebugger\DebugStrategy\Web\VariableDebugWebColorTheme;
use lightla\VariableDebugger\DebugStrategy\Web\VariableDebugWebPrintStrategy;
use lightla\VariableDebugger\VariableDebugPrintStrategy;

class VariableDebugConfigurator
{
    protected ?bool $allowPrint = null;
    protected ?string $projectRootPath = null;
    protected ?int $maxDepth = null;
    protected ?int $maxLine = null;
    protected ?VariableDebugConfigTerminationMode $terminationMode = null;
    protected ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    protected ?bool $showValueType = null;
    protected ?bool $showDetailAccessModifiers = null;
    protected ?VariableDebugCliColorTheme $cliTheme = null;
    protected ?VariableDebugWebColorTheme $webTheme = null;
    protected ?bool $showKeyOnly = null;
    protected ?array $ignoredShowKeyProperties = null;
    protected ?array $includedProperties = null;
    protected ?array $excludedProperties = null;
    protected ?bool $showExcludedCount = null;
    protected ?array $includedClassProperties = null;
    protected ?array $includedBuildLaterClassProperties = null;
    protected ?array $includedPatternProperties = null;
    protected ?VariableDebugPrintStrategy $printStrategy = null;

    public function allowPrint(?bool $allowPrint): static
    {
        $this->allowPrint = $allowPrint;

        return $this;
    }

    public function enable(): static
    {
        $this->allowPrint = true;

        return $this;
    }

    public function disable(): static
    {
        $this->allowPrint = false;

        return $this;
    }

    public function presetCompact(?int $maxDepth = null, ?bool $showArrayOnlyFirstElement = null): static
    {
        return $this
            ->withMaxDepth($maxDepth)
            ->withShowArrayMode(
                is_null($showArrayOnlyFirstElement)
                    ? null
                    : (
                        $showArrayOnlyFirstElement
                            ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                            : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                    ),
            )
            ->withShowDetailAccessModifiers(false)
            ->withShowValueType(false)
            ->withShowExcludedCount(false);
    }

    public function presetDetailed(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): static
    {
        return $this
            ->withMaxDepth($maxDepth)
            ->withShowArrayMode(
                is_null($showArrayOnlyFirstElement)
                    ? null
                    : (
                        $showArrayOnlyFirstElement
                            ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                            : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                    ),
            )
            ->withShowDetailAccessModifiers(true)
            ->withShowValueType(true)
            ->withShowExcludedCount(true);
    }

    public function runningInCli(?bool $runningInCli = null): self
    {
        if (is_bool($runningInCli)) {
            $this->printStrategy = $runningInCli
                ? new VariableDebugCliPrintStrategy()
                : new VariableDebugWebPrintStrategy();
        }

        return $this;
    }

    public function withProjectRootPath(?string $projectRootPath): self
    {
        $this->projectRootPath = $projectRootPath;

        return $this;
    }

    public function withMaxDepth(?int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    public function withMaxLine(?int $maxLine): self
    {
        $this->maxLine = $maxLine;

        return $this;
    }

    public function withShowArrayMode(?VariableDebugConfigArrayShowMode $showArrayMode): self
    {
        $this->showArrayMode = $showArrayMode;

        return $this;
    }

    public function useTerminationMode(VariableDebugConfigTerminationMode $terminationMode): self
    {
        $this->terminationMode = $terminationMode;

        return $this;
    }

    public function useTerminationExitSuccess(): self
    {
        return $this->useTerminationMode(VariableDebugConfigTerminationMode::EXIT_SUCCESS);
    }

    public function useTerminationThrowException(): self
    {
        return $this->useTerminationMode(VariableDebugConfigTerminationMode::THROW_EXCEPTION);
    }

    public function withShowValueType(?bool $showValueType): self
    {
        $this->showValueType = $showValueType;

        return $this;
    }

    public function withShowDetailAccessModifiers(?bool $showDetailAccessModifiers): self
    {
        $this->showDetailAccessModifiers = $showDetailAccessModifiers;

        return $this;
    }

    public function withShowKeyOnly(?bool $showKeyOnly): self
    {
        $this->showKeyOnly = $showKeyOnly;

        return $this;
    }

    public function withShowExcludedCount(?bool $showExcludedCount): self
    {
        $this->showExcludedCount = $showExcludedCount;

        return $this;
    }

    public function withIgnoredShowKeyProperties(?array $ignoredShowKeyProperties): self
    {
        if (is_null($ignoredShowKeyProperties)) {
            $this->ignoredShowKeyProperties = null;
        } else {
            $this->ignoredShowKeyProperties = array_unique(array_merge(
                $this->ignoredShowKeyProperties ?? [],
                $ignoredShowKeyProperties,
            ));
        }

        return $this;
    }

    public function withProperties(?array $properties): self
    {
        if (is_null($properties)) {
            $this->includedProperties = null;
        } else {
            $this->includedProperties = array_merge(
                $this->includedProperties ?? [],
                $this->normalizeProperties($properties, 'include properties'),
            );
        }

        return $this;
    }

    public function withPatternProperties(?array $patterns): self
    {
        if (is_null($patterns)) {
            $this->includedPatternProperties = null;
        } else {
            $this->includedPatternProperties = array_merge(
                $this->includedPatternProperties ?? [],
                $this->normalizeProperties($patterns, 'include property patterns'),
            );
        }

        return $this;
    }

    public function withoutProperties(?array $withoutProperties): self
    {
        if (is_null($withoutProperties)) {
            $this->excludedProperties = null;
        } else {
            $this->excludedProperties = array_unique(array_merge($this->excludedProperties ?? [], $withoutProperties));
        }

        return $this;
    }

    public function addClassProperties(string $className, array $properties): self
    {
        $this->includedClassProperties[$className] = array_merge(
            $this->includedClassProperties[$className] ?? [],
            $this->normalizeProperties($properties, "adding class property {$className}"),
        );

        return $this;
    }

    public function addBuildLaterClassProperties(string $className, callable $properties): self
    {
        $this->includedBuildLaterClassProperties[$className] = $properties;

        return $this;
    }

    public function addClassPropertiesFromPlugin(VariableDebugClassPropertyPluginAdapter $plugin): self
    {
        $plugin->applyTo($this);

        return $this;
    }

    public function addClassPropertiesFromPluginLaravel(): self
    {
        return $this->addClassPropertiesFromPlugin(new VariableDebugClassPropertyPluginAdapterLaravel());
    }

    public function addClassPropertiesFromPluginPDO(): self
    {
        return $this->addClassPropertiesFromPlugin(new VariableDebugClassPropertyPluginAdapterPDO());
    }

    public function useCliTheme(VariableDebugCliColorTheme $theme): self
    {
        $this->cliTheme = $theme;

        return $this;
    }

    public function useWebTheme(VariableDebugWebColorTheme $theme): self
    {
        $this->webTheme = $theme;

        return $this;
    }

    public function useCliThemeDark(): self
    {
        return $this->useCliTheme(VariableDebugCliColorTheme::dark());
    }

    public function useCliThemeLight(): self
    {
        return $this->useCliTheme(VariableDebugCliColorTheme::light());
    }

    public function useCliThemeNoColor(): self
    {
        return $this->useCliTheme(VariableDebugCliColorTheme::noColor());
    }

    public function useWebThemeDark(): self
    {
        return $this->useWebTheme(VariableDebugWebColorTheme::dark());
    }

    public function useWebThemeLight(): self
    {
        return $this->useWebTheme(VariableDebugWebColorTheme::light());
    }

    public function useWebThemeNoColor(): self
    {
        return $this->useWebTheme(VariableDebugWebColorTheme::noColor());
    }

    private function normalizeProperties(array $properties, string $errorPrefix): array
    {
        $normalized = [];

        foreach ($properties as $property => $value) {
            if (is_bool($value)) {
                $normalized[$property] = $value
                    ? VariableDebugClassPropertyShowValueMode::SHOW_DETAIL
                    : VariableDebugClassPropertyShowValueMode::SHOW_TYPE_ONLY;

                continue;
            }

            if ($value instanceof VariableDebugClassPropertyShowValueMode) {
                $normalized[$property] = $value;

                continue;
            }

            if (is_int($property)) {
                $normalized[$value] = VariableDebugClassPropertyShowValueMode::SHOW_DETAIL;

                continue;
            }

            throw new \RuntimeException("{$errorPrefix} has error occur");
        }

        return $normalized;
    }
}
