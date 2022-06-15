<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OubliMdpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailFirst', EmailType::class, [
                'label' => 'Votre adresse email',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un email !',
                    ]),
                ],
                'invalid_message' => 'Votre saisie n\'est pas un email !'
            ])
            ->add('dateAnniversaire', DateType::class, [
                'label' => 'Votre date de naissance',
                'html5' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'help' => ' format : JJ/MM/AAAA',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre date de naissance !',
                    ]),
                ],
                'invalid_message' => 'Votre saisie n\'est pas une date !',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['id' => 'formOubliMdp']
        ]);
    }
}
