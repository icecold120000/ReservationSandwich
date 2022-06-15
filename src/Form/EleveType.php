<?php

namespace App\Form;

use App\Entity\Eleve;
use App\Entity\Classe;
use App\Repository\ClasseRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class EleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomEleve', TextType::class, [
                'label' => 'Nom de l\'élève',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom d\'élève !'
                    ])
                ]
            ])
            ->add('prenomEleve', TextType::class, [
                'label' => 'Prénom de l\'élève',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom d\'élève !'
                    ])
                ]
            ])
            ->add('photoEleve', FileType::class, [
                'label' => 'Photo de l\'élève',
                'help' => 'Type de fichier supporté : .png, .jpg ou .jpeg.',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .png/.jpeg/.jpg !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de 4096ko !',
                    ])
                ],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance de l\'élève',
                'html5' => true,
                'widget' => 'single_text',
                'required' => true,
                'invalid_message' => 'Votre saisie n\'est pas une date !',
            ])
            ->add('archiveEleve', ChoiceType::class, [
                'label' => 'Élève archivé',
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
                'required' => false,
                'empty_data' => false,
            ])
            ->add('classeEleve', EntityType::class, [
                'label' => 'Classe de l\'élève',
                'class' => Classe::class,
                'query_builder' => function (ClasseRepository $er) {
                    return $er->createQueryBuilder('cl')
                        ->orderBy('cl.id', 'ASC');
                },
                'choice_label' => 'codeClasse',
                'required' => true,
                'constraints' => [
                    new NotNull([
                        'message' => 'Veuillez sélectionner une classe !'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Eleve::class,
            'attr' => ['id' => 'eleveForm']
        ]);
    }
}
