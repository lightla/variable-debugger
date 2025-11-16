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
            $this->showArrayMode,
            $this->showValueType,
            $this->showDetailAccessModifiers,
            $this->cliTheme,
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
