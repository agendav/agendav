<?php

namespace AgenDAV\Event;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use AgenDAV\Event\VObjectHelper;
use Mockery as m;

class VObjectHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $vcalendar;

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
    }

    public function testSetBaseVEventEmptyVCalendar()
    {
        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'Test event';
        $vevent->DTSTART = new \DateTime();

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }

    public function testSetBaseVEventSimpleVCalendar()
    {
        $this->vcalendar->add('VTIMEZONE', []);

        // The base VEVENT will be the second children
        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This vevent will disappear',
        ]);

        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'Test event';
        $vevent->DTSTART = new \DateTime();

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }

    public function testSetBaseVEventWithExceptions()
    {

        $this->addBaseEventAndExceptions();

        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'New base vevent';
        $vevent->DTSTART = '20150220T184900Z';
        $vevent->RRULE = 'FREQ=DAILY';

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }


    public function testFindExceptionVEvent()
    {
        $this->addBaseEventAndExceptions();

        $unexisting_exception = VObjectHelper::findExceptionVEvent(
            $this->vcalendar,
            '20150909T184900Z'
        );

        $this->assertNull($unexisting_exception);

        $existing_exception = VObjectHelper::findExceptionVEvent(
            $this->vcalendar,
            '20150227T184900Z'
        );

        $this->assertEquals(
            $existing_exception->SUMMARY,
            'This is the first exception'
        );
    }

    public function testSetExceptionVEvent()
    {
        $this->addBaseEventAndExceptions();

        $new_exception = $this->vcalendar->create('VEVENT');
        $new_exception->SUMMARY = 'New exception';
        $new_exception->{'RECURRENCE-ID'} = '20150302T184900Z';

        VObjectHelper::setExceptionVEvent($this->vcalendar, $new_exception);

        $this->assertEquals(
            VObjectHelper::findExceptionVEvent($this->vcalendar, '20150302T184900Z'),
            $new_exception,
            'VEVENT exceptions are not added'
        );

        // Add a new VEVENT using the same RECURRENCE-ID. The original one
        // should be replaced by this one
        $modified_exception = clone $new_exception;
        $modified_exception->SUMMARY = 'Modified new exception';

        VObjectHelper::setExceptionVEvent($this->vcalendar, $modified_exception);

        $this->assertEquals(
            VObjectHelper::findExceptionVEvent($this->vcalendar, '20150302T184900Z'),
            $modified_exception,
            'VEVENT exceptions are not replaced'
        );
    }

    /**
     * Internal function, used to add a base VEVENT and two exceptions
     * to the test VCALENDAR
     */
    protected function addBaseEventAndExceptions()
    {
        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This vevent will disappear',
            'DTSTART' => '20150220T184900Z',
            'RRULE' => 'FREQ=DAILY',
        ]);

        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This is the first exception',
            'RECURRENCE-ID' => '20150227T184900Z',
        ]);

        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This is the second exception',
            'RECURRENCE-ID' => '20150226T184900Z',
        ]);
    }
}
