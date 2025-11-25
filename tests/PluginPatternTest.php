<?php
namespace lightla\VariableDebuggerTests;

use lightla\VariableDebugger\Adapters\VariableDebugClassPropertyPluginAdapter;
use lightla\VariableDebugger\Config\VariableDebugConfigurator;
use PHPUnit\Framework\TestCase;

class PluginPatternTest extends TestCase
{
    public function test_can_add_custom_plugin()
    {
        $plugin = new class implements VariableDebugClassPropertyPluginAdapter {
            public function applyTo(VariableDebugConfigurator $configurator): void
            {
                $configurator->withClassProperties('stdClass', ['custom_prop']);
            }
        };

        $configurator = new VariableDebugConfigurator();
        $result = $configurator->addClassPropertiesFromPlugin($plugin);

        $this->assertInstanceOf(VariableDebugConfigurator::class, $result);
    }

    public function test_can_chain_multiple_plugins()
    {
        $plugin1 = new class implements VariableDebugClassPropertyPluginAdapter {
            public function applyTo(VariableDebugConfigurator $configurator): void
            {
                $configurator->withClassProperties('stdClass', ['prop1']);
            }
        };

        $plugin2 = new class implements VariableDebugClassPropertyPluginAdapter {
            public function applyTo(VariableDebugConfigurator $configurator): void
            {
                $configurator->withClassProperties('ArrayObject', ['prop2']);
            }
        };

        $configurator = new VariableDebugConfigurator();
        $result = $configurator
            ->addClassPropertiesFromPlugin($plugin1)
            ->addClassPropertiesFromPlugin($plugin2);

        $this->assertInstanceOf(VariableDebugConfigurator::class, $result);
    }

    public function test_laravel_plugin_helper_method()
    {
        $configurator = new VariableDebugConfigurator();
        $result = $configurator->addClassPropertiesFromPluginLaravel();

        $this->assertInstanceOf(VariableDebugConfigurator::class, $result);
    }
}
