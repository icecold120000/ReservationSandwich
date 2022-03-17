<?php

namespace App\Form;

use App\Entity\CommandeGroupe;
use App\Entity\Dessert;
use App\Entity\Sandwich;
use App\Repository\DessertRepository;
use App\Repository\SandwichRepository;
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
use Symfony\Component\Validator\Constraints\Regex;

class CommandeGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sandwichChoisi1', EntityType::class,[
                'label' => 'Choisir un premier sandwich',
                'class' => Sandwich::class,
                'mapped' =>false,
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
            ])
            ->add('nbSandwichChoisi1', NumberType::class,[
                'label' => 'Nombre du premier sandwich choisi',
                'mapped' =>false,
                'invalid_message' => 'Veuillez saisir un nombre !',
                'required' => true,
                'constraints' => [
                    new Regex([
                        'match' => false,
                        'pattern' => "/[\-]/",
                        'message' => "Veuillez saisir un nombre positif !",
                    ])
                ],
            ])
            ->add('sandwichChoisi2', EntityType::class,[
                'label' => 'Choisir un deuxième sandwich',
                'class' => Sandwich::class,
                'mapped' =>false,
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
            ])
            ->add('nbSandwichChoisi2', NumberType::class,[
                'label' => 'Nombre du deuxième sandwich choisi',
                'invalid_message' => 'Veuillez saisir un nombre !',
                'mapped' =>false,
                'required' => true,
                'constraints' => [
                    new Regex([
                        'match' => false,
                        'pattern' => "/[\-]/",
                        'message' => "Veuillez saisir un nombre positif !",
                    ])
                ],
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
            ->add('commentaireCommande', TextareaType::class,[
                'label' => 'Commentaire sur la commande',
                'help' => 'Nombre d\'élève, allergies, d\'autres contraintes...',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un commentaire'])
                ],
            ])
            ->add('motifSortie', TextareaType::class,[
                'label' => 'Motif sortie',
                'help' => 'Description de la sortie, nombre de participant...',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un commentaire.'])
                ],
            ])
            ->add('dateHeureLivraison', DateTimeType::class, [
                'label' => 'Date et Heure de livraison',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'help' => "Attention : Veuillez sélectionner une date d'au moins ".$options['limiteDateSortie']." jours minimum !",
                'empty_data' => date("d/m/Y H:i"),
                'invalid_message' => 'Votre saisie n\'est pas une date et heure !',
                'constraints' => [
                    new GreaterThanOrEqual("+".$options['limiteDateSortie']."days 00:00:00",
                        null,"Veuillez sélectionner une date d'au moins ".$options['limiteDateSortie']." jours minimum !"),
                ],
            ])
            ->add('lieuLivraison', ChoiceType::class, [
                'label' => 'Choisir le lieu de livraison',
                'choices' => [
                    'Self' => 'Self',
                    'Accueil' => 'Accueil',
                    'Cuisine' => 'Cuisine',
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('dessertChoisi', EntityType::class,[
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeGroupe::class,
            'attr' => ['id'=>'formCommandeGroupe'],
            'limiteDateSortie' => 7,
        ]);
    }
}