<?php

namespace lightla\VariableDebugger\Config;

use lightla\VariableDebugger\VariableDebugConfig;

class VariableDebugConfigGlobalPendingBuilder extends VariableDebugConfigurator
{
    use VariableDebugConfigBuilderBuildTrait;

    private static ?VariableDebugConfigGlobalPendingBuilder $instance = null;

    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Đồng bộ cấu hình vào hệ thống
     */
    public function sync(): void
    {
        $newConfig = $this->doBuild();
        $oldGlobal = VariableDebugConfig::getGlobalConfig();

        if ($oldGlobal) {
            VariableDebugConfig::setGlobalConfig($newConfig->merge($oldGlobal));
        } else {
            VariableDebugConfig::setGlobalConfig($newConfig);
        }
    }

    public function __destruct()
    {
        $this->sync();
    }
}
