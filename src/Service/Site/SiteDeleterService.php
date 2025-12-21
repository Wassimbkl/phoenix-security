<?php

namespace App\Service\Site;

use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class SiteDeleterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function delete(Site $site): void
    {
      

        $this->em->remove($site);
        $this->em->flush();
    }
}
