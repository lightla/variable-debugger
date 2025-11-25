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
    private const DEFAULT_MAX_LINE = 200;
    private const DEFAULT_SHOW_KEY_ONLY = false;

    public function __construct(
        private ?string $projectRootPath = '',
        private ?int $maxDepth = null,
        private ?int $maxLine = null,
        private ?VariableDebugConfigArrayShowMode $showArrayMode = null,
        private ?bool $showValueType = null,
        private ?bool $showDetailAccessModifiers = null,
        private ?bool $showKeyOnly = null,
        private ?array $ignoredShowKeyProperties = null,
        private ?VariableDebugCliColorTheme $cliTheme = null,
        private ?array $includedProperties = [],
        private ?array $excludedProperties = [],
        private ?array $includedClassProperties = [],
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
     * @return bool|null
     */
    public function getShowKeyOnly(): ?bool
    {
        return $this->showKeyOnly;
    }

    /**
     * @return array|null
     */
    public function getIgnoredShowKeyProperties(): ?array
    {
        return $this->ignoredShowKeyProperties;
    }

    /**
     * @return array|null
     */
    public function getExcludedProperties(): ?array
    {
        return $this->excludedProperties;
    }

    /**
     * @return array|null
     */
    public function getIncludedProperties(): ?array
    {
        return $this->includedProperties;
    }

    /**
     * @return array|null
     */
    public function getIncludedClassProperties(): ?array
    {
        return $this->includedClassProperties;
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
     * @return bool
     */
    public function resolveShowKeyOnlyOrDefault(): bool
    {
        return $this->showKeyOnly ?? self::DEFAULT_SHOW_KEY_ONLY;
    }

    /**
     * @return array
     */
    public function resolveIgnoredShowKeyPropertiesOrDefault(): array
    {
        return $this->ignoredShowKeyProperties ?? [];
    }

    /**
     * @return array
     */
    public function resolveIncludedPropertiesOrDefault(): array
    {
        return $this->includedProperties ?? [];
    }

    /**
     * @return array
     */
    public function resolveExcludedPropertiesOrDefault(): array
    {
        return $this->excludedProperties ?? [];
    }

    /**
     * @return array
     */
    public function resolveIncludedClassPropertiesOrDefault(): array
    {
        return $this->includedClassProperties ?? [];
    }

    /**
     * @return array
     */
    public function resolveExcludedClassPropertiesOrDefault(): array
    {
        return [];
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
            $this->showKeyOnly ?? $config?->getShowKeyOnly(),
            $this->ignoredShowKeyProperties ?? $config?->getIgnoredShowKeyProperties(),
            $this->cliTheme ?? $config?->getCliTheme(),
            $this->includedProperties ?? $config?->getIncludedProperties(),
            $this->excludedProperties ?? $config?->getExcludedProperties(),
            array_replace(
                $config?->getIncludedClassProperties() ?? [],
                $this->includedClassProperties ?? []
            )
        );
    }
}
