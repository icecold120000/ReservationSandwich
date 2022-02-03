<?php

namespace App\Form;

use App\Entity\Eleve;
use App\Entity\InscriptionCantine;
use App\Repository\EleveRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionCantineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['row_attr']['route'] == "inscription_cantine_new") {
            $required = true;
        }
        else{
            $required = false;
        }
        $builder
            ->add('repasJ1',ChoiceType::class, [
                'label' => 'Inscrit le lundi',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('repasJ2',ChoiceType::class, [
                'label' => 'Inscrit le mardi',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('repasJ3',ChoiceType::class, [
                'label' => 'Inscrit le mercredi',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('repasJ4',ChoiceType::class, [
                'label' => 'Inscrit le jeudi',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('repasJ5',ChoiceType::class, [
                'label' => 'Inscrit le vendredi',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => false,
            ])
            ->add('eleve', EntityType::class,[
                'label' => 'Élève auquel l\'inscription est rattachée',
                'mapped' => false,
                'class' => Eleve::class,
                'query_builder' => function (EleveRepository $er) {
                    return $er->createQueryBuilder('el');
                },
                'choice_label' => function (?Eleve $eleve) {
                    return $eleve ? $eleve->getPrenomEleve().' '. $eleve->getNomEleve() : '';
                },
                'required' => $required,
                'placeholder' => 'Veuillez choisir un élève',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InscriptionCantine::class,
            'attr' => ['id' => 'inscritCantForm']
        ]);
    }
}
