<?php

namespace App\Form;

use App\Entity\CommandeGroupe;
use App\Entity\Dessert;
use App\Entity\LieuLivraison;
use App\Entity\Sandwich;
use App\Entity\SandwichCommandeGroupe;
use App\Entity\User;
use App\Repository\DessertRepository;
use App\Repository\LieuLivraisonRepository;
use App\Repository\SandwichRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class CommandeGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sandwichChoisi1', EntityType::class, [
                'label' => 'Choisir un premier sandwich',
                'class' => Sandwich::class,
                'mapped' => false,
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
                'data' => $options['sandwichChoisi1']?->getSandwichChoisi(),
                'invalid_message' => 'Veuillez sélectionner un sandwich !',
            ])
            ->add('nbSandwichChoisi1', NumberType::class, [
                'label' => 'Nombre du premier sandwich choisi',
                'mapped' => false,
                'invalid_message' => 'Veuillez saisir un nombre !',
                'required' => true,
                'constraints' => [
                    new Regex([
                        'match' => false,
                        'pattern' => "/[\-]/",
                        'message' => "Veuillez saisir un nombre positif !",
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez saisir un nombre de sandwich commandé !'
                    ])
                ],
                'data' => $options['sandwichChoisi1']?->getNombreSandwich(),
            ])
            ->add('sandwichChoisi2', EntityType::class, [
                'label' => 'Choisir un deuxième sandwich',
                'class' => Sandwich::class,
                'mapped' => false,
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
                'data' => $options['sandwichChoisi2']?->getSandwichChoisi(),
                'invalid_message' => 'Veuillez sélectionner un sandwich !',
            ])
            ->add('nbSandwichChoisi2', NumberType::class, [
                'label' => 'Nombre du deuxième sandwich choisi',
                'invalid_message' => 'Veuillez saisir un nombre !',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Regex([
                        'match' => false,
                        'pattern' => "/[\-]/",
                        'message' => "Veuillez saisir un nombre positif !",
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez saisir un nombre de sandwich commandé !'
                    ])
                ],
                'data' => $options['sandwichChoisi2']?->getNombreSandwich(),
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
            ->add('commentaireCommande', TextareaType::class, [
                'label' => 'Commentaire sur la commande',
                'help' => 'Ex : nombre d\'élève, allergies, d\'autres contraintes...',
                'required' => $options['requiredNonAdmin'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un commentaire !'])
                ],
            ])
            ->add('motifSortie', TextareaType::class, [
                'label' => 'Motif de la sortie',
                'help' => 'Ex : Description de la sortie, nombre de participant...',
                'required' => $options['requiredNonAdmin'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un motif de sortie !'])
                ],
            ])
            ->add('dateHeureLivraison', DateTimeType::class, [
                'label' => 'Date et Heure de livraison',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'help' => "Attention : Veuillez sélectionner une date d'au moins " . $options['limiteDateSortie'] . " jours minimum après aujourd'hui !",
                'invalid_message' => 'Votre saisie n\'est pas une date et heure !',
                'constraints' => [
                    new GreaterThanOrEqual("+" . $options['limiteDateSortie'] . "days 00:00:00",
                        null, "Veuillez choisir une date d'au moins " . $options['limiteDateSortie'] . " jours minimum !"),
                    new NotNull([
                        'message' => 'Veuillez choisir une date et heure de livraison !',
                    ]),
                ],
            ])
            ->add('lieuLivraison', EntityType::class, [
                'label' => 'Choisir un lieu de livraison',
                'class' => LieuLivraison::class,
                'query_builder' => function (LieuLivraisonRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->andWhere('l.estActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('l.libelleLieu', 'ASC');
                },
                'choice_label' => 'libelleLieu',
                'invalid_message' => 'Veuillez sélectionner un lieu de livraison !',
                'required' => true,
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
                        ->andWhere('u.roles not Like :eleve')
                        ->setParameters(['verified' => true, 'eleve' => '%' . 'ROLE_ELEVE' . '%']);
                },
                'choice_label' => function (?User $user) {
                    return $user ? substr($user->getPrenomUser(), 0, 4) . '. ' . $user->getNomUser() : '';
                },
                'placeholder' => 'Veuillez choisir une personne',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeGroupe::class,
            'attr' => ['id' => 'formCommandeGroupe'],
            'limiteDateSortie' => 0,
            'sandwichChoisi1' => SandwichCommandeGroupe::class,
            'sandwichChoisi2' => SandwichCommandeGroupe::class,
            'requiredNonAdmin' => true,
        ]);
    }
}
