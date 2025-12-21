<?php

namespace App\Service\Site;

use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class SiteCreatorService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function create(Site $site): void
    {
        $this->em->persist($site);
        $this->em->flush();
    }
}
