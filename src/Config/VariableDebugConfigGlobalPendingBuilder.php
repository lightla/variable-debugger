<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\VariableDebugConfig;

class VariableDebugConfigGlobalPendingBuilder extends VariableDebugConfigurator
{
   public function __destruct()
   {
       $this->injectGlobal();
   }

    private function build(): VariableDebugConfig
    {
        return new VariableDebugConfig(
            $this->projectRootPath,
            $this->maxDepth,
            $this->showArrayMode,
            $this->showValueType,
            $this->showDetailAccessModifiers,
            $this->cliTheme
        );
    }

    /**
     * @return void
     */
    private function injectGlobal(): void
    {
        VariableDebugConfig::setGlobalConfig($this->build());
    }
}
