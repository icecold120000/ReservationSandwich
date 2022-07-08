<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;

class FilterExportationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('methodeExport', ChoiceType::class, [
                'label' => 'Méthode d\'exportation',
                'required' => true,
                'choices' => [
                    'Impression' => 'Impression',
                    'Excel' => 'Excel',
                    'PDF' => 'PDF',
                ],
                'constraints' => [
                    new NotNull([
                        'message' => 'Veuillez choisir une méthode d\'exportation !',
                    ]),
                ],
            ])
            ->add('affichageExport', ChoiceType::class, [
                'label' => 'Affichage de l\'exportation',
                'choices' => [
                    'Les deux' => 'les deux',
                    'Commandes individuelles seulement' => 'individuelle',
                    'Commandes groupées seulement' => 'groupé',
                ],
                'required' => true,
                'empty_data' => 'les deux',
            ])
            ->add('modaliteCommande', ChoiceType::class, [
                'label' => 'Choix de présentation',
                'required' => true,
                'choices' => [
                    'Par commande' => 'Séparées',
                    'Regroupée pour la préparation' => 'Regroupées'
                ],
                'constraints' => [
                    new NotNull([
                        'message' => 'Veuillez choisir une présentation !',
                    ]),
                ],
            ])
            ->add('dateExport', DateType::class, [
                'label' => 'Choisir la date',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual("-1 month 00:00:00",
                        null, "Vous ne pouvez plus exporter de commandes qui ont plus d\'un mois !"),
                    new NotNull([
                        'message' => 'Veuillez choisir une date !',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'formExport']
        ]);
    }
}
