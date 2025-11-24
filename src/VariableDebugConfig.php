<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Config\VariableDebugConfigArrayShowMode;
use lightla\VariableDebugger\Config\VariableDebugConfigBuilder;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliColorTheme;

class VariableDebugConfig
{
    private static ?VariableDebugConfig $globalConfig = null;

    private const DEFAULT_MAX_DEPTH = 10;
    private const DEFAULT_SHOW_ARRAY_MODE = VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT;
    private const DEFAULT_MAX_LINE = 50;

    public function __construct(
        private ?string $projectRootPath = '',
        private ?int $maxDepth = null,
        private ?int $maxLine = null,
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

    /**
     * @return int|null
     */
    public function getMaxLine(): ?int
    {
        return $this->maxLine;
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
     * @return VariableDebugCliColorTheme
     */
    public function resolveCliThemeOrDefault(): VariableDebugCliColorTheme
    {
        return $this->cliTheme ?? VariableDebugCliColorTheme::noColor();
    }

    /**
     * @return int
     */
    public function resolveMaxDepthOrDefault(): int
    {
        return $this->maxDepth ?? self::DEFAULT_MAX_DEPTH;
    }

    /**
     * @return int
     */
    public function resolveMaxLineOrDefault(): int
    {
        return $this->maxLine ?? self::DEFAULT_MAX_LINE;
    }

    /**
     * @return VariableDebugConfigArrayShowMode
     */
    public function resolveShowArrayModeOrDefault(): VariableDebugConfigArrayShowMode
    {
        return $this->showArrayMode ?? self::DEFAULT_SHOW_ARRAY_MODE;
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
            $this->maxLine ?? $config?->getMaxLine(),
            $this->showArrayMode ?? $config?->getShowArrayMode(),
            $this->showValueType ?? $config?->getShowValueType(),
            $this->showDetailAccessModifiers ?? $config?->getShowDetailAccessModifiers(),
            $this->cliTheme ?? $config?->getCliTheme(),
        );
    }
}
