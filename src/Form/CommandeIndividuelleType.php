<?php

namespace App\Form;

use App\Entity\Boisson;
use App\Entity\CommandeIndividuelle;
use App\Entity\Dessert;
use App\Entity\Sandwich;
use App\Entity\User;
use App\Repository\BoissonRepository;
use App\Repository\DessertRepository;
use App\Repository\SandwichRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CommandeIndividuelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sandwichChoisi', EntityType::class, [
                'label' => 'Choisir un sandwich',
                'class' => Sandwich::class,
                'query_builder' => function (SandwichRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.dispoSandwich = :dispo')
                        ->setParameter('dispo', true)
                        ->orderBy('s.id', 'ASC');
                },
                'choice_label' => 'nomSandwich',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'invalid_message' => 'Veuillez sélectionner un sandwich !',
            ])
            ->add('boissonChoisie', EntityType::class, [
                'label' => 'Choisir une boisson',
                'class' => Boisson::class,
                'query_builder' => function (BoissonRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->where('b.dispoBoisson = :dispo')
                        ->setParameter('dispo', true)
                        ->orderBy('b.id', 'ASC');
                },
                'choice_label' => 'nomBoisson',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'invalid_message' => 'Veuillez sélectionner une boisson !',
            ])
            ->add('dessertChoisi', EntityType::class, [
                'label' => 'Choisir un dessert',
                'class' => Dessert::class,
                'query_builder' => function (DessertRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->where('d.dispoDessert = :dispo')
                        ->setParameter('dispo', true)
                        ->orderBy('d.id', 'ASC');
                },
                'choice_label' => 'nomDessert',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'invalid_message' => 'Veuillez sélectionner un dessert !',
            ])
            ->add('prendreChips', ChoiceType::class, [
                'label' => 'Prendre des chips',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('dateHeureLivraison', DateTimeType::class, [
                'label' => 'Date et Heure de livraison',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'help' => 'Attention : Vous ne pouvez pas faire une commande pour le jour même après 9h30 !',
                'invalid_message' => 'Votre saisie n\'est pas une date et heure !',
                'constraints' => [
                    new GreaterThanOrEqual("today", null, "Veuillez choisir une date et/ou heure future !"),
                    new NotNull([
                        'message' => 'Veuillez choisir une date et heure de livraison !',
                    ]),
                ],
            ])
            ->add('raisonCommande', ChoiceType::class, [
                'label' => 'Votre raison de faire cette commande',
                'choices' => [
                    'AS' => 'AS',
                    'Réunion' => 'Réunion',
                    'Pastoral' => 'Pastorale',
                    'Autres (à préciser)' => 'Autre'
                ],
                'required' => true,
                'attr' => ["onChange" => "update()"],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une raison valable !'])
                ],
            ])
            ->add('raisonCommandeAutre', TextareaType::class, [
                'label' => 'À préciser',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une raison valable !'])
                ],
                'empty_data' => 'Ajouter text',
            ])
            ->add('commandeur', EntityType::class, [
                'label' => 'La personne qui a commandé',
                'help' => 'Appuyer sur une lettre pour filter les utilisateurs',
                'required' => false,
                'class' => User::class,
                'query_builder' => function (UserRepository $er) {
                    return $er
                        ->createQueryBuilder('u')
                        ->andWhere('u.isVerified = :verified')
                        ->setParameter('verified', true);
                },
                'choice_label' => function (?User $user) {
                    return $user ? substr($user->getPrenomUser(), 0, 4) . '. ' . $user->getNomUser() : '';
                },
                'placeholder' => 'Veuillez choisir une personne',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeIndividuelle::class,
            'attr' => ['id' => 'formCommandeInd'],
        ]);
    }
}
