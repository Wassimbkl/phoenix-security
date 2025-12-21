<?php

namespace App\Service\Shift;

use App\Entity\Shift;
use Doctrine\ORM\EntityManagerInterface;

class ShiftDeleterService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function delete(Shift $shift): void
    {
        $this->em->remove($shift);
        $this->em->flush();
    }
}
