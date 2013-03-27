<?php
namespace AgenDAV\Data;

class PermissionsTest extends \PHPUnit_Framework_TestCase
{
    private $sample1, $sample2;

    public function setUp()
    {
        $this->sample1 = array(
            new SinglePermission('NS1', 'P1'),
        );

        $this->sample2 = array(
            new SinglePermission('NS2', 'P2'),
            new SinglePermission('NS3', 'P3'),
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWrongDefault()
    {
        $perms = new Permissions(array('wrong!'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWrongPermsProfile()
    {
        $perms = new Permissions(array());
        $perms->addProfile('test', array('wrong'));
    }

    public function testGetDefault()
    {
        $perms = new Permissions($this->sample1);
        $this->assertEquals(
            $perms->getDefault(),
            $this->sample1
        );
    }

    public function testGetProfile()
    {
        $perms = new Permissions($this->sample1);
        $perms->addProfile('test', $this->sample2);
        $this->assertEquals(
            $perms->getProfile('test'),
            $this->sample2
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetWrongProfile()
    {
        $perms = new Permissions($this->sample1);
        $perms->addProfile('test', $this->sample2);
        $perms->getProfile('doesntexist');
    }
}
