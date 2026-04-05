<?php

namespace lightla\VariableDebugger\Tests;

use PHPUnit\Framework\TestCase;
use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugger;
use lightla\VariableDebugger\Exceptions\VariableDebugGracefulExitException;

class ConfigurationLogicTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset global config before each test
        $reflection = new \ReflectionClass(VariableDebugConfig::class);
        $property = $reflection->getProperty('globalConfig');
        $property->setAccessible(true);
        $property->setValue(null, null); // Pass null as first arg for static property
        
        // Clear the singleton instance of the builder
        $builderReflection = new \ReflectionClass(\lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::class);
        $instanceProperty = $builderReflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null); // Pass null as first arg for static property
    }

    public function test_additive_properties_rules()
    {
        v_gl_config()->withProperties(['id']);
        v_gl_config()->withProperties(['name']);
        
        // Trigger sync
        \lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();
        
        $config = VariableDebugConfig::getGlobalConfig();
        $properties = $config->getIncludedProperties();
        
        $this->assertArrayHasKey('id', $properties);
        $this->assertArrayHasKey('name', $properties);
    }

    public function test_master_disable_switch()
    {
        v_gl_config()->disable();
        \lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();
        
        $config = VariableDebugConfig::getGlobalConfig();
        
        $this->assertFalse($config->resolveAllowPrintOrDefault());
        $this->assertFalse($config->resolveAllowExitOrDefault());
    }

    public function test_independent_print_and_exit_logic()
    {
        // Case 1: Disable Print but Enable Exit (v_dd should NOT print but SHOULD exit)
        v_gl_config()->disablePrint()->enableExit();
        \lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();
        
        $config = VariableDebugConfig::getGlobalConfig();
        $this->assertFalse($config->resolveAllowPrintOrDefault(), "Print should be disabled");
        $this->assertTrue($config->resolveAllowExitOrDefault(), "Exit should be enabled");

        // Use THROW_EXCEPTION mode for testing
        v_gl_config()->useTerminationThrowException();
        \lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();
        
        $config = VariableDebugConfig::getGlobalConfig();
        $debugger = new VariableDebugger($config);
        
        $this->expectException(VariableDebugGracefulExitException::class);
        $debugger->exit();
    }

    public function test_disable_exit_logic()
    {
        // Case 2: Enable Print but Disable Exit (v_dd should print but should NOT exit)
        v_gl_config()->enablePrint()->disableExit();
        \lightla\VariableDebugger\Config\VariableDebugConfigGlobalPendingBuilder::getInstance()->sync();
        
        $config = VariableDebugConfig::getGlobalConfig();
        $this->assertTrue($config->resolveAllowPrintOrDefault(), "Print should be enabled");
        $this->assertFalse($config->resolveAllowExitOrDefault(), "Exit should be disabled");

        $debugger = new VariableDebugger($config);
        
        // Should return silently instead of exiting
        $debugger->exit();
        
        $this->assertTrue(true); // If it reaches here, it didn't exit
    }
}
