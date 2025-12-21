<?php

namespace App\Service\Site;

use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class SiteUpdaterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function update(Site $site): void
    {
        

        $this->em->flush();
    }
}
