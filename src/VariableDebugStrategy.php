<?php

namespace lightla\VariableDebugger;

interface VariableDebugStrategy
{
    /**
     * @param VariableDebugConfig $config
     * @param array $backtrace
     * @param ...$vars
     * @return void
     * @throws \ReflectionException
     */
    public function dumpFromTrace(VariableDebugConfig $config, array $backtrace, ...$vars): void;
}