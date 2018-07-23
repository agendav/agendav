<?php


namespace AgenDAV\Repositories;

use Doctrine\ORM\EntityManager;
use AgenDAV\Data\Share;
use AgenDAV\Data\Principal;
use AgenDAV\CalDAV\Resource\Calendar;


class DoctrineOrmPrincipalsRepository implements PrincipalsRepository
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var DAVPrincipalsRepository
     */
    private $davPrinRepo;


    /**
     * @param Doctrine\ORM\EntityManager Entity manager
     */
    public function __construct(EntityManager $em, DAVPrincipalsRepository $davPrinRepo)
    {
        $this->em = $em;
        $this->davPrinRepo = $davPrinRepo;
    }
    
    /**
     * Returns a Principal object for a given URL
     *
     * @param string $url
     * @return AgenDAV\Data\Principal
     */
    public function get($url)
    {

        $principal = $this->em->find('AgenDAV\Data\Principal', $url);

        if($principal == NULL) {
            $principal = $this->davPrinRepo->get($url);
            $this->save($principal);
        }
        return $principal;
    }

    /**
     * Searchs a principal using a filter string
     *
     * @param string $filter
     * @return AgenDAV\Data\Principal[]
     */
    public function search($filter)
    {
        return $this->davPrinRepo->search($filter);
    }
    
    /**
     * Stores a grant on the database
     *
     * @param AgenDAV\Data\Share $share  Share object
     */
    public function save(Principal $principal)
    {
        $this->em->persist($principal);
        $this->em->flush();
    }
}

