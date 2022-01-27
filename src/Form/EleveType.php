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

class EleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomEleve', TextType::class,[
                'label' => 'Nom de l\'élève',
                'required' => true,
            ])
            ->add('prenomEleve', TextType::class,[
                'label' => 'Prénom de l\'élève',
                'required' => true,
            ])
            ->add('photoEleve', FileType::class, [
                'label' => 'Photo de l\'élève',
                'help' => 'Type de fichier supporté : png, jpg ou jpeg.',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .png/.jpeg/.jpg !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de {{limit}} !',
                    ])
                ],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance de l\'élève',
                'help' => 'Format : JJ/MM/AAAA.',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'invalid_message' => 'Votre saisie n\'est pas une date !',
            ])
            ->add('archiveEleve', ChoiceType::class, [
                'label' => 'Élève archivé',
                'choices' => [
                    'Non' => '0',
                    'Oui' => 1,
                ],
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('classeEleve', EntityType::class,[
                'label' => 'Classe de l\'élève',
                'class' => Classe::class,
                'query_builder' => function (ClasseRepository $er) {
                    return $er->createQueryBuilder('cl')
                        ->orderBy('cl.id', 'ASC');
                },
                'choice_label' => 'codeClasse',
                'required' => true,
                'empty_data' => '1',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Eleve::class,
            'attr' => ['id' => 'eleveForm']
        ]);
    }
}
