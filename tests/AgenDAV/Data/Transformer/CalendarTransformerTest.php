<?php

namespace AgenDAV\Data\Transformer;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Principal;
use PHPUnit\Framework\TestCase;

class CalendarTransformerTest extends TestCase
{
    /** @var AgenDAV\CalDAV\Resource\Calendar */
    private $calendar;

    public function setUp(): void
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
                'is_subscribed' => false,
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
                'is_subscribed' => false,
                'writable' => true,
                'shares' => [],
            ]
        );

    }

    /**
     * @dataProvider namedColors
     */
    public function testTransformNamedColor($name, $expected)
    {
        $calendar = new Calendar('http://test.url', [Calendar::COLOR => $name]);
        $calendar->setOwner(new Principal('/owner/principal'));

        $result = (new CalendarTransformer('/owner/principal'))->transform($calendar);

        $this->assertEquals($expected, $result['color']);
    }

    public static function namedColors()
    {
        return [
            '4 chars' => ['navy', '#000080'],
            '7 chars' => ['skyblue', '#87ceeb'],
            '9 chars' => ['limegreen', '#32cd32'],
        ];
    }
}
