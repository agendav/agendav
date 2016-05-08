<?php
namespace AgenDAV\Data;

use AgenDAV\CalDAV\Resource\Calendar;

class ShareTest extends \PHPUnit_Framework_TestCase
{

    public function testApplyCustomPropertiesTo()
    {
        $calendar = new Calendar('/calendar/url',
            [
                Calendar::DISPLAYNAME => 'Original displayname',
            ]
        );

        $share = new Share;

        $share->setProperty(Calendar::DISPLAYNAME, 'New displayname');
        $share->setProperty('{urn:test}invented', 'Test value');

        $share->applyCustomPropertiesTo($calendar);

        $this->assertEquals(
            'New displayname',
            $calendar->getProperty(Calendar::DISPLAYNAME),
            'Share::applyCustomPropertiesTo does not change existing calendar properties'
        );

        $this->assertEquals(
            'Test value',
            $calendar->getProperty('{urn:test}invented'),
            'Share::applyCustomPropertiesTo does not add new calendar properties'
        );
    }

}
