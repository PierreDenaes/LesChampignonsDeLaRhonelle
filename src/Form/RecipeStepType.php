<?php

namespace App\Form;

use App\Entity\RecipeStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RecipeStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stepNumber', IntegerType::class, [
                'label' => 'Étape',
                'attr' => ['readonly' => true],
            ])
            ->add('stepDescription', TextareaType::class, [
                'label' => 'Description de l\'étape',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description de l\'étape est obligatoire.',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit faire au moins {{ limit }} caractères.',
                    ]),
                ],
                'empty_data' => '', 
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RecipeStep::class,
            'validation_groups' => ['create', 'update'],
        ]);
    }
}