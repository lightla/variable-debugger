<?php
namespace lightla\VariableDebugger;

use lightla\VariableDebugger\DebugStrategy\VariableDebugViaCliStrategy;
use lightla\VariableDebugger\DebugStrategy\VariableDebugViaHtmlStrategy;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebugger
{
    private VariableDebugStrategy $debugStrategy;

    public function __construct(
        private VariableDebugConfig $config,
        ?VariableDebugStrategy $debugStrategy = null,
    )
    {
        if ($debugStrategy) {
            $this->debugStrategy = $debugStrategy;
        } else {
            $this->autoDetectDebugStrategy();
        }
    }

    public function setConfig(VariableDebugConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function setDebugStrategy(VariableDebugStrategy $debugStrategy): self
    {
        $this->debugStrategy = $debugStrategy;

        return $this;
    }

    public function autoDetectDebugStrategy(): self
    {
        $isCli = (PHP_SAPI === 'cli');

        $this->debugStrategy = $isCli
            ? new VariableDebugViaCliStrategy()
            : new VariableDebugViaHtmlStrategy();

        return $this;
    }

    public function dump(...$vars)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->dumpFromTrace($backtrace, ...$vars);
    }

    /**
     * @param ...$vars
     * @return void
     * @throws VariableDebugGracefulExitException
     */
    public function dd(...$vars): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->dumpFromTrace($backtrace, $vars);

        $this->exit();
    }

    /**
     * @return void
     * @throws VariableDebugGracefulExitException
     */
    public function exit(): void
    {
        throw new VariableDebugGracefulExitException();
    }

    /**
     * @param $backtrace
     * @param ...$vars
     * @return void
     * @throws VariableDebugGracefulExitException|\ReflectionException
     */
    public function ddFromTrace($backtrace, ...$vars): void
    {
        $this->dumpFromTrace($backtrace, ...$vars);

        $this->exit();
    }

    /**
     * @param array $backtrace
     * @param ...$vars
     * @return void
     * @throws \ReflectionException
     */
    public function dumpFromTrace(array $backtrace, ...$vars): void
    {
        $this->debugStrategy->dumpFromTrace($this->config, $backtrace, ...$vars);
    }

    #------------------
    # HELPERS
    #------------------
}
