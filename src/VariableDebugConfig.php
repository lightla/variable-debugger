<?php

namespace lightla\VariableDebugger;

use lightla\VariableDebugger\Config\VariableDebugConfigArrayShowMode;
use lightla\VariableDebugger\Config\VariableDebugConfigBuilder;
use lightla\VariableDebugger\Config\VariableDebugConfigTerminationMode;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliColorTheme;
use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliPrintStrategy;
use lightla\VariableDebugger\DebugStrategy\Web\VariableDebugWebColorTheme;
use lightla\VariableDebugger\DebugStrategy\Web\VariableDebugWebPrintStrategy;

class VariableDebugConfig
{
    private static ?VariableDebugConfig $globalConfig = null;

    private const DEFAULT_ALLOW_PRINT = true;
    private const DEFAULT_MAX_DEPTH = 10;
    private const DEFAULT_SHOW_ARRAY_MODE = VariableDebugConfigArrayShowMode::SHOW_ALL_ELEMENT;
    private const DEFAULT_TERMINATION_MODE = VariableDebugConfigTerminationMode::EXIT_SUCCESS;
    private const DEFAULT_MAX_LINE = 200;
    private const DEFAULT_SHOW_KEY_ONLY = false;
    private const DEFAULT_SHOW_EXCLUDED_COUNT = true;

    public function __construct(
        private ?VariableDebugPrintStrategy         $printStrategy = null,
        private ?bool                               $allowPrint = null,
        private ?string                             $projectRootPath = null,
        private ?int                                $maxDepth = null,
        private ?int                                $maxLine = null,
        private ?VariableDebugConfigTerminationMode $terminationMode = null,
        private ?VariableDebugConfigArrayShowMode   $showArrayMode = null,
        private ?bool                               $showValueType = null,
        private ?bool                               $showDetailAccessModifiers = null,
        private ?bool                               $showKeyOnly = null,
        private ?array                              $ignoredShowKeyProperties = null,
        private ?VariableDebugCliColorTheme         $cliTheme = null,
        private ?VariableDebugWebColorTheme         $webTheme = null,
        private ?array                              $includedProperties = [],
        private ?array                              $excludedProperties = [],
        private ?bool                               $showExcludedCount = null,
        private ?array                            $includedClassProperties = [],
        private ?array                            $includedBuildLaterClassProperties = [],
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

    /**
     * @return VariableDebugConfigTerminationMode|null
     */
    public function getTerminationMode(): ?VariableDebugConfigTerminationMode
    {
        return $this->terminationMode;
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
     * @return bool|null
     */
    public function getShowExcludedCount(): ?bool
    {
        return $this->showExcludedCount;
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
     * @return array|null
     */
    public function getIncludedBuildLaterClassProperties(): ?array
    {
        return $this->includedBuildLaterClassProperties;
    }

    /**
     * @return VariableDebugCliColorTheme|null
     */
    public function getCliTheme(): ?VariableDebugCliColorTheme
    {
        return $this->cliTheme;
    }

    /**
     * @return VariableDebugWebColorTheme|null
     */
    public function getWebTheme(): ?VariableDebugWebColorTheme
    {
        return $this->webTheme;
    }

    /**
     * @return bool|null
     */
    public function getAllowPrint(): ?bool
    {
        return $this->allowPrint;
    }

    /**
     * @return VariableDebugPrintStrategy|null
     */
    public function getPrintStrategy(): ?VariableDebugPrintStrategy
    {
        return $this->printStrategy;
    }

    #-----------------
    # RESOLVE BLOCK
    #-----------------

    /**
     * @return bool
     */
    public function resolveAllowPrintOrDefault(): bool
    {
        return $this->allowPrint ?? self::DEFAULT_ALLOW_PRINT;
    }

    /**
     * @return VariableDebugCliColorTheme
     */
    public function resolveCliThemeOrDefault(): VariableDebugCliColorTheme
    {
        return $this->cliTheme ?? VariableDebugCliColorTheme::noColor();
    }

    /**
     * @return VariableDebugWebColorTheme
     */
    public function resolveWebThemeOrDefault(): VariableDebugWebColorTheme
    {
        return $this->webTheme ?? VariableDebugWebColorTheme::dark();
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
    public function resolveBuildLaterClassPropertiesOrDefault(): array
    {
        return $this->includedBuildLaterClassProperties ?? [];
    }

    /**
     * @return array
     */
    public function resolveExcludedClassPropertiesOrDefault(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function resolveShowExcludedCount(): bool
    {
        return $this->showExcludedCount ?? self::DEFAULT_SHOW_EXCLUDED_COUNT;
    }

    /**
     * @return VariableDebugConfigArrayShowMode
     */
    public function resolveShowArrayModeOrDefault(): VariableDebugConfigArrayShowMode
    {
        return $this->showArrayMode ?? self::DEFAULT_SHOW_ARRAY_MODE;
    }

    public function resolveTerminationModeOrDefault(): VariableDebugConfigTerminationMode
    {
        return $this->terminationMode ?? self::DEFAULT_TERMINATION_MODE;
    }

    /**
     * @return VariableDebugPrintStrategy
     */
    public function resolvePrintStrategyOrDefault(): VariableDebugPrintStrategy
    {
        if ($this->printStrategy) {
            return $this->printStrategy;
        }

        $isCli = (
            PHP_SAPI === 'cli'
            && empty($_SERVER['HTTP_HOST'])
            && empty($_SERVER['REQUEST_URI'])
        );

        return $isCli
            ? new VariableDebugCliPrintStrategy()
            : new VariableDebugWebPrintStrategy();
    }

    /**
     * @param ?VariableDebugConfig $config
     * @return VariableDebugConfig
     */
    public function merge(?VariableDebugConfig $config): VariableDebugConfig
    {
        return new VariableDebugConfig(
            $this->printStrategy ?? $config?->getPrintStrategy(),
            $this->allowPrint ?? $config?->getAllowPrint(),
            $this->projectRootPath ?? $config?->getProjectRootPath(),
            $this->maxDepth ?? $config?->getMaxDepth(),
            $this->maxLine ?? $config?->getMaxLine(),
            $this->terminationMode ?? $config?->getTerminationMode(),
            $this->showArrayMode ?? $config?->getShowArrayMode(),
            $this->showValueType ?? $config?->getShowValueType(),
            $this->showDetailAccessModifiers ?? $config?->getShowDetailAccessModifiers(),
            $this->showKeyOnly ?? $config?->getShowKeyOnly(),
            $this->ignoredShowKeyProperties ?? $config?->getIgnoredShowKeyProperties(),
            $this->cliTheme ?? $config?->getCliTheme(),
            $this->webTheme ?? $config?->getWebTheme(),
            $this->includedProperties ?? $config?->getIncludedProperties(),
            $this->excludedProperties ?? $config?->getExcludedProperties(),
            $this->showExcludedCount ?? $config?->getShowExcludedCount(),
            array_replace(
                $config?->getIncludedClassProperties() ?? [],
                $this->includedClassProperties ?? []
            ),
            array_replace(
                $config?->getIncludedBuildLaterClassProperties() ?? [],
                $this->includedBuildLaterClassProperties ?? []
            ),
        );
    }
}
