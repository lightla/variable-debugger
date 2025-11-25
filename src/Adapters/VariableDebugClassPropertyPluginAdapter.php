<?php

namespace lightla\VariableDebugger\Adapters;

use lightla\VariableDebugger\Config\VariableDebugConfigurator;

interface VariableDebugClassPropertyPluginAdapter
{
    public function applyTo(VariableDebugConfigurator $configurator): void;
}