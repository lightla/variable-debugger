<?php

namespace LightLa\VariableDebugger\Config;

class VariableDebugConfigGlobalPendingBuilder extends VariableDebugConfigBuilder
{
   public function __destruct()
   {
       $this->injectGlobal();
   }
}
