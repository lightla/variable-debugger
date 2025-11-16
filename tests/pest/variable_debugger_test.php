<?php

test('graceful exit2', function () {
    expect(true)->toBeTrue();
});

test('graceful exit', function () {
    $runner = __DIR__.'/../graceful_runner.php';

    $proc = proc_open("php $runner", [
        1 => ['pipe','w'],
        2 => ['pipe','w'],
    ], $pipes);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    proc_close($proc);

    expect(trim($stdout))->toBe('');
    expect(trim($stderr))->toBe('');
});