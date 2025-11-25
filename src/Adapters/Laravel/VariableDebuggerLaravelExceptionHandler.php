<?php
namespace lightla\VariableDebugger\Adapters\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;
use Throwable;

class VariableDebuggerLaravelExceptionHandler implements ExceptionHandler {
    private ExceptionHandler $originalHandler;

    public function __construct(ExceptionHandler $originalHandler)
    {
        $this->originalHandler = $originalHandler;
    }

    public function renderForConsole($output, Throwable $e)
    {
        if ($e instanceof VariableDebugGracefulExitException) {
            return;
        }

        $this->originalHandler->renderForConsole($output, $e);
    }

    public function report(Throwable $e)
    {
        if ($e instanceof VariableDebugGracefulExitException) {
            return;
        }

        $this->originalHandler->report($e);
    }

    public function shouldReport(Throwable $e)
    {
        if ($e instanceof VariableDebugGracefulExitException) {
            return false;
        }

        return $this->originalHandler->shouldReport($e);
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof VariableDebugGracefulExitException) {
            return response('', 200);
        }

        return $this->originalHandler->render($request, $e);
    }
}