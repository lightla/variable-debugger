<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliColorTheme;

class VariableDebugConfigurator
{
    protected string $projectRootPath = '';
    protected ?int $maxDepth = null;
    protected ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    protected ?bool $showValueType = null;
    protected ?bool $showDetailAccessModifiers = null;
    protected ?VariableDebugCliColorTheme $cliTheme = null;

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
