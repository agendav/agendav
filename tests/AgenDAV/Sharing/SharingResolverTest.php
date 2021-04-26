<?php
namespace AgenDAV\Sharing;

use AgenDAV\Data\Share;
use AgenDAV\Data\Principal;
use PHPUnit\Framework\TestCase;

class SharingResolverTest extends TestCase
{
    public function testResolveShares()
    {
        $shares_repository = $this->createMock(\AgenDAV\Repositories\SharesRepository::class);
        $principals_repository = $this->createMock(\AgenDAV\Repositories\PrincipalsRepository::class);
        $sharing_resolver = new SharingResolver(
            $shares_repository,
            $principals_repository
        );

        $principal = new Principal('/principals/test');
        $principal->setDisplayName('Test principal');

        $principals_repository
            ->expects($this->once())
            ->method('get')
            ->with($principal->getUrl())
            ->willReturn($principal);

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
