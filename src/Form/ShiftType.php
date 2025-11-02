<?php

namespace App\Form;

use App\Entity\Shift;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shiftDate')
            ->add('startTime')
            ->add('endTime')
            ->add('type')
            ->add('status')
            ->add('agent')
            ->add('site')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shift::class,
        ]);
    }
}
