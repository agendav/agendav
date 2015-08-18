<?php

namespace AgenDAV\Data\Transformer;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use AgenDAV\CalDAV\Resource\Calendar;

class CalendarTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AgenDAV\CalDAV\Resource\Calendar */
    private $calendar;

    public function setUp()
    {
        $this->calendar = new Calendar(
            'http://test.url',
            [
            Calendar::DISPLAYNAME => 'Test calendar',
            Calendar::COLOR => '#ff0000ff',
            Calendar::ORDER => '3',
            Calendar::CTAG => 'abcdefg',
            ]
        );
    }

    public function testTransformBasicNotSharedOwned()
    {
        $owner = '/owner/principal';
        $this->calendar->setOwner($owner);

        $fractal = new Manager();
        $fractal->setSerializer(new JsonApiSerializer());
        $resource = new Item($this->calendar, new CalendarTransformer($owner), 'calendar');
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
                    'is_shared' => false,
                    'is_owned' => true,
                    'writable' => true,
                    'shares' => [],
                    ]
                ]
            ]
        );

    }

    public function testTransformBasicSharedNotOwner()
    {
        $owner = '/owner/principal';
        $this->calendar->setOwner($owner);
        $this->calendar->setShared(true);

        $fractal = new Manager();
        $fractal->setSerializer(new JsonApiSerializer());
        $resource = new Item($this->calendar, new CalendarTransformer('/other/principal'), 'calendar');
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
                    'is_shared' => true,
                    'is_owned' => false,
                    'writable' => true,
                    'shares' => [],
                    ]
                ]
            ]
        );

    }
}
