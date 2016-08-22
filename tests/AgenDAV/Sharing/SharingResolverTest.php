<?php
namespace AgenDAV\Sharing;

use AgenDAV\Data\Share;
use AgenDAV\Data\Principal;
use \Mockery as m;

class SharingResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveShares()
    {
        $shares_repository = m::mock('\AgenDAV\Repositories\SharesRepository');
        $principals_repository = m::mock('\AgenDAV\Repositories\PrincipalsRepository');
        $sharing_resolver = new SharingResolver(
            $shares_repository,
            $principals_repository
        );

        $principal = new Principal('/principals/test');
        $principal->setDisplayName('Test principal');

        $principals_repository->shouldReceive('get')
            ->once()
            ->with($principal->getUrl())
            ->andReturn($principal);

        $share = new Share();
        $share->setWith($principal->getUrl());

        $sharing_resolver->resolveShares([ $share ]);

        $this->assertEquals(
            $principal,
            $share->getPrincipal(),
            'Sharing resolver does not retrieve Principals from the repository'
        );
    }
}
