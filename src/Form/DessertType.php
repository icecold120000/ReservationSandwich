<?php

namespace App\Form;

use App\Entity\Dessert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DessertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomDessert', TextType::class, [
                'label' => 'Nom du dessert',
                'required' => true,
            ])
            ->add('imageDessert', FileType::class, [
                'label' => 'Photo du dessert',
                'help' => 'Type de fichier supporté : .png, .jpg ou .jpeg.',
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
            ->add('ingredientDessert', TextareaType::class, [
                'label' => 'Liste d\'ingrédients du dessert',
                'required' => false,
            ])
            ->add('commentaireDessert', TextareaType::class, [
                'label' => 'Commentaire sur le dessert',
                'help' => 'Ex : spécifier des allergènes, régime particuliers...',
                'required' => false,
            ])
            ->add('dispoDessert', ChoiceType::class, [
                'label' => 'Disponibilité du dessert',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
                'empty_data' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dessert::class,
            'attr' => ['id' => 'dessertForm'],
            'fichierRequired' => true,
        ]);
    }
}
