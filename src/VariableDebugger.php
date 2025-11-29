<?php
namespace lightla\VariableDebugger;

use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugCliPrintStrategy;
use lightla\VariableDebugger\DebugStrategy\Web\VariableDebugWebPrintStrategy;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebugger
{
    public function __construct(
        private VariableDebugConfig $config,
    )
    {
    }

    /**
     * @return VariableDebugConfig
     */
    public function getConfig(): VariableDebugConfig
    {
        return $this->config;
    }

    public function setConfig(VariableDebugConfig $config): self
    {
        $this->config = $config;

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
        $this->config->resolvePrintStrategyOrDefault()
            ->printFromTrace($this->config, $backtrace, ...$vars);
    }

    #------------------
    # HELPERS
    #------------------
}
