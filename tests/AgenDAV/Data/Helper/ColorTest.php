<?php

namespace AgenDAV\Data\Helper;

use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    /**
     * @dataProvider colors
     */
    public function testToHex($input, $expected)
    {
        $this->assertSame($expected, Color::toHex($input));
    }

    public static function colors()
    {
        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'name' => ['limegreen', '#32cd32'],
            'mixed case name' => ['LightBlue', '#add8e6'],
            'name with spaces' => [' grey ', '#808080'],
            'gray alias' => ['gray', '#808080'],
            'hex' => ['#FF0000', '#FF0000'],
            'hex without #' => ['03A9F4', '#03A9F4'],
            'hex with alpha' => ['#ff0000ff', '#ff0000ff'],
            'unknown name' => ['transparent', '#transparent'],
        ];
    }
}
