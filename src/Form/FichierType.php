<?php

namespace App\Form;

use App\Entity\Fichier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class FichierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fileSubmit', FileType::class, [
                'mapped' => false,
                'required' => true,
                'help' => 'Type de fichier supporté : Excel(xlsx).',
                'constraints' => [
                    new File([
                        'maxSize' => '8192k',
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .xlsx !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de {{limit}} !',
                    ]),
                    new NotNull([
                        'message' => 'Veuillez sélectionner un fichier'
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Fichier::class,
            'attr' => ['id' => 'formFichier']
        ]);
    }
}
