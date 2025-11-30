<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoShiftOverlap extends Constraint
{
    public string $message = 'L\'agent {{ agent }} a déjà un shift le {{ date }} de {{ start }} à {{ end }}. Les horaires se chevauchent.';
    
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
