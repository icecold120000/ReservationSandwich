<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomUser', TextType::class, [
                'label' => 'Nom de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom d\'utilisateur !'
                    ])
                ]
            ])
            ->add('prenomUser', TextType::class, [
                'label' => 'Prénom de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un prénom d\'utilisateur !'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un email !'
                    ])
                ],
                'invalid_message' => 'Votre saisie n\'est pas un email !',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'required' => false,
                'help' => 'Votre mot de passe doit comporter au moins 6 caractères.',
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Votre mot de passe'],
                'second_options' => ['label' => 'Confirmer votre mot de passe'],
                'invalid_message' => 'Les mots de passe doivent correspondre !',
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit comporter au moins 6 caractères !',
                        'max' => 4096,
                        'maxMessage' => 'Votre mot de passe doit être limité à 4096 caractères !',
                    ]),
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez saisir un mot de passe !'
                        ])
                    ]
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => ['id' => 'formEditProfile']
        ]);
    }
}
