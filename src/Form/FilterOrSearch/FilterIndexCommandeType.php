<?php

namespace App\Form\FilterOrSearch;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterIndexCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', ChoiceType::class,[
                'label' => 'Par date',
                'choices' => [
                    'Aujourd\'hui' => new \DateTime('now'),
                    'Demain' => new \DateTime('+1 day'),
                    'Dans 1 semaine' => new \DateTime('+1 week'),
                    'Dans 1 mois' => new \DateTime('+1 month'),
                ],
                'required' => false,
            ])
            ->add('cloture', ChoiceType::class,[
                'label' => 'Commande clôturé',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => false,
            ])
            ->add('affichageTableau', ChoiceType::class,[
                'label' => 'Affichage tableau',
                'choices' => [
                    'Les deux' => 'les deux',
                    'Commandes individuelles seulement' => 'individuelle',
                    'Commandes groupées seulement' => 'groupé',
                ],
                'required' => false,
                'empty_data' => 'les deux',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
