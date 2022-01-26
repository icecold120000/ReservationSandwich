<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OubliMdpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailFirst', EmailType::class,[
                'label' => 'Votre adresse e-mail',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un e-mail !',
                    ]),
                ],
            ])
            ->add('dateAnniversaire', DateTimeType::class, [
                'label' => 'Votre date d\'anniversaire',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'help' => ' format : JJ/MM/AAAA',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre mot de passe !',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'formOubliMdp']
        ]);
    }
}
