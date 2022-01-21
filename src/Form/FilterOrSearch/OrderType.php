<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
             ->add('ordreAlphabet', ChoiceType::class, [
                'label' => 'Ordre',
                'choices' => [
                    'Croissant' => '1',
                    'Décroissant' => '0',
                ],
                'required' => false,
                'empty_data' => '1',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
