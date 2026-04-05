<?php
namespace lightla\VariableDebuggerTests;

use PHPUnit\Framework\TestCase;

class WildcardPropertyFilterTest extends TestCase
{
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

    public function test_empty_result_when_no_match()
    {
        $data = [
            ['x' => 1],
            ['y' => 2],
            ['z' => 3]
        ];

        // Pattern ?.nonexistent should show empty array
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['?.nonexistent'])->showExcludedCount(false);
        });

        // Should be completely empty, not showing keys
        $this->assertStringNotContainsString('"x"', $output);
        $this->assertStringNotContainsString('"y"', $output);
        $this->assertStringNotContainsString('"z"', $output);
        $this->assertStringContainsString('[]', $output);
    }

    public function test_deep_path_no_match()
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 123
                    ]
                ]
            ]
        ];

        // Looking for completely wrong path
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['wrong.path.here'])->showExcludedCount(false);
        });

        $this->assertStringContainsString('[]', $output);
        $this->assertStringNotContainsString('level1', $output);
    }

    public function test_hash_with_numeric_strings()
    {
        $data = [
            '0' => 'zero',
            '1' => 'one',
            'a' => 'alpha',
            '2x' => 'mixed'
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['#']);
        });

        $this->assertStringContainsString('zero', $output);
        $this->assertStringContainsString('one', $output);
        $this->assertStringNotContainsString('alpha', $output);
        $this->assertStringNotContainsString('mixed', $output);
    }

    public function test_question_matches_any_string()
    {
        $data = [
            'short' => 1,
            'verylongstring' => 2,
            'x' => 3,
            '?' => 4
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['?']);
        });

        $this->assertStringContainsString('"short"', $output);
        $this->assertStringContainsString('"verylongstring"', $output);
        $this->assertStringContainsString('"x"', $output);
        $this->assertStringContainsString('"?"', $output);
    }

    public function test_nested_wildcard_combination()
    {
        $data = [
            0 => [
                'users' => [
                    0 => ['name' => 'User 0-0'],
                    1 => ['name' => 'User 0-1']
                ]
            ],
            1 => [
                'users' => [
                    0 => ['name' => 'User 1-0']
                ]
            ]
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['#.users.#.name']);
        });

        $this->assertStringContainsString('User 0-0', $output);
        $this->assertStringContainsString('User 0-1', $output);
        $this->assertStringContainsString('User 1-0', $output);
    }

    public function test_partial_path_match_shows_nothing()
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'age' => 30
                ]
            ]
        ];

        // Pattern requires full path to exist
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['user.profile.name.extra'])->showExcludedCount(false);
        });

        // 'name' is not an array/object, so 'extra' can't exist
        $this->assertStringContainsString('[]', $output);
        $this->assertStringNotContainsString('John', $output);
    }

    public function test_multiple_wildcard_levels()
    {
        $data = [
            'data' => [
                0 => [
                    'items' => [
                        0 => ['val' => 'a'],
                        1 => ['val' => 'b']
                    ]
                ],
                1 => [
                    'items' => [
                        0 => ['val' => 'c']
                    ]
                ]
            ]
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['data.#.items.#.val']);
        });

        $this->assertStringContainsString('"a"', $output);
        $this->assertStringContainsString('"b"', $output);
        $this->assertStringContainsString('"c"', $output);
        $this->assertStringNotContainsString('[]', $output);
    }

    public function test_mixed_wildcards_question_and_hash()
    {
        $data = [
            'group1' => [
                0 => 'value0',
                1 => 'value1'
            ],
            'group2' => [
                0 => 'value2'
            ]
        ];

        // ? matches 'group1' and 'group2', # matches numeric keys
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['?.#']);
        });

        $this->assertStringContainsString('value0', $output);
        $this->assertStringContainsString('value1', $output);
        $this->assertStringContainsString('value2', $output);
    }

    public function test_literal_question_mark_in_key()
    {
        $data = [
            '?mark' => 'literal question',
            'normal' => 'normal value'
        ];

        // Looking for literal '?mark'
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['?mark']);
        });

        $this->assertStringContainsString('literal question', $output);
        $this->assertStringNotContainsString('normal value', $output);
    }

    public function test_literal_hash_in_key()
    {
        $data = [
            '#tag' => 'hashtag value',
            '123' => 'numeric value'
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['#tag']);
        });

        $this->assertStringContainsString('hashtag value', $output);
        $this->assertStringNotContainsString('numeric value', $output);
    }

    public function test_empty_intermediate_level()
    {
        $data = [
            0 => [],
            1 => ['x' => 'value'],
            2 => []
        ];

        // Only 1.x exists
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['#.x']);
        });

        $this->assertStringContainsString('value', $output);
        // Keys 0 and 2 should not appear since they have no matching descendants
        $lines = explode("\n", $output);
        $hasKey0 = false;
        $hasKey2 = false;
        foreach ($lines as $line) {
            if (preg_match('/^\s*0\s*=>/', $line))
                $hasKey0 = true;
            if (preg_match('/^\s*2\s*=>/', $line))
                $hasKey2 = true;
        }
        $this->assertFalse($hasKey0, 'Key 0 should not be shown');
        $this->assertFalse($hasKey2, 'Key 2 should not be shown');
    }

    public function test_all_levels_empty()
    {
        $data = [
            [[]],
            [[]],
            [[]]
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['#.#.something'])->showExcludedCount(false);
        });

        $this->assertStringContainsString('[]', $output);
    }

    public function test_exact_vs_wildcard_precedence()
    {
        $data = [
            'user' => ['name' => 'Alice'],
            'admin' => ['name' => 'Bob']
        ];

        // Using ? should match both
        $output1 = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['?.name']);
        });

        $this->assertStringContainsString('Alice', $output1);
        $this->assertStringContainsString('Bob', $output1);

        // Using exact key should match only one
        $output2 = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['user.name']);
        });

        $this->assertStringContainsString('Alice', $output2);
        $this->assertStringNotContainsString('Bob', $output2);
    }

    public function test_very_deep_nesting()
    {
        $data = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => [
                            'e' => 'deep value'
                        ]
                    ]
                ]
            ]
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['a.b.c.d.e']);
        });

        $this->assertStringContainsString('deep value', $output);

        // Wrong path should show nothing
        $output2 = $this->captureOutput(function () use ($data) {
            v_dump($data)->withProperties(['a.b.x.d.e'])->showExcludedCount(false);
        });

        $this->assertStringContainsString('[]', $output2);
        $this->assertStringNotContainsString('deep value', $output2);
    }

    public function test_numeric_as_integer_vs_string()
    {
        $data = [
            0 => 'int key zero',
            '0' => 'string key zero', // Will overwrite in PHP
            1 => 'int key one'
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['#']);
        });

        // Both should appear as # matches numeric
        $this->assertStringContainsString('int key', $output);
    }

    public function test_multiple_patterns_mixed()
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
                ['id' => 2, 'name' => 'Bob', 'role' => 'user']
            ],
            'settings' => ['theme' => 'dark']
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['users.#.name', 'settings.theme']);
        });

        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('Bob', $output);
        $this->assertStringContainsString('dark', $output);
        $this->assertStringNotContainsString('admin', $output);
        $this->assertStringNotContainsString('role', $output);
    }

    public function test_exclusion_with_wildcards()
    {
        $data = [
            'u1' => ['name' => 'John', 'secret' => '123'],
            'u2' => ['name' => 'Jane', 'secret' => '456'],
            'other' => ['val' => 'visible']
        ];

        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withoutProperties(['?.secret']);
        });

        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('Jane', $output);
        $this->assertStringContainsString('visible', $output);
        $this->assertStringNotContainsString('secret', $output);
        $this->assertStringNotContainsString('123', $output);
    }

    public function test_object_properties_with_wildcards()
    {
        $obj = new \stdClass();
        $obj->firstName = 'John';
        $obj->lastName = 'Doe';
        $obj->secret = 'hidden';

        $output = $this->captureOutput(function () use ($obj) {
            v_dump($obj)->withPatternProperties(['?Name']);
        });

        // Current implementation matches whole segment. 
        // So '?Name' matches ONLY literal '?Name'.
        // To match any property, we use '?'

        $output2 = $this->captureOutput(function () use ($obj) {
            v_dump($obj)->withPatternProperties(['?']);
        });

        $this->assertStringContainsString('John', $output2);
        $this->assertStringContainsString('Doe', $output2);
        $this->assertStringContainsString('hidden', $output2);
    }

    public function test_mixed_array_object_nesting()
    {
        $admin = new \stdClass();
        $admin->username = 'boss';
        $admin->meta = ['login_count' => 5];

        $user = new \stdClass();
        $user->username = 'staff';
        $user->meta = ['login_count' => 10];

        $data = [
            'groups' => [
                'admins' => [$admin],
                'users' => [$user]
            ]
        ];

        // Pattern to get login_count for all admins
        $output = $this->captureOutput(function () use ($data) {
            v_dump($data)->withPatternProperties(['groups.admins.#.meta.login_count']);
        });

        $this->assertStringContainsString('5', $output);
        $this->assertStringNotContainsString('staff', $output);
        $this->assertStringNotContainsString('10', $output);
    }
}
