<?php
namespace AgenDAV\Data;

use PHPUnit\Framework\TestCase;

class PreferencesTest extends TestCase
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

    public function testGetMethod()
    {
        $prefs = new Preferences();
        $prefs->exists = 'This one exists';

        $this->assertEquals('This one exists', $prefs->get('exists', 'default'));
        $this->assertEquals('default', $prefs->get('does_not_exist', 'default'));
    }

}
