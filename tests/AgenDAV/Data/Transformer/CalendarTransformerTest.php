<?php

namespace AgenDAV\Data\Transformer;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Principal;

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
        $owner = new Principal('/owner/principal');
        $this->calendar->setOwner($owner);

        $fractal = new Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Item($this->calendar, new CalendarTransformer($owner->getUrl()));
        $this->assertEquals(
            $fractal->createData($resource)->toArray(),
            [
                'url' => 'http://test.url',
                'calendar' => 'http://test.url',
                'displayname' => 'Test calendar',
                'color' => '#ff0000ff',
                'order' => 3,
                'ctag' => 'abcdefg',
                'is_shared' => false,
                'owner' => '/owner/principal',
                'is_owned' => true,
                'writable' => true,
                'shares' => [],
            ]
        );

    }

    public function testTransformBasicSharedNotOwner()
    {
        $owner = new Principal('/owner/principal');
        $this->calendar->setOwner($owner);

        $fractal = new Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Item($this->calendar, new CalendarTransformer('/other/principal'));
        $this->assertEquals(
            $fractal->createData($resource)->toArray(),
            [
                'url' => 'http://test.url',
                'calendar' => 'http://test.url',
                'displayname' => 'Test calendar',
                'color' => '#ff0000ff',
                'order' => 3,
                'ctag' => 'abcdefg',
                'is_shared' => true,
                'owner' => '/owner/principal',
                'is_owned' => false,
                'writable' => true,
                'shares' => [],
            ]
        );

    }
}
