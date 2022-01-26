<?php

namespace App\Form\FilterOrSearch;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class FilterEleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('classe', EntityType::class,[
                'label' => 'Classe',
                'class' => Classe::class,
                'query_builder' => function (ClasseRepository $er) {
                    return $er->createQueryBuilder('cl')
                        ->orderBy('cl.id');
                },
                'choice_label' => 'libelle',
                'choice_value' => function (?Classe $classe) {
                    return $classe ? $classe->getId() : '';
                },
                'required' => false,
                'placeholder' => 'Toutes',
            ])
            ->add('genreEleve', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Garçon' => 'M',
                    'Fille' => 'F',
                ],
                'required' => false,
                'placeholder' => 'Tous',
            ])
            ->add('archiveEleve', ChoiceType::class, [
                'label' => 'Archivés',
                'choices' => [
                    'Non Archivés' => false,
                    'Archivés' => true,
                ],
                'required' => false,
                'placeholder' => 'Tous',
            ])
            ->add('ordreNom', ChoiceType::class, [
                'label' => 'Ordre par nom',
                'choices' => [
                    'Croissant' => 'ASC',
                    'Décroissant' => 'DESC',
                ],
                'required' => false,
            ])
            ->add('ordrePrenom', ChoiceType::class, [
                'label' => 'Ordre par prénom',
                'choices' => [
                    'Croissant' => 'ASC',
                    'Décroissant' => 'DESC',
                ],
                'required' => false,
            ])
            ->add('compteExistant', ChoiceType::class, [
                'label' => 'Compte Existant',
                'choices' => [
                    'Oui' => 'Not Null',
                    'Non' => 'Null',
                ],
                'required' => false,
                'placeholder' => 'Tout',
            ])            
        ;
    }

}