<?php
namespace lightla\VariableDebugger\Adapters\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;
use Throwable;

class VariableDebuggerLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bootForWeb();
    }

    public function register(): void
    {
        $this->registerForConsole();
    }

    private function bootForWeb(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->afterResolving(
            Handler::class,
            fn (Handler $handler) => (function (Exceptions $exceptions) use ($handler): void {
                $exceptions->render(function (VariableDebugGracefulExitException $e) {
                    return response('', 200);
                });
            })(new Exceptions($handler)),
        );
    }

    private function registerForConsole(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        # Using Decorator Pattern để "bọc" ExceptionHandler gốc
        $this->app->extend(ExceptionHandler::class, function (ExceptionHandler $originalHandler, $app) {
            return new class($originalHandler) implements ExceptionHandler {
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
                    return $this->originalHandler->render($request, $e);
                }
            };
        });
    }
}