<?php
namespace lightla\VariableDebuggerTests;

class VariableDebuggerTest extends \PHPUnit\Framework\TestCase
{
    public function test_handler_gracefully_exits()
    {
        $runner = __DIR__ . '/graceful_runner.php';

        $proc = proc_open("php " . escapeshellarg($runner), [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ], $pipes);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        $status = proc_get_status($proc);
        proc_close($proc);

        // no output
        $this->assertSame('', trim($stdout));
        $this->assertSame('', trim($stderr));

        // exit code = 0
        $this->assertSame(0, $status['exitcode']);
    }
}
