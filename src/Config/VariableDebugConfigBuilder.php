<?php

namespace LightLa\VariableDebugger\Config;

class VariableDebugConfigBuilder extends VariableDebugConfigurator
{
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
}
