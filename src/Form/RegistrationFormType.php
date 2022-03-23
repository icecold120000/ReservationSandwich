<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomUser', TextType::class, [
                'label' => 'Votre nom',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom !',
                    ]),
                ],
            ])
            ->add('prenomUser', TextType::class, [
                'label' => 'Votre prénom',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un prénom !',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre e-mail',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un e-mail !',
                    ]),
                ],
            ])
            ->add('dateNaissanceUser', DateType::class, [
                'label' => 'Votre date de naissance',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotNull([
                        'message' => 'Veuillez saisir une date de naissance !',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'required' => true,
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Votre mot de passe'],
                'second_options' => ['label' => 'Confirmer votre mot de passe'],
                'invalid_message' => 'Vos mots de passe doivent correspondre !',
                'help' => 'Veuillez saisir un mot de passe avec au moins 6 caractères.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre mot de passe !',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Vos champs doit comporter au moins 6 caractères !',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_group' => ['Default', 'inscription'],
            'attr' => ['id' => 'registerForm']
        ]);
    }
}
