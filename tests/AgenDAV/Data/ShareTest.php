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

    // Just make sure there are no errors
    public function testReplaceOldPropertiesNothingHappens()
    {
        $share = new Share;

        $share->replaceOldProperties();
    }

    public function testReplaceOldProperties()
    {
        $share = new Share;

        $share->setProperty('displayname', 'Old style displayname');
        $share->setProperty('color', '#ffaa00ff');

        $share->replaceOldProperties();

        $this->assertEquals(
            'Old style displayname',
            $share->getProperty(Calendar::DISPLAYNAME),
            'Share::replaceOldProperties does not move displayname to its namespaced property'
        );

        $this->assertEquals(
            null,
            $share->getProperty('displayname'),
            'Share::replaceOldProperties does not remove "displayname"'
        );

        $this->assertEquals(
            '#ffaa00ff',
            $share->getProperty(Calendar::COLOR),
            'Share::replaceOldProperties does not move color to its namespaced property'
        );

        $this->assertEquals(
            null,
            $share->getProperty('color'),
            'Share::replaceOldProperties does not remove "color"'
        );
    }

    public function testReplaceOldPropertiesWhenNewExist()
    {
        $share = new Share;

        $share->setProperty(Calendar::DISPLAYNAME, 'New style displayname');
        $share->setProperty('displayname', 'Old style displayname');

        $share->replaceOldProperties();

        $this->assertEquals(
            'New style displayname',
            $share->getProperty(Calendar::DISPLAYNAME),
            'Share::replaceOldProperties does not override displayname with its namespaced property'
        );

        $this->assertEquals(
            null,
            $share->getProperty('displayname'),
            'Share::replaceOldProperties does not remove "displayname"'
        );
    }

}
