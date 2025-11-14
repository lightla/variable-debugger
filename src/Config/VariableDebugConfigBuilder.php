<?php

namespace LightLa\VariableDebugger\Config;

class VariableDebugConfigBuilder
{
    private string $projectRootPath = '';
    private ?int $maxDepth = null;
    private ?VariableDebugConfigArrayShowMode $showArrayMode = null;
    private ?bool $showValueType = null;
    private ?bool $showDetailAccessModifiers = null;

    public function build(): VariableDebugConfig
    {
        return new VariableDebugConfig(
            $this->projectRootPath,
            $this->maxDepth,
            $this->showArrayMode,
            $this->showValueType,
            $this->showDetailAccessModifiers,
        );
    }

    /**
     * @return void
     */
    public function injectGlobal(): void
    {
        VariableDebugConfig::setGlobalConfig($this->build());
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
