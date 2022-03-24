<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterLieuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ordreLieu', ChoiceType::class, [
                'label' => 'Ordre',
                'choices' => [
                    'Croissant' => 'ASC',
                    'Décroissant' => 'DESC',
                ],
                'required' => false,
                'empty_data' => 'ASC',
            ])
            ->add('lieuActive', ChoiceType::class,[
                'label' => 'Active',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => false,
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
