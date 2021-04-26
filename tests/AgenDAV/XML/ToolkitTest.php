<?php
namespace AgenDAV\XML;

use AgenDAV\CalDAV\Resource\Calendar;
use PHPUnit\Framework\TestCase;

class ToolkitTest extends TestCase
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
        $this->parser = $this->createMock(Parser::class);
        $this->generator = $this->createMock(Generator::class);
    }

    public function testFacadeParseMultiStatus()
    {
        $this->parser
            ->expects($this->once())
            ->method('extractPropertiesFromMultistatus')
            ->with('body', false)
            ->willReturn(['result']);

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
            ->expects($this->once())
            ->method('mkCalendarBody')
            ->with(self::$property_list)
            ->willReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('MKCALENDAR', self::$property_list)
        );
    }

    public function testGenerateProfindBody()
    {
        $this->generator
            ->expects($this->once())
            ->method('propfindBody')
            ->with(self::$property_list)
            ->willReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('PROPFIND', self::$property_list)
        );
    }

    public function testGenerateProppatchBody()
    {
        $this->generator
            ->expects($this->once())
            ->method('proppatchBody')
            ->with(self::$property_list)
            ->willReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('PROPPATCH', self::$property_list)
        );
    }

    public function testGenerateCalendarQueryReportBody()
    {
        $filter = $this->createMock(\AgenDAV\CalDAV\ComponentFilter::class);
        $this->generator
            ->expects($this->once())
            ->method('calendarQueryBody')
            ->with($filter)
            ->willReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('REPORT-CALENDAR', $filter)
        );
    }

    public function testGeneratePrincipalPropertySearchReportBody()
    {
        $filter = $this->createMock(\AgenDAV\CalDAV\Filter\PrincipalPropertySearch::class);
        $this->generator
            ->method('principalPropertySearchBody')
            ->with($filter)
            ->willReturn('result');

        $toolkit = $this->createToolkit();
        $this->assertEquals(
            'result',
            $toolkit->generateRequestBody('REPORT-PRINCIPAL-SEARCH', $filter)
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
