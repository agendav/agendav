<?php
namespace AgenDAV\Data;

class SinglePermissionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $perm = new SinglePermission('NS', 'elem');
        $this->assertEquals(
            $perm->getNamespace(),
            'NS'
        );
        $this->assertEquals(
            $perm->getName(),
            'elem'
        );

        $perm2 = new SinglePermission(array('NS', 'elem'));
        $this->assertEquals(
            $perm2->getNamespace(),
            'NS'
        );
        $this->assertEquals(
            $perm2->getName(),
            'elem'
        );
    }
}
