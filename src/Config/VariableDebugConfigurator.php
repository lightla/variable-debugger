<?php

namespace lightla\VariableDebugger\Config;

class VariableDebugConfigurator
{
    protected string $projectRootPath = '';
    protected ?int $maxDepth = null;
    protected ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    protected ?bool $showValueType = null;
    protected ?bool $showDetailAccessModifiers = null;

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
}
