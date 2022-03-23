<?php

namespace App\Form;

use App\Entity\Boisson;
use App\Entity\CommandeIndividuelle;
use App\Entity\Dessert;
use App\Entity\Sandwich;
use App\Repository\BoissonRepository;
use App\Repository\DessertRepository;
use App\Repository\SandwichRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CommandeIndividuelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sandwichChoisi', EntityType::class,[
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
            ])
            ->add('boissonChoisie', EntityType::class,[
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
                    new GreaterThanOrEqual("today",null,"Veuillez sélectionner une date et/ou heure future !"),
                    new NotNull([
                        'message' => 'Veuillez saisir une date et heure de livraison !',
                    ]),
                ],
            ])
            ->add('raisonCommande', ChoiceType::class, [
                'label' => 'Votre raison de faire cette commande',
                'choices' => [
                    'AS' => 'AS',
                    'Réunion' => 'Réunion',
                    'Pastoral' => 'Pastoral',
                    'Autres (à préciser)' => 'Autre'
                ],
                'required' => true,
                'attr' => [ 'onChange' => 'update()'],
            ])
            ->add('raisonCommandeAutre', TextareaType::class,[
                'label' => 'À préciser',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une raison valable!'])
                ],
                'empty_data' => 'Ajouter text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeIndividuelle::class,
            'attr' => ['id' => 'formCommandeInd']
        ]);
    }
}
