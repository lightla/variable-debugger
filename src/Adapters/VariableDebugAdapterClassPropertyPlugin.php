<?php

namespace lightla\VariableDebugger\Adapters;

use lightla\VariableDebugger\Config\VariableDebugConfigurator;

interface VariableDebugAdapterClassPropertyPlugin
{
    public function extendClassProperties(VariableDebugConfigurator $configurator): void;
}