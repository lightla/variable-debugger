<?php
namespace lightla\VariableDebuggerTests;

use PHPUnit\Framework\TestCase;

class EdgeCaseTest extends TestCase
{
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

    public function test_empty_array()
    {
        $output = $this->captureOutput(function() {
            v_dump([]);
        });

        $this->assertStringContainsString('[]', $output);
    }

    public function test_null_value()
    {
        $output = $this->captureOutput(function() {
            v_dump(null);
        });

        $this->assertStringContainsString('null', $output);
    }

    public function test_boolean_values()
    {
        $output = $this->captureOutput(function() {
            v_dump([true, false]);
        });

        $this->assertStringContainsString('true', $output);
        $this->assertStringContainsString('false', $output);
    }

    public function test_numeric_values()
    {
        $output = $this->captureOutput(function() {
            v_dump([123, 45.67, -89, 0]);
        });

        $this->assertStringContainsString('123', $output);
        $this->assertStringContainsString('45.67', $output);
    }

    public function test_special_characters_in_strings()
    {
        $output = $this->captureOutput(function() {
            v_dump(['test' => "Line1\nLine2\tTab"]);
        });

        $this->assertNotEmpty($output);
    }

    public function test_circular_reference_protection()
    {
        $obj = new \stdClass();
        $obj->self = $obj;
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj)->maxDepth(3);
        });

        $this->assertStringContainsString('[Max Depth Reached]', $output);
    }

    public function test_empty_object()
    {
        $obj = new \stdClass();
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj);
        });

        $this->assertStringContainsString('stdClass', $output);
    }

    public function test_object_with_private_properties()
    {
        $obj = new class {
            private $private = 'secret';
            protected $protected = 'hidden';
            public $public = 'visible';
        };
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj);
        });

        $this->assertStringContainsString('private', $output);
        $this->assertStringContainsString('protected', $output);
        $this->assertStringContainsString('public', $output);
    }

    public function test_uninitialized_property()
    {
        $obj = new class {
            public string $initialized = 'value';
            public string $uninitialized;
        };
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj);
        });

        $this->assertStringContainsString('[uninitialized]', $output);
    }

    public function test_nested_empty_structures()
    {
        $data = [
            'empty_array' => [],
            'empty_object' => new \stdClass(),
            'nested' => [
                'also_empty' => []
            ]
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data);
        });

        $this->assertStringContainsString('[]', $output);
    }

    public function test_mixed_numeric_and_string_keys()
    {
        $data = [
            0 => 'zero',
            'one' => 1,
            2 => 'two',
            'three' => 3
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data);
        });

        $this->assertStringContainsString('0', $output);
        $this->assertStringContainsString('"one"', $output);
    }

    public function test_filter_non_existent_property()
    {
        $data = ['a' => 1, 'b' => 2];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->includeProperties(['c', 'd']);
        });

        $this->assertStringNotContainsString('"a"', $output);
        $this->assertStringNotContainsString('"b"', $output);
    }

    public function test_exclude_all_properties()
    {
        $data = ['a' => 1, 'b' => 2];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->excludeProperties(['a', 'b']);
        });

        $this->assertStringContainsString('excluded', $output);
    }

    public function test_very_long_string()
    {
        $longString = str_repeat('a', 1000);
        
        $output = $this->captureOutput(function() use ($longString) {
            v_dump(['long' => $longString]);
        });

        $this->assertNotEmpty($output);
    }

    public function test_deeply_nested_path()
    {
        $data = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 'value'
                    ]
                ]
            ]
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->includeProperties(['a.b.c.d']);
        });

        $this->assertStringContainsString('"a"', $output);
        $this->assertStringContainsString('"d"', $output);
    }
}
