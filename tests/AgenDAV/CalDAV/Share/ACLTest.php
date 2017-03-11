<?php
namespace AgenDAV\CalDAV\Share;

use AgenDAV\CalDAV\Share\Permissions;
use PHPUnit\Framework\TestCase;

class ACLTest extends TestCase
{

    public static $privileges;

    private $permissions;

    public static function setUpBeforeClass()
    {
        self::$privileges = [
            'default' => [
                '{urn:ietf:params:xml:ns:caldav}read-free-busy',
            ],
            'owner' => [
                '{DAV:}all',
            ],
            'read-write' => [
                '{DAV:}read',
                '{DAV:}write',
            ],
            'read-only' => [
                '{DAV:}read',
            ],
        ];

    }

    public function setUp()
    {
        $this->permissions = new Permissions(self::$privileges);
    }

    public function testDefault()
    {
        $acl = new ACL($this->permissions);
        $this->assertEquals(
            [],
            $acl->getGrants()
        );

        $this->assertEquals(
            self::$privileges['owner'],
            $acl->getOwnerPrivileges()
        );

        $this->assertEquals(
            self::$privileges['default'],
            $acl->getDefaultPrivileges()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddGrantInvalidRole()
    {
        $acl = new ACL($this->permissions);
        $acl->addGrant('/principal/1', 'owner');
    }

    public function testAddGrant()
    {
        $acl = new ACL($this->permissions);
        $acl->addGrant('/principal/1', 'read-write');

        $this->assertEquals(
            [ '/principal/1' => 'read-write' ],
            $acl->getGrants()
        );

        $this->assertEquals(
            [ '/principal/1' => self::$privileges['read-write'] ],
            $acl->getGrantsPrivileges()
        );
    }

}
