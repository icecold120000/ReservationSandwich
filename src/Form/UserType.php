<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'l\'email de l\'utilisateur',
                'required' => true,
            ])
            ->add('roles', CollectionType::class, [
                'label' => 'Rôle de l\'utilisateur',
                'entry_type'   => ChoiceType::class,
                'entry_options'  => [
                    'choices'  => [
                        'Admin' => User::ROLE_ADMIN,
                        'Eleve' => User::ROLE_ELEVE,
                        'Adulte' => User::ROLE_ADULTES,
                        'Cuisinier' => User::ROLE_CUISINE,
                        'Utilisateur' => User::ROLE_USER,
                    ],
                    'required' => false,
                    'placeholder' => 'Veuillez choisir un fonction de l\'utilisateur',
                ],
            ])
            ->add('password', PasswordType::class,[
                'label' => 'Mot de passe de l\'utilisateur',
                'required' => false,
            ])
            ->add('nomUser', TextType::class,[
                'label' => 'Nom de l\'utilisateur',
                'required' => true,
            ])
            ->add('prenomUser', TextType::class,[
                'label' => 'Prénom de l\'utilisateur',
                'required' => true,
            ])
            ->add('dateNaissanceUser', DateType::class, [
                'label' => 'Date de naissance de l\'utilisateur',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'help' => ' format : JJ/MM/AAAA'
            ])
            ->add('isVerified', ChoiceType::class, [
                'label' => 'Compte vérifié',
                'choices' => [
                    'Oui' => 1,
                    'Non' => '0',
                ],
                'required' => true,
                'empty_data' => 1,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => ['id' =>'userForm'],
        ]);
    }
}
