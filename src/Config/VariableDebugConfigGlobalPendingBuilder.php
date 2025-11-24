<?php

namespace lightla\VariableDebugger\Config;

class VariableDebugConfigGlobalPendingBuilder extends VariableDebugConfigurator
{
    use VariableDebugConfigBuilderBuildTrait;

   public function __destruct()
   {
       $this->doBuildWithInjectGlobal();
   }
}
