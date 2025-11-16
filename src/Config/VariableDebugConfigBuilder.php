<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\VariableDebugConfig;

class VariableDebugConfigBuilder extends VariableDebugConfigurator
{
    use VariableDebugConfigBuilderBuildTrait;

    public function build(): VariableDebugConfig
    {
        return $this->doBuild();
    }

    /**
     * @return void
     */
    public function injectGlobal(): void
    {
        $this->doBuildWithInjectGlobal();
    }
}
