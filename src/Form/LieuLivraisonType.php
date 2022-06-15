<?php

namespace App\Form;

use App\Entity\LieuLivraison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LieuLivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelleLieu', TextType::class, [
                'label' => 'Libellé du lieu',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un libellé de lieu !'
                    ])
                ]
            ])
            ->add('estActive', ChoiceType::class, [
                'label' => 'Lieu activé',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LieuLivraison::class,
            'attr' => ['id' => 'lieuForm'],
        ]);
    }
}
