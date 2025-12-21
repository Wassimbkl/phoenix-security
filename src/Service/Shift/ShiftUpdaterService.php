<?php

namespace App\Service\Shift;

use App\Entity\Shift;
use Doctrine\ORM\EntityManagerInterface;

class ShiftUpdaterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function update(Shift $shift): void
    {
        
        $this->em->flush();
    }
}
