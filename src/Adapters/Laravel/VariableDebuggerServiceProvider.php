<?php
namespace LightLa\VariableDebugger\Adapters\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Configuration\Exceptions;
use LightLa\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class VariableDebuggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->afterResolving('exceptions', function (Exceptions $exceptions) {
            $exceptions->render(function (VariableDebugGracefulExitException $e) {
                return response('', 200);
            });
        });
    }
}