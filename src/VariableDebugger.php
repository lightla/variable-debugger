<?php
namespace lightla\VariableDebugger;

use lightla\VariableDebugger\DebugStrategy\Cli\VariableDebugPrintCliPrintStrategy;
use lightla\VariableDebugger\DebugStrategy\Html\VariableDebugPrintHtmlPrintStrategy;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebugger
{
    private VariableDebugPrintStrategy $printStrategy;

    public function __construct(
        private VariableDebugConfig $config,
        ?VariableDebugPrintStrategy $printStrategy = null,
    )
    {
        if ($printStrategy) {
            $this->printStrategy = $printStrategy;
        } else {
            $this->autoDetectPrintStrategy();
        }
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

    public function setPrintStrategy(VariableDebugPrintStrategy $printStrategy): self
    {
        $this->printStrategy = $printStrategy;

        return $this;
    }

    public function autoDetectPrintStrategy(): self
    {
        $isCli = (PHP_SAPI === 'cli');

        $this->printStrategy = $isCli
            ? new VariableDebugPrintCliPrintStrategy()
            : new VariableDebugPrintHtmlPrintStrategy();

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
        $this->printStrategy->printFromTrace($this->config, $backtrace, ...$vars);
    }

    #------------------
    # HELPERS
    #------------------
}
