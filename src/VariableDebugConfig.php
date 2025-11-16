<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Config\VariableDebugConfigArrayShowMode;
use lightla\VariableDebugger\Config\VariableDebugConfigBuilder;
use lightla\VariableDebugger\DebugStrategy\VariableDebugCliColorTheme;

class VariableDebugConfig
{
    private static ?VariableDebugConfig $globalConfig = null;

    public function __construct(
        private ?string $projectRootPath = '',
        private ?int $maxDepth = 10,
        private ?VariableDebugConfigArrayShowMode $showArrayMode = null,
        private ?bool $showValueType = null,
        private ?bool $showDetailAccessModifiers = null,
        private ?VariableDebugCliColorTheme $cliTheme = null,
    )
    {
    }

    /**
     * @return VariableDebugConfigBuilder
     */
    public static function builder(): VariableDebugConfigBuilder
    {
        return new VariableDebugConfigBuilder();
    }

    /**
     * @param VariableDebugConfig $config
     * @return void
     */
    public static function setGlobalConfig(VariableDebugConfig $config): void
    {
        self::$globalConfig = $config;
    }

    public static function getGlobalConfig(): ?VariableDebugConfig
    {
        return self::$globalConfig;
    }

    public function getProjectRootPath(): ?string
    {
        return $this->projectRootPath;
    }

    public function getMaxDepth(): ?int
    {
        return $this->maxDepth;
    }

    public function getShowArrayMode(): ?VariableDebugConfigArrayShowMode
    {
        return $this->showArrayMode;
    }

    public function getShowValueType(): ?bool
    {
        return $this->showValueType;
    }

    public function getShowDetailAccessModifiers(): ?bool
    {
        return $this->showDetailAccessModifiers;
    }

    /**
     * @return VariableDebugCliColorTheme|null
     */
    public function getCliTheme(): ?VariableDebugCliColorTheme
    {
        return $this->cliTheme;
    }

    /**
     * @param ?VariableDebugConfig $config
     * @return VariableDebugConfig
     */
    public function merge(?VariableDebugConfig $config): VariableDebugConfig
    {
        return new VariableDebugConfig(
            $this->projectRootPath ?? $config?->getProjectRootPath(),
            $this->maxDepth ?? $config?->getMaxDepth(),
            $this->showArrayMode ?? $config?->getShowArrayMode(),
            $this->showValueType ?? $config?->getShowValueType(),
            $this->showDetailAccessModifiers ?? $config?->getShowDetailAccessModifiers(),
            $this->cliTheme ?? $config?->getCliTheme(),
        );
    }
}
