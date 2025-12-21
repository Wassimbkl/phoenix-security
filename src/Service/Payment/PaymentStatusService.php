<?php

namespace App\Service\Payment;

use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;

class PaymentStatusService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function markAsPaid(Payment $payment, \DateTime $paymentDate): void
    {
        $payment->setPaymentDate($paymentDate);
        $this->em->flush();
    }
}
