<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\Adapters\Laravel\VariableDebugClassPropertyPluginAdapterLaravel;
use lightla\VariableDebugger\Adapters\Laravel\VariableDebugPropertyLaravel;
use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliColorTheme;

class VariableDebugConfigurator
{
    protected string $projectRootPath = '';
    protected ?int $maxDepth = null;
    protected ?int $maxLine = null;
    protected ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    protected ?bool $showValueType = null;
    protected ?bool $showDetailAccessModifiers = null;
    protected ?VariableDebugCliColorTheme $cliTheme = null;
    protected ?bool $showKeyOnly = null;
    protected ?array $ignoredShowKeyProperties = null;
    protected ?array $includedProperties = null;
    protected ?array $excludedProperties = null;
    protected ?array $includedClassProperties = null;
    /**
     * @param int|null $maxDepth
     * @param bool $showArrayOnlyFirstElement
     * @return $this
     */
    public function configShort(?int $maxDepth = null, ?bool $showArrayOnlyFirstElement = null): static
    {
        $this->withMaxDepth($maxDepth)
            ->withShowArrayMode(is_null($showArrayOnlyFirstElement)
                ? null
                : ( $showArrayOnlyFirstElement
                    ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                    : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                )
            )
            ->withShowDetailAccessModifiers(false)
            ->withShowValueType(false);

        return $this;
    }

    public function configFull(?int $maxDepth = null, bool $showArrayOnlyFirstElement = false): static
    {
        $this->withMaxDepth($maxDepth)
            ->withShowArrayMode(is_null($showArrayOnlyFirstElement)
                ? null
                : ( $showArrayOnlyFirstElement
                    ? VariableDebugConfigArrayShowMode::SHOW_FIRST_ELEMENT
                    : VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT
                )
            )
            ->withShowDetailAccessModifiers(true)
            ->withShowValueType(true);

        return $this;
    }

    public function withProjectRootPath(string $projectRootPath): self
    {
        $this->projectRootPath = $projectRootPath;

        return $this;
    }

    public function withMaxDepth(?int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    public function withShowArrayMode(?VariableDebugConfigArrayShowMode $showArrayMode): self
    {
        $this->showArrayMode = $showArrayMode;

        return $this;
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

    public function withIgnoredShowKeyProperties(?array $ignoredShowKeyProperties): self
    {
        $this->ignoredShowKeyProperties = $ignoredShowKeyProperties;

        return $this;
    }

    public function withProperties(?array $properties): self
    {
        $this->includedProperties = $properties;

        return $this;
    }

    public function withoutProperties(?array $withoutProperties): self
    {
        $this->excludedProperties = $withoutProperties;

        return $this;
    }

    public function withClassProperties(string $className, array $properties): self
    {
        $this->includedClassProperties[$className] = $properties;

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

    public function useCliThemeDark(): self
    {
        $this->cliTheme = VariableDebugCliColorTheme::dark();

        return $this;
    }

    public function useCliThemeLight(): self
    {
        $this->cliTheme = VariableDebugCliColorTheme::light();

        return $this;
    }

    public function useCliThemeNoColor(): self
    {
        $this->cliTheme = VariableDebugCliColorTheme::noColor();

        return $this;
    }

    public function useWebThemeDark(): self
    {
        # Comming soon ^^ (always dark)

        return $this;
    }
}
