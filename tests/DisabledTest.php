<?php
namespace lightla\VariableDebuggerTests;

use lightla\VariableDebugger\VariableDebugConfig;
use lightla\VariableDebugger\VariableDebugger;
use PHPUnit\Framework\TestCase;

class DisabledTest extends TestCase
{
    public function test_disabled_config_prevents_printing()
    {
        $config = VariableDebugConfig::builder()
            ->disable()
            ->build();
        
        $this->assertFalse($config->resolveAllowPrintOrDefault());
        
        $debugger = new VariableDebugger($config);
        
        ob_start();
        $debugger->dump('test');
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }

    public function test_enabled_config_allows_printing()
    {
        $config = VariableDebugConfig::builder()
            ->enable()
            ->build();
        
        $this->assertTrue($config->resolveAllowPrintOrDefault());
        
        $debugger = new VariableDebugger($config);
        
        ob_start();
        $debugger->dump('test');
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }

    public function test_disabled_on_config_instance()
    {
        $config = new VariableDebugConfig();
        $disabledConfig = $config->disable();
        
        $this->assertFalse($disabledConfig->getAllowPrint());
        $this->assertNull($config->getAllowPrint());
    }

    public function test_v_dump_disabled()
    {
        v_gl_config()->disable();

        ob_start();
        v_dump('test');
        $output = ob_get_clean();
        
        $this->assertEmpty($output);

        v_gl_config()->enable();
    }

    public function test_v_die_disabled_does_not_exit()
    {
        // We use a custom termination mode to avoid actual exit in PHPUnit
        $config = VariableDebugConfig::builder()
            ->disable()
            ->useTerminationExitSuccess() // even with this, it should not exit because it's disabled
            ->build();
        
        $debugger = new VariableDebugger($config);
        
        // This should not throw and not exit
        $debugger->exit();
        
        $this->assertTrue(true); // If we reach here, it didn't exit
    }
}
