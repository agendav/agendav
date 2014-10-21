<?php
namespace AgenDAV\Data;

class PreferencesTest extends \PHPUnit_Framework_TestCase
{
    public function testUsername()
    {
        $prefs = new Preferences();
        $prefs->setUsername('user');
        $this->assertEquals($prefs->getUsername(), 'user');
    }

    public function testCreation()
    {
        $prefs = new Preferences(array('id1' => 'value1', 'id2' => 'value2'));
        $this->assertEquals($prefs->id1, 'value1');
        $this->assertEquals($prefs->id2, 'value2');
    }

    public function testSetAll()
    {
        $values = array(
            'i1' => 'v1',
            'i2' => 'v2',
        );
        $prefs = new Preferences();
        $prefs->setAll($values);
        $this->assertEquals($prefs->i1, 'v1');
        $this->assertEquals($prefs->i2, 'v2');
    }

}
