<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderEleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ordreNom', ChoiceType::class, [
                'label' => 'Ordre par nom',
                'choices' => [
                    'Croissant' => 'ASC',
                    'Décroissant' => 'DESC',
                ],
                'required' => false,
            ])
            ->add('ordrePrenom', ChoiceType::class, [
                'label' => 'Ordre par prénom',
                'choices' => [
                    'Croissant' => 'ASC',
                    'Décroissant' => 'DESC',
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'filterClasseEleve'],
        ]);
    }
}