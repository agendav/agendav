<?php
namespace AgenDAV;

use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    /**
     * UTC timezone, cached
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

        $expected = new \DateTimeImmutable('2012-10-07 00:00:00', $this->utc);

        $this->assertEquals($expected, $dt);


        $str = '2023-06-05T00:00:00';
        $dt = DateHelper::fullcalendarToDateTime($str, $this->utc);

        $expected = new \DateTimeImmutable('2023-06-05 00:00:00', $this->utc);

        $this->assertEquals($expected, $dt);

    }

    public function testAddMinutesTo()
    {
        $start = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));

        $expected_1 = $start->modify('+10 minutes');
        $expected_2 = $start->modify('-10 minutes');

        $result_1 = DateHelper::addMinutesTo($start, '10');
        $result_2 = DateHelper::addMinutesTo($start, '-10');

        $this->assertEquals($expected_1, $result_1, 'Adding minutes does not work');
        $this->assertEquals($expected_2, $result_2, 'Subtracting minutes does not work');
    }

    public function testSwitchTimeZone()
    {
        $datetime = new \DateTimeImmutable('2015-01-13 00:00:00', new \DateTimeZone('UTC'));

        $converted = DateHelper::switchTimeZone(
            $datetime,
            new \DateTimeZone('America/New_York')
        );

        $this->assertEquals('2015-01-13 00:00:00', $converted->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $converted->getTimeZone()->getName());
    }

    public function testGetStartOfDayUTC()
    {
        $datetime = new \DateTimeImmutable('2015-01-27 12:03:19', new \DateTimeZone('Europe/London'));

        $start_of_day = DateHelper::getStartOfDayUTC($datetime);

        $this->assertEquals('2015-01-27 00:00:00', $start_of_day->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start_of_day->getTimeZone()->getName());
    }
}
