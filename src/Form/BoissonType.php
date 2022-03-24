<?php

namespace App\Form;

use App\Entity\Boisson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BoissonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomBoisson', TextType::class,[
                'label' => 'Nom de la boisson',
                'required' => true,
            ])
            ->add('imageBoisson', FileType::class, [
                'label' => 'Photo de la boisson',
                'help' => 'Type de fichier supporté : png, jpg ou jpeg.',
                'mapped' => false,
                'required' => $options['fichierRequired'],
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier .png/.jpeg/.jpg !',
                        'maxSizeMessage' => 'Veuillez transférer un fichier ayant pour taille maximum de 4096 Ko !',
                    ])
                ],
            ])
            ->add('dispoBoisson', ChoiceType::class, [
                'label' => 'Disponibilité de la boisson',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boisson::class,
            'attr' => ['id' => 'boissonForm'],
            'fichierRequired' => true,
        ]);
    }
}
