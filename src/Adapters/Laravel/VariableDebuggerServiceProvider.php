<?php
namespace lightla\VariableDebugger\Adapters\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Configuration\Exceptions;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebuggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->afterResolving(
            \Illuminate\Foundation\Exceptions\Handler::class,
            fn ($handler) => (function (Exceptions $exceptions): void {
                $exceptions->render(function (VariableDebugGracefulExitException $e) {
                    return response('', 200);
                });
            })(new Exceptions($handler)),
        );
    }
}