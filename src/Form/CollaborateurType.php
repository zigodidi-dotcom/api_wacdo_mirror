<?php

namespace App\Form;

use App\Entity\Collaborateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollaborateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'documentation' => [
                    'type' => 'string',
                    'description' => 'all mamamn bobobo'
                ]
            ])
            ->add('roles', ChoiceType::class, array(
                        'attr'  =>  array('class' => 'form-control',
                        'style' => 'margin:5px 0;'),
                        'choices' =>
                                array
                                (
                                    'ROLE_ADMIN' => array
                                    (
                                        'Yes' => 'ROLE_ADMIN',
                                    ),
                                    'ROLE_TEACHER' => array
                                    (
                                        'Yes' => 'ROLE_TEACHER'
                                    ),
                                    'ROLE_STUDENT' => array
                                    (
                                        'Yes' => 'ROLE_STUDENT'
                                    ),
                                    'ROLE_PARENT' => array
                                    (
                                        'Yes' => 'ROLE_PARENT'
                                    ),
                                ),
                    'multiple' => true,
                    'required' => true,
                )
            )

            ->add('password')
            ->add('prenom')
            ->add('nom')
            ->add('dateembauche', DateTimeType::class, [
                'html5' => true,
                'attr'  => [
                        'min' => (new \DateTime())->format('Y-m-d\TH:i'), // Optionnel : date minimale
                        ],
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collaborateur::class,
            "csrf_protection" => false,
        ]);
    }
}
