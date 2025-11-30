<?php

namespace App\Validator;

use App\Entity\Shift;
use App\Repository\ShiftRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoShiftOverlapValidator extends ConstraintValidator
{
    public function __construct(
        private ShiftRepository $shiftRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoShiftOverlap) {
            throw new UnexpectedTypeException($constraint, NoShiftOverlap::class);
        }

        if (!$value instanceof Shift) {
            return;
        }

        // Vérifier que toutes les données nécessaires sont présentes
        if (!$value->getAgent() || !$value->getShiftDate() || !$value->getStartTime() || !$value->getEndTime()) {
            return;
        }

        // Récupérer tous les shifts de l'agent pour ce jour
        $existingShifts = $this->shiftRepository->createQueryBuilder('s')
            ->where('s.agent = :agent')
            ->andWhere('s.shiftDate = :date')
            ->setParameter('agent', $value->getAgent())
            ->setParameter('date', $value->getShiftDate())
            ->getQuery()
            ->getResult();

        $newStart = $value->getStartTime();
        $newEnd = $value->getEndTime();

        foreach ($existingShifts as $existingShift) {
            // Ignorer le shift actuel s'il est en cours de modification
            if ($value->getId() && $existingShift->getId() === $value->getId()) {
                continue;
            }

            $existingStart = $existingShift->getStartTime();
            $existingEnd = $existingShift->getEndTime();

            // Vérifier le chevauchement
            // Il y a chevauchement si:
            // - Le nouveau shift commence pendant un shift existant
            // - Le nouveau shift se termine pendant un shift existant
            // - Le nouveau shift englobe complètement un shift existant
            if ($this->timesOverlap($newStart, $newEnd, $existingStart, $existingEnd)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ agent }}', $value->getAgent()->getFirstName() . ' ' . $value->getAgent()->getLastName())
                    ->setParameter('{{ date }}', $value->getShiftDate()->format('d/m/Y'))
                    ->setParameter('{{ start }}', $existingStart->format('H:i'))
                    ->setParameter('{{ end }}', $existingEnd->format('H:i'))
                    ->addViolation();
                
                return; // Un seul message d'erreur suffit
            }
        }
    }

    private function timesOverlap(
        \DateTimeInterface $start1,
        \DateTimeInterface $end1,
        \DateTimeInterface $start2,
        \DateTimeInterface $end2
    ): bool {
        // Convertir en minutes pour faciliter la comparaison
        $s1 = $start1->format('H') * 60 + $start1->format('i');
        $e1 = $end1->format('H') * 60 + $end1->format('i');
        $s2 = $start2->format('H') * 60 + $start2->format('i');
        $e2 = $end2->format('H') * 60 + $end2->format('i');

        // Gérer les shifts qui passent minuit
        if ($e1 < $s1) $e1 += 24 * 60;
        if ($e2 < $s2) $e2 += 24 * 60;

        // Il y a chevauchement si les plages se croisent
        return $s1 < $e2 && $e1 > $s2;
    }
}
