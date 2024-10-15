<?php
namespace App\Form;

use App\Entity\Recipe;
use App\Form\IngredientType;
use App\Form\RecipeStepType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints as Assert;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre est obligatoire.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'empty_data' => '', 
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est obligatoire.',
                    ]),
                ],
                'empty_data' => '', 
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'label' => 'Image de votre recette',
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou WEBP).',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 2 Mo.',
                    ]),
                ],
            ])
            ->add('difficulty', HiddenType::class, [
                
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La difficulté est obligatoire.',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'La difficulté doit être comprise entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('nbGuest', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nombre de personnes est obligatoire.',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Le nombre de personnes doit être au moins {{ compared_value }}.',
                    ]),
                ],
            ])
            ->add('preparation_time', IntegerType::class, [
                'label' => 'Temps de préparation',
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le temps de préparation ne peut pas être négatif.',
                    ]),
                ],
            ])
            ->add('cooking_time', IntegerType::class, [
                'label' => 'Temps de cuisson',
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le temps de cuisson ne peut pas être négatif.',
                    ]),
                ],
            ])
            ->add('rest_time', IntegerType::class, [
                'label' => 'Temps de repos',
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le temps de repos ne peut pas être négatif.',
                    ]),
                ],
            ])
            ->add('ingredients', CollectionType::class, [
                'entry_type' => IngredientType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'label' => false,
            ])
            ->add('steps', CollectionType::class, [
                'entry_type' => RecipeStepType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'label' => false,
                'constraints' => [new Assert\Valid()]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
            'validation_groups' => ['create', 'update'], 
        ]);
    }
}