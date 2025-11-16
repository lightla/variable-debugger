<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\DebugStrategy\VariableDebugCliColorTheme;

class VariableDebugConfigurator
{
    protected string $projectRootPath = '';
    protected ?int $maxDepth = null;
    protected ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    protected ?bool $showValueType = null;
    protected ?bool $showDetailAccessModifiers = null;
    protected ?VariableDebugCliColorTheme $cliTheme = null;

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

    public function withShowArrayMode(VariableDebugConfigArrayShowMode $showArrayMode): self
    {
        $this->showArrayMode = $showArrayMode;

        return $this;
    }

    public function withShowValueType(bool $showValueType): self
    {
        $this->showValueType = $showValueType;

        return $this;
    }

    public function withShowDetailAccessModifiers(bool $showDetailAccessModifiers): self
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
