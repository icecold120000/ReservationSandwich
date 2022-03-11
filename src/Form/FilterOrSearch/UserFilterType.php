<?php

namespace App\Form\FilterOrSearch;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('roleUser', ChoiceType::class, [
                'label' => 'Fonction',
                'choices' => [
                    'Utilisateur'=> '[]',
                    'Admin' => User::ROLE_ADMIN,
                    'Élève' => User::ROLE_ELEVE,
                    'Cuisine' => User::ROLE_CUISINE,
                    'Adulte' => User::ROLE_ADULTES,
                ],
                'required' => false,
                'placeholder' => 'Toutes',
            ])
            ->add('userVerifie', ChoiceType::class, [
                'label' => 'Utilisateurs vérifiés',
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
                'required' => false,
                'placeholder' => 'Tous',
            ])
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'filterUser'],
        ]);
    }
}