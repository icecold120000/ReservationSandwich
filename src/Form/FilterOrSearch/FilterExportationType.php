<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterExportationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('methodeExport',ChoiceType::class,[
                'label' => 'Méthode d\'exportation',
                'required' => true,
                'choices' => [
                    'PDF' => 'PDF',
                    'Excel' => 'Excel',
                    'Impression' => 'Impression',
                ],
            ])
            ->add('modaliteCommande', ChoiceType::class, [
                'label' => 'Choix de présentation',
                'required' => true,
                'choices' => [
                    'Par commande' => 'Séparé',
                    'Groupée pour préparation' => 'Regroupé'
                ],
            ])
            ->add('dateExport', DateType::class,[
                'label' => 'Choisir la date',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'formExport']
        ]);
    }
}
