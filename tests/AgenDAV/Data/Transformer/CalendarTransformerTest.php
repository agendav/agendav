<?php

namespace AgenDAV\Data\Transformer;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use AgenDAV\CalDAV\Resource\Calendar;

class CalendarTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransform()
    {
        $calendar = new Calendar(
            'http://test.url',
            [
                Calendar::DISPLAYNAME => 'Test calendar',
                Calendar::COLOR => '#ff0000ff',
                Calendar::ORDER => '3',
                Calendar::CTAG => 'abcdefg',
            ]
        );

        $fractal = new Manager();
        $fractal->setSerializer(new JsonApiSerializer());
        $resource = new Item($calendar, new CalendarTransformer, 'calendar');
        $this->assertEquals(
            $fractal->createData($resource)->toArray(),
            [
                'calendar' => [
                    [
                    'url' => 'http://test.url',
                    'calendar' => 'http://test.url',
                    'displayname' => 'Test calendar',
                    'color' => '#ff0000ff',
                    'order' => 3,
                    'ctag' => 'abcdefg',
                    ]
                ]
            ]
        );


    }
}
