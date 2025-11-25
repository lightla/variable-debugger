<?php
namespace lightla\VariableDebuggerTests;

use lightla\VariableDebugger\Config\VariableDebugConfigurator;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

    public function test_max_depth_limits_nesting()
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep'
                    ]
                ]
            ]
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->maxDepth(2);
        });

        $this->assertStringContainsString('[Max Depth Reached]', $output);
    }

    public function test_show_array_first_element_only()
    {
        $data = ['a', 'b', 'c', 'd', 'e'];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->presetCompact(null, true);
        });

        $this->assertStringContainsString('and', $output);
        $this->assertStringContainsString('others', $output);
    }

    public function test_show_value_type()
    {
        $data = ['string' => 'test', 'int' => 123, 'bool' => true];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->showValueType(true);
        });

        $this->assertStringContainsString('array', $output);
        $this->assertStringContainsString('string', $output);
    }

    public function test_config_short_preset()
    {
        $data = ['test' => 'value'];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->presetCompact();
        });

        $this->assertNotEmpty($output);
    }

    public function test_config_full_preset()
    {
        $data = ['test' => 'value'];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->presetDetailed();
        });

        $this->assertNotEmpty($output);
    }

    public function test_with_properties_method()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['a', 'b']);
        });

        $this->assertStringContainsString('"a"', $output);
        $this->assertStringContainsString('"b"', $output);
        $this->assertStringNotContainsString('"c"', $output);
    }

    public function test_without_properties_method()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withoutProperties(['b']);
        });

        $this->assertStringContainsString('"a"', $output);
        $this->assertStringContainsString('"c"', $output);
        $this->assertStringNotContainsString('"b"', $output);
    }

    public function test_method_chaining()
    {
        $data = ['test' => 'value'];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)
                ->maxDepth(5)
                ->showValueType(true)
                ->withProperties(['test']);
        });

        $this->assertStringContainsString('"test"', $output);
    }

    public function test_cli_theme_methods()
    {
        $configurator = new VariableDebugConfigurator();
        
        $result1 = $configurator->useCliThemeDark();
        $this->assertInstanceOf(VariableDebugConfigurator::class, $result1);
        
        $result2 = $configurator->useCliThemeLight();
        $this->assertInstanceOf(VariableDebugConfigurator::class, $result2);
        
        $result3 = $configurator->useCliThemeNoColor();
        $this->assertInstanceOf(VariableDebugConfigurator::class, $result3);
    }
}
