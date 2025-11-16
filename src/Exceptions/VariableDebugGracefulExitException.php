<?php
namespace lightla\VariableDebugger\Exceptions;

class VariableDebugGracefulExitException extends \RuntimeException {
    public function __construct() {

        parent::__construct('VariableDebugGracefulExitException');
    }
}
