<?php

namespace App\Form;

use App\Entity\Profile;
use App\Entity\Rating;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('score', ChoiceType::class, [
                'choices' => [
                    '0.5 Champignon' => 0.5,
                    '1 Champignon' => 1,
                    '1.5 Champignon' => 1.5,
                    '2 Champignons' => 2,
                    '2.5 Champignons' => 2.5,
                    '3 Champignons' => 3,
                    '3.5 Champignons' => 3.5,
                    '4 Champignons' => 4,
                    '4.5 Champignons' => 4.5,
                    '5 Champignons' => 5,
                ],
                'expanded' => true, // Utilisé pour afficher comme des boutons radio
                'multiple' => false, // Une seule note peut être sélectionnée
            ])
            ->add('recipe', EntityType::class, [
                'class' => Recipe::class,
                'choice_label' => 'id',
            ])
            ->add('profile', EntityType::class, [
                'class' => Profile::class,
                'choice_label' => 'id',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}