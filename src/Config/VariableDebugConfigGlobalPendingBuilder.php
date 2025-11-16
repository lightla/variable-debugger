<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\VariableDebugConfig;

class VariableDebugConfigGlobalPendingBuilder extends VariableDebugConfigurator
{
    use VariableDebugConfigBuilderBuildTrait;

   public function __destruct()
   {
       $this->doBuildWithInjectGlobal();
   }

    public function build(): VariableDebugConfig
    {
        return $this->doBuild();
    }
}
