<?php

namespace App\Form;

use App\Entity\Adulte;
use App\Entity\User;
use App\Entity\Eleve;
use App\Repository\AdulteRepository;
use App\Repository\EleveRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un email de l\'utilisateur !'
                    ])
                ]
            ])
            ->add('roles', CollectionType::class, [
                'label' => 'Fonction de l\'utilisateur',
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'choices' => [
                        'Utilisateur' => '[]',
                        'Admin' => User::ROLE_ADMIN,
                        'Élève' => User::ROLE_ELEVE,
                        'Cuisinie' => User::ROLE_CUISINE,
                        'Adulte' => User::ROLE_ADULTES,
                    ],
                    'required' => false,
                    'placeholder' => 'Veuillez choisir une fonction',
                    'attr' => ['onChange' => 'update()'],
                    'label_attr' => ['onChange' => 'update()'],
                    'empty_data' => '[]',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe de l\'utilisateur',
                'required' => $options['password_required'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre mot de passe !',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit comporter au moins 6 caractères !',
                        'max' => 4096,
                        'maxMessage' => 'Votre mot de passe doit être limité à 4096 caractères !',
                    ]),
                ]
            ])
            ->add('nomUser', TextType::class, [
                'label' => 'Nom de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom de l\'utilisateur !'
                    ])
                ]
            ])
            ->add('prenomUser', TextType::class, [
                'label' => 'Prénom de l\'utilisateur',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un prénom de l\'utilisateur !'
                    ])
                ]
            ])
            ->add('dateNaissanceUser', DateType::class, [
                'label' => 'Date de naissance de l\'utilisateur',
                'help' => 'Format : JJ/MM/AAAA.',
                'mapped' => false,
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'invalid_message' => 'Votre saisie n\'est pas une date !',
            ])
            ->add('isVerified', ChoiceType::class, [
                'label' => 'Utilisateur vérifié',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => false,
                'empty_data' => false,
            ])
            ->add('eleve', EntityType::class, [
                'label' => 'Compte auquel l\'utilisateur est rattaché',
                'mapped' => false,
                'class' => Eleve::class,
                'query_builder' => function (EleveRepository $er) {
                    return $er
                        ->createQueryBuilder('el')
                        ->andWhere('el.archiveEleve = :archive')
                        ->setParameter('archive', false);
                },
                'choice_label' => function (?Eleve $eleve) {
                    return $eleve ? substr($eleve->getPrenomEleve(), 0, 4) . '. ' . $eleve->getNomEleve() : '';
                },
                'required' => false,
                'placeholder' => 'Veuillez choisir un élève',
            ])
            ->add('adulte', EntityType::class, [
                'mapped' => false,
                'class' => Adulte::class,
                'query_builder' => function (AdulteRepository $er) {
                    return $er
                        ->createQueryBuilder('ad')
                        ->andWhere('ad.archiveAdulte = :archive')
                        ->setParameter('archive', false);
                },
                'choice_label' => function (?Adulte $adulte) {
                    return $adulte ? substr($adulte->getPrenomAdulte(), 0, 4) . '. ' . $adulte->getNomAdulte() : '';
                },
                'required' => false,
                'placeholder' => 'Veuillez choisir un adulte',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => ['id' => 'userForm'],
            'password_required' => true,
        ]);
    }
}
