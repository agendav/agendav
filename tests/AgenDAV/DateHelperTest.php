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
        $str = '2012-10-07';
        $dt = DateHelper::fullcalendarToDateTime($str, $this->utc);

        $expected = new \DateTime('2012-10-07 00:00:00', $this->utc);

        $this->assertEquals($expected, $dt);
    }

    public function testDurationToDateInterval()
    {
        $d1 = DateHelper::durationToDateInterval('P1D');
        $this->assertEquals($d1->invert, 0);
        $d2 = DateHelper::durationToDateInterval('-P1D');
        $this->assertEquals($d2->invert, 1);
    }
}
