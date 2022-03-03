<?php

namespace App\Form;

use App\Entity\LimitationCommande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LimitationCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelleLimite', TextType::class,[
                'label' => 'Libelle de la limitation',
                'required' => true,
            ])
            ->add('is_active', ChoiceType::class, [
                'label' => 'Limitation activée',
                'choices' => [
                    'Non' => false,
                    'Oui' => true,
                ],
                'required' => true,
            ])
            ->add('nbLimite', NumberType::class,[
                'label' => 'Nombre limite',
                'invalid_message' => 'Veuillez saisir un nombre !',
                'required' => false,
            ])
            ->add('heureLimite', TimeType::class,[
                'label' => 'Heure limite',
                'required' => false,
                'invalid_message' => 'Veuillez saisir une heure réelle !',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LimitationCommande::class,
            'attr' => ['id' => 'limiteForm']
        ]);
    }
}
