<?php

namespace App\Form;

use App\Entity\Unit;
use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints as Assert;

class IngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'ingrédient',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de l\'ingrédient est obligatoire.',
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La quantité est obligatoire.',
                    ]),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'La quantité doit être supérieure à 0.',
                    ]),
                ],
            ])
            ->add('unit', EntityType::class, [
                'class' => Unit::class,
                'label' => 'Unité',
                'choice_label' => 'name',
                'placeholder' => 'Choisir une unité',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ingredient::class,
        ]);
    }
}
