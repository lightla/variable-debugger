<?php

require __DIR__.'/../../vendor/autoload.php';

v_register_exception_handler();

http_response_code(500);

throw new \lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException();

echo 'This text is not display';