<?php

namespace Elegant\Sanitizer\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    use SanitizesData;

    public function test_combine_filters()
    {
        $data = [
            'name' => '  HellO EverYboDy   ',
        ];
        $rules = [
            'name' => 'trim|capitalize',
        ];
        $data = $this->sanitize($data, $rules);

        $this->assertEquals('Hello Everybody', $data['name']);
    }

    public function test_input_unchanged_if_no_filter()
    {
        $data = [
            'name' => '  HellO EverYboDy   ',
        ];
        $rules = [
            'name' => '',
        ];
        $data = $this->sanitize($data, $rules);

        $this->assertEquals('  HellO EverYboDy   ', $data['name']);
    }

    public function test_array_filters()
    {
        $data = [
            'name' => '  HellO EverYboDy   ',
        ];
        $rules = [
            'name' => ['trim', 'capitalize'],
        ];
        $data = $this->sanitize($data, $rules);

        $this->assertEquals('Hello Everybody', $data['name']);
    }

    public function test_wildcard_filters()
    {
        $data = [
            'name' => [
                'first' => ' John ',
                'last'  => ' Doe ',
            ],
            'address' => [
                'street' => ' Some street ',
                'city'   => ' New York ',
            ],
        ];
        $rules = [
            'name.*' => 'trim',
            'address.city' => 'trim',
        ];
        $data = $this->sanitize($data, $rules);

        $sanitized = [
            'name' => ['first' => 'John', 'last' => 'Doe'],
            'address' => ['street' => ' Some street ', 'city' => 'New York'],
        ];

        $this->assertEquals($sanitized, $data);
    }

    public function test_throws_exception_if_non_existing_filter()
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [
            'name' => '  HellO EverYboDy   ',
        ];
        $rules = [
            'name' => 'non-filter',
        ];
        $data = $this->sanitize($data, $rules);
    }

    public function test_should_only_sanitize_passed_data()
    {
        $data = [
            'title' => ' Hello WoRlD '
        ];
        $rules = [
            'title' => 'trim',
            'name' => 'trim|escape'
        ];
        $data = $this->sanitize($data, $rules);

        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals(1, count($data));
    }

    public function test_closure_rule()
    {
        $data = [
            'name' => ' Sina '
        ];
        $rules = [
            'name' => ['trim', fn($value) => strtoupper($value)]
        ];
        $data = $this->sanitize($data, $rules);

        $this->assertEquals('SINA', $data['name']);
    }

    public function test_removed_array_elements_are_persistent()
    {
        $actual = null;

        $data = [
            'users' => [
                ['name' => 'Mohammad', 'age' => 32],
                ['name' => 'Ali', 'age' => 25]
            ]
        ];
        $rules = [
            'users' => [function ($value) {
                unset($value[0]);
                return $value;
            }],
            'users.*.age' => [function ($value) use (&$actual) {
                $actual[] = $value;
                return $value;
            }]
        ];
        $data = $this->sanitize($data, $rules);

        $sanitized = [
            'users' => [
                1 => ['name' => 'Ali', 'age' => 25]
            ]
        ];

        $this->assertEquals(['25'], $actual);
        $this->assertEquals($sanitized, $data);
    }
}
