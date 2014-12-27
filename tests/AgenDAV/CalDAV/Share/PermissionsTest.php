<?php
namespace AgenDAV\CalDAV\Share;

class PermissionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testEmptyPrivileges()
    {
        $perms = new Permissions();
        $perms->getPrivilegesFor('owner');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadyConfiguredRole()
    {
        $perms = new Permissions([
            'owner' => [ '{DAV:}all' ],
        ]);

        $perms->setPrivilegesFor('owner', [ '{DAV:}moreall' ]);
    }

    public function testGetPrivilegeSet()
    {
        $privileges = [
            '{DAV:}all',
            '{urn:fake}privilege',
        ];

        $perms = new Permissions([
            'owner' => $privileges,
        ]);

        $this->assertEquals(
            $privileges,
            $perms->getPrivilegesFor('owner')
        );
    }

    public function testAddPrivilegeSet()
    {
        $privileges = [
            '{DAV:}all',
        ];

        $privileges_2 = [
            '{DAV:}write',
        ];

        $perms = new Permissions([
            'owner' => $privileges,
        ]);

        $perms->setPrivilegesFor('read-write', $privileges_2);

        $this->assertEquals(
            $privileges_2,
            $perms->getPrivilegesFor('read-write')
        );


        // Get all roles
        $this->assertEquals(
            [
                'owner' => $privileges,
                'read-write' => $privileges_2,
            ],
            $perms->getAll()
        );
    }
}
