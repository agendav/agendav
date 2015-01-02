<?php
namespace AgenDAV;

class DateHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * UTC timezone 
     * 
     * @var mixed
     * @access private
     */
    private $utc;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateDateTimeFail()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '99/99/99 99:99', $this->utc);
    }

    public function testCreateDateTimeSampleZero()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/7/2012 10:00', $this->utc);
        $dt2 = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $this->utc);

        $this->assertEquals($dt, $dt2);
    }

    public function testCreateDateTimeTZ()
    {
        $different_tz = new \DateTimeZone('Europe/Madrid');
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $different_tz);

        $this->assertEquals($dt->getTimeZone(), $different_tz);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testApproximateBadDt()
    {
        $tmp = DateHelper::approximate('notadatetime');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testApproximateBadFactor()
    {
        $tmp = DateHelper::approximate(new \DateTime(), 'xxx');
    }

    public function testApproximateExample1()
    {
        $dt = new \DateTime();
        $tmp = DateHelper::approximate($dt, 1);

        $this->assertEquals($dt, $tmp);
    }

    public function testApproximateExample2()
    {
        $dt = new \DateTime('now', $this->utc);
        $dt->setTimestamp(1);

        // First example
        $tmp = DateHelper::approximate($dt, 60);
        $this->assertTrue($tmp->getTimestamp() == 0);

        // Second one
        $dt->setTimestamp(2);
        $tmp2 = DateHelper::approximate($dt, 3);
        $this->assertTrue($tmp2->getTimestamp() == 3);
    }

    public function testFrontendToDatetimeExample()
    {
        $str = '2014-12-15T19:45:00.000Z';

        $dt = DateHelper::frontendToDateTime($str, $this->utc);
        $this->assertEquals('201412151945', $dt->format('YmdHi'));

        // No timezone specified. Should use UTC
        $dt = DateHelper::frontendToDateTime($str);
        $this->assertEquals('201412151945', $dt->format('YmdHi'));

        $dt = DateHelper::frontendToDateTime($str, new \DateTimeZone('Europe/Madrid'));
        $this->assertEquals('201412152045', $dt->format('YmdHi'));
    }


    public function testFullcalendarToDateTime()
    {
        $str = '20121007100000';
        $dt = DateHelper::fullcalendarToDateTime($str, $this->utc);

        $this->assertEquals($dt->format('YmdHis'), '20121007100000');
    }

    public function testDateTimeToiCalendar1()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $this->utc);

        $this->assertEquals(
            DateHelper::dateTimeToiCalendar($dt, 'DATE-TIME'),
            '20121007T100000Z'
        );
    }

    public function testDateTimeToiCalendar2()
    {
        // TZ != UTC
        $tz = new \DateTimeZone('Europe/Madrid');
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $tz);

        $this->assertEquals(
            DateHelper::dateTimeToiCalendar($dt, 'DATE-TIME'),
            '20121007T100000'
        );
    }

    public function testDateTimeToiCalendar3()
    {
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $this->utc);

        $this->assertEquals(
            DateHelper::dateTimeToiCalendar($dt, 'DATE'),
            '20121007'
        );
    }

    public function testDateTimeToiCalendar4()
    {
        // TZ != UTC
        $tz = new \DateTimeZone('Europe/Madrid');
        $dt = DateHelper::createDateTime('m/d/Y H:i', '10/07/2012 10:00', $tz);

        $this->assertEquals(
            DateHelper::dateTimeToiCalendar($dt, 'DATE'),
            '20121007'
        );
    }

    public function testiCalCreatorToDateTime1()
    {
        $sample = array(
            'year' => '2012',
            'month' => '10',
            'day' => '07',
            'hour' => '23',
            'min' => '00',
            'sec' => '00',
            'tz' => 'Europe/Madrid',
        );

        $dt = DateHelper::iCalcreatorToDateTime($sample, new \DateTimeZone('Europe/Madrid'));

        $this->assertEquals($dt->format('YmdHis'), '20121007230000');
    }

    public function testiCalCreatorToDateTime2()
    {
        $sample = array(
            'year' => '2012',
            'month' => '10',
            'day' => '07',
            'tz' => 'Europe/Madrid',
        );

        $dt = DateHelper::iCalcreatorToDateTime($sample, new \DateTimeZone('Europe/Madrid'));

        $this->assertEquals($dt->format('YmdHis'), '20121007000000');
    }

    public function testDurationToDateInterval()
    {
        $d1 = DateHelper::durationToDateInterval('P1D');
        $this->assertEquals($d1->invert, 0);
        $d2 = DateHelper::durationToDateInterval('-P1D');
        $this->assertEquals($d2->invert, 1);
    }

    public function testiCalcreatorXCurrentToDateTime()
    {
        $example1 = '2012-10-08';
        $res1 = DateHelper::iCalcreatorXCurrentToDateTime($example1, $this->utc);
        $this->assertEquals($res1, DateHelper::createDateTime('YmdHis', '20121008000000', $this->utc));

        $example2 = '2012-10-08 10:11:12 CEST';
        $res2 = DateHelper::iCalcreatorXCurrentToDateTime($example2, $this->utc);
        $this->assertEquals($res2, DateHelper::createDateTime('YmdHis', '20121008101112', $this->utc));
    }

    public function testformatTime()
    {
        $dt = DateHelper::createDateTime('YmdHis', '20121008200000', $this->utc);
        $this->assertEquals(DateHelper::formatTime($dt, '24'), '20:00');
        $this->assertEquals(DateHelper::formatTime($dt, '12'), '08:00 pm');
    }
}
