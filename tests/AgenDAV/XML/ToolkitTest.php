<?php
namespace AgenDAV\XML;

use AgenDAV\CalDAV\Resource\Calendar;
use \Mockery as m;

class ToolkitTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    protected $generator;

    /*
     * Used to create bodies
     */
    public static $property_list = [
        'prop1',
        'prop2',
        'prop3',
    ];

    public function setUp()
    {
        $this->parser = m::mock('\AgenDAV\XML\Parser');
        $this->generator = m::mock('\AgenDAV\XML\Generator');
    }

    public function testFacadeParseMultiStatus()
    {
        $this->parser
            ->shouldReceive('extractPropertiesFromMultistatus')
            ->once()
            ->with('body', false)
            ->andReturn(['result']);

        $toolkit = $this->createToolkit();

        $this->assertEquals(
            ['result'],
            $toolkit->parseMultistatus('body', false)
        );

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateBodyUnsupportedRequest()
    {
        $toolkit = $this->createToolkit();
        $toolkit->generateRequestBody('INVALID', 'test');
    }

    public function testGenerateMkCalendarBody()
    {
        $this->generator
            ->shouldReceive('mkCalendarBody')
            ->once()
            ->with(self::$property_list)
            ->andReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('MKCALENDAR', self::$property_list)
        );
    }

    public function testGenerateProfindBody()
    {
        $this->generator
            ->shouldReceive('propfindBody')
            ->once()
            ->with(self::$property_list)
            ->andReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('PROPFIND', self::$property_list)
        );
    }

    public function testGenerateProppatchBody()
    {
        $this->generator
            ->shouldReceive('proppatchBody')
            ->once()
            ->with(self::$property_list)
            ->andReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('PROPPATCH', self::$property_list)
        );
    }

    public function testGenerateCalendarQueryReportBody()
    {
        $filter = m::mock('AgenDAV\CalDAV\ComponentFilter');
        $this->generator
            ->shouldReceive('calendarQueryBody')
            ->once()
            ->with($filter)
            ->andReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('REPORT-CALENDAR', $filter)
        );
    }

    protected function createToolkit()
    {
        return new Toolkit(
            $this->parser,
            $this->generator
        );
    }
}
