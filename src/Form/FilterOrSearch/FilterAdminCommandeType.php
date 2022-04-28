<?php

namespace App\Form\FilterOrSearch;

use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterAdminCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Rechercher une personne',
                'help' => 'Saisie possible : Nom, Prénom, Classe',
                'required' => false,
            ])
            ->add('date', ChoiceType::class, [
                'label' => 'Par date',
                'choices' => [
                    'Aujourd\'hui' => new DateTime('now'),
                    'Demain' => new DateTime('+1 day'),
                    'Dans 1 semaine' => new DateTime('+1 week'),
                    'Dans 1 mois' => new DateTime('+1 month'),
                ],
                'required' => false,
            ])
            ->add('cloture', ChoiceType::class, [
                'label' => 'Commande clôturée',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'required' => false,
            ])
            ->add('affichageTableau', ChoiceType::class, [
                'label' => 'Type de commande',
                'choices' => [
                    'Les deux' => 'les deux',
                    'Commandes individuelle seulement' => 'individuelle',
                    'Commandes groupé seulement' => 'groupé',
                ],
                'required' => false,
                'empty_data' => 'les deux',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'filterAdminCom'],
        ]);
    }
}
