<?php

namespace lightla\VariableDebugger;

interface VariableDebugPrintStrategy
{
    /**
     * @param VariableDebugConfig $config
     * @param array $backtrace
     * @param ...$vars
     * @return void
     * @throws \ReflectionException
     */
    public function printFromTrace(VariableDebugConfig $config, array $backtrace, ...$vars): void;
}