<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\VariableDebugConfig;

trait VariableDebugConfigBuilderBuildTrait
{
    private function doBuild(): VariableDebugConfig
    {
        return new VariableDebugConfig(
            $this->projectRootPath,
            $this->maxDepth,
            $this->maxLine,
            $this->showArrayMode,
            $this->showValueType,
            $this->showDetailAccessModifiers,
            $this->showKeyOnly,
            $this->ignoredShowKeyProperties,
            $this->cliTheme,
            $this->includedProperties,
            $this->excludedProperties,
            $this->showExcludedCount,
            $this->includedClassProperties,
        );
    }

    /**
     * @return void
     */
    private function doBuildWithInjectGlobal(): void
    {
        VariableDebugConfig::setGlobalConfig($this->doBuild());
    }
}
