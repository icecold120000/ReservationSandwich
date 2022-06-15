<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fileSubmit', FileType::class, [
                'mapped' => false,
                'required' => true,
                'help' => 'Type de fichier supporté : .png, .jpg ou .jpeg.',
                'constraints' => [
                    new File([
                        'maxSize' => '8192k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .png, .jpg ou .jpeg !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de 8192ko !',
                    ]),
                    new NotNull([
                        'message' => 'Veuillez sélectionner un fichier !'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'menuForm']
        ]);
    }
}
