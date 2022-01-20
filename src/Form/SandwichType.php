<?php

namespace App\Form;

use App\Entity\Sandwich;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SandwichType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSandwich')
            ->add('imageSandwich')
            ->add('ingredientSandwich')
            ->add('commentaireSandwich')
            ->add('disponibleSandwich')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sandwich::class,
        ]);
    }
}
