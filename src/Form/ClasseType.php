<?php

namespace App\Form;

use App\Entity\Classe;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelleClasse', TextType::class, [
                'label' => 'Libellé de la classe',
                'required' => true,
            ])
            ->add('codeClasse', TextType::class, [
                'label' => 'Code de la classe',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Classe::class,
            'attr' => ['id' => 'formClasse']
        ]);
    }
}
