<?php
namespace lightla\VariableDebuggerTests;

use lightla\VariableDebugger\VariableDebugger;
use PHPUnit\Framework\TestCase;

class PropertyFilterTest extends TestCase
{
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

    public function test_include_properties_filters_array_keys()
    {
        $data = ['x' => 1, 'y' => 2, 'z' => 3];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['x', 'z']);
        });

        $this->assertStringContainsString('"x"', $output);
        $this->assertStringContainsString('"z"', $output);
        $this->assertStringNotContainsString('"y"', $output);
    }

    public function test_exclude_properties_filters_array_keys()
    {
        $data = ['x' => 1, 'y' => 2, 'z' => 3];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withoutProperties(['y']);
        });

        $this->assertStringContainsString('"x"', $output);
        $this->assertStringContainsString('"z"', $output);
        $this->assertStringNotContainsString('"y"', $output);
    }

    public function test_nested_array_property_filtering()
    {
        $data = [
            'x' => ['tmp1' => 1, 'tmp2' => 2],
            'y' => ['g1', 'g2'],
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['x.tmp1']);
        });

        $this->assertStringContainsString('"x"', $output);
        $this->assertStringContainsString('"tmp1"', $output);
        $this->assertStringNotContainsString('"tmp2"', $output);
        $this->assertStringNotContainsString('"y"', $output);
    }

    public function test_object_property_filtering()
    {
        $obj = new class {
            public $name = 'Test';
            public $email = 'test@example.com';
            public $password = 'secret';
        };
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj)->withProperties(['name', 'email']);
        });

        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('email', $output);
        $this->assertStringNotContainsString('password', $output);
    }

    public function test_nested_object_in_array_filtering()
    {
        $obj = new class {
            public $id = 1;
            public $name = 'Test';
            public $secret = 'hidden';
        };
        
        $data = [
            'user' => $obj,
            'meta' => ['key' => 'value']
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['user.name']);
        });

        $this->assertStringContainsString('"user"', $output);
        $this->assertStringContainsString('name', $output);
        $this->assertStringNotContainsString('secret', $output);
        $this->assertStringNotContainsString('"meta"', $output);
    }

    public function test_show_key_only_for_arrays()
    {
        $data = ['email' => 'test@example.com', 'password' => 'secret'];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->showKeyOnly(true);
        });

        $this->assertStringContainsString('"email"', $output);
        $this->assertStringContainsString('[hidden]', $output);
        $this->assertStringNotContainsString('test@example.com', $output);
    }

    public function test_show_key_only_with_ignored_paths()
    {
        $data = [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret'
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->showKeyOnly(true, ['name']);
        });

        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('[hidden]', $output);
        $this->assertStringNotContainsString('john@example.com', $output);
    }

    public function test_excluded_count_shown_for_arrays()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['a', 'b']);
        });

        $this->assertStringContainsString('[2 excluded]', $output);
    }

    public function test_excluded_count_shown_for_objects()
    {
        $obj = new class {
            public $a = 1;
            public $b = 2;
            public $c = 3;
            public $d = 4;
        };
        
        $output = $this->captureOutput(function() use ($obj) {
            v_dump($obj)->withProperties(['a', 'b']);
        });

        $this->assertStringContainsString('[2 excluded]', $output);
    }

    public function test_no_excluded_count_when_all_shown()
    {
        $data = ['a' => 1, 'b' => 2];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data);
        });

        $this->assertStringNotContainsString('excluded', $output);
    }

    public function test_complex_nested_filtering()
    {
        $data = [
            'x' => ['tmp1' => 1, 'tmp2' => 2, 'tmp3' => 3],
            'y' => ['g1', 'g2'],
            'z' => new class {
                public $id = 1;
                public $name = 'Test';
                public $email = 'test@example.com';
            }
        ];
        
        $output = $this->captureOutput(function() use ($data) {
            v_dump($data)->withProperties(['x.tmp1', 'z.name']);
        });

        $this->assertStringContainsString('"x"', $output);
        $this->assertStringContainsString('"tmp1"', $output);
        $this->assertStringContainsString('"z"', $output);
        $this->assertStringContainsString('name', $output);
        $this->assertStringNotContainsString('"y"', $output);
        $this->assertStringNotContainsString('email', $output);
    }
}
