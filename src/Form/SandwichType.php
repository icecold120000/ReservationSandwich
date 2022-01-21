<?php

namespace App\Form;

use App\Entity\Sandwich;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SandwichType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['row_attr']['route'] == "sandwich_new") {
            $required = true;
        }
        else{
            $required = false;
        }
        $builder
            ->add('nomSandwich', TextType::class,[
                'label' => 'Nom du sandwich',
                'required' => true,
            ])
            ->add('imageSandwich', FileType::class, [
                'label' => 'Photo du sandwich',
                'help' => 'Type de fichier supporté : png, jpg ou jpeg.',
                'mapped' => false,
                'required' => $required,
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
            ->add('ingredientSandwich', TextareaType::class,[
                'label' => 'Liste d\'ingrédients du sandwich',
                'required' => false,
            ])
            ->add('commentaireSandwich', TextareaType::class,[
                'label' => 'Liste d\'ingrédients du sandwich',
                'required' => false,
            ])
            ->add('dispoSandwich',ChoiceType::class, [
                'label' => 'Disponibilité du sandwich',
                'choices' => [
                    'Oui' => 1,
                    'Non' => '0',
                ],
                'required' => true,
                'empty_data' => 1,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sandwich::class,
            'attr' => ['id' => 'sandwichForm']
        ]);
    }
}
