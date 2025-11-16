<?php
namespace lightla\VariableDebugger\Adapters\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Configuration\Exceptions;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;
use Throwable;

class VariableDebuggerServiceProvider extends ServiceProvider
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
        $this->app->afterResolving(
            \Illuminate\Foundation\Exceptions\Handler::class,
            fn (\Illuminate\Foundation\Exceptions\Handler $handler) => (function (Exceptions $exceptions) use ($handler): void {
                $exceptions->render(function (VariableDebugGracefulExitException $e) {
                    return response('', 200);
                });
            })(new Exceptions($handler)),
        );
    }

    private function registerForConsole(): void
    {
        // Chỉ thực thi khi đang chạy trong console
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Sử dụng Decorator Pattern để "bọc" ExceptionHandler gốc
        $this->app->extend(ExceptionHandler::class, function (ExceptionHandler $originalHandler, $app) {
            // Trả về một đối tượng handler mới, "bọc" lấy handler gốc
            return new class($originalHandler) implements ExceptionHandler {
                private $originalHandler;

                public function __construct(ExceptionHandler $originalHandler)
                {
                    $this->originalHandler = $originalHandler;
                }

                public function renderForConsole($output, Throwable $e): void
                {
                    if ($e instanceof VariableDebugGracefulExitException) {
                        // Đã xử lý, không làm gì thêm
                        return;
                    }
                    // Nếu không phải lỗi của mình, chuyển cho handler gốc xử lý
                    $this->originalHandler->renderForConsole($output, $e);
                }

                // Tất cả các phương thức khác đều được chuyển thẳng đến handler gốc
                public function report(Throwable $e): void
                {
                    $this->originalHandler->report($e);
                }

                public function shouldReport(Throwable $e): bool
                {
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