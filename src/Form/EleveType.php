<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Eleve;
use App\Repository\ClasseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class EleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance de l\'élève',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'help' => ' format : JJ/MM/AAAA'
            ])
            ->add('archiveEleve', ChoiceType::class, [
                'label' => 'Adulte archivé',
                'choices' => [
                    'Non' => 1,
                    'Oui' => '0',
                ],
                'required' => true,
                'empty_data' => 1,
            ])
            ->add('photoEleve', FileType::class, [
                'mapped' => false,
                'required' => false,
                'help' => 'Type de fichier supporté : .png, .jpg ou .jpeg.',
                'constraints' => [
                    new File([
                        'maxSize' => '8192k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .png,.jpg ou .jpeg !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de {{limit}} !',
                    ]),
                    new NotNull([
                        'message' => 'Veuillez sélectionner un fichier !'
                    ])
                ],
            ])
            ->add('classeEleve', EntityType::class,[
                'label' => 'Classe de l\'élève',
                'class' => Classe::class,
                'query_builder' => function (ClasseRepository $er) {
                    return $er->createQueryBuilder('cl')
                        ->orderBy('cl.libelleClasse', 'ASC');
                },
                'choice_label' => 'libelleClasse',
                'required' => true,
                'empty_data' => '1',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Eleve::class,
            'attr' => ['id' =>'eleveForm'],
        ]);
    }
}
