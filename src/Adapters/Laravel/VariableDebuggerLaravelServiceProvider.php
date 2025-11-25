<?php
namespace lightla\VariableDebugger\Adapters\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebuggerLaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        # Using Decorator Pattern for cover original ExceptionHandler
        $this->app->extend(ExceptionHandler::class, function (ExceptionHandler $originalHandler) {
            return new VariableDebuggerLaravelExceptionHandler($originalHandler);
        });
    }

    /**
     * Example for Laravel 12 (not use because Laravel 10 not have Exceptions::class)
     *
     * @version Laravel 12
     * @return void
     */
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

                $exceptions->dontReport(VariableDebugGracefulExitException::class);
            })(new Exceptions($handler)),
        );
    }
}