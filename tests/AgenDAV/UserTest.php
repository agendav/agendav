<?php

namespace AgenDAV;

use Mockery as m;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialize()
    {
        $session = $this->getSessionMock();

        $session->shouldReceive('get')
            ->with('username')
            ->andReturn('username_value')
            ->getMock();

        $session->shouldReceive('get')
            ->with('password')
            ->andReturn('password_value');

        $session->shouldReceive('isAuthenticated')
            ->andReturn(true);

        $user = new User($session);

        $this->assertEquals($user->getUsername(), 'username_value');
        $this->assertEquals($user->getPassword(), 'password_value');
    }

    protected function getSessionMock()
    {
        return m::mock('\AgenDAV\Session\Session');
    }
}
