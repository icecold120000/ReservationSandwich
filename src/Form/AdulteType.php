<?php

namespace App\Form;

use App\Entity\Adulte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdulteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomAdulte', TextType::class, [
                'label' => 'Nom de l\'adulte',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un nom d\'adulte !'
                    ])
                ]
            ])
            ->add('prenomAdulte', TextType::class, [
                'label' => 'Prénom de l\'adulte',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un prénom d\'adulte !'
                    ])
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance de l\'adulte ',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'help' => ' format : JJ/MM/AAAA',
                'invalid_message' => 'Votre saisie n\'est pas une date !',
                'constraints' => [
                    new LessThan([
                        'value' => 'today',
                        'message' => 'Veuillez saisir une date de naissance !'
                    ]),
                ]
            ])
            ->add('archiveAdulte', ChoiceType::class, [
                'label' => 'Adulte archivé',
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
                'required' => true,
                'empty_data' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adulte::class,
            'attr' => ['id' => 'adulteForm'],
        ]);
    }
}
