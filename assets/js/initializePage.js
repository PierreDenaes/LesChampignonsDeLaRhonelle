import { handleAddIngredient, handleAddStep } from './formHandlers';
import { loadRecipes, viewRecipe, attachDeleteHandlers, editRecipe } from './recipeHandlers';
import { showNotification } from './notificationHandlers';
import { Tab } from 'bootstrap';

export function initializePage() {
    const recipesList = document.getElementById('recipes-list');
    const recipeFormNew = document.getElementById('recipe-form-new');

    loadRecipes(recipesList, attachDeleteHandlers);

    window.viewRecipe = viewRecipe;
    window.editRecipe = editRecipe;

    handleDifficultyIcons();

    recipeFormNew.addEventListener('submit', function(event) {
        event.preventDefault();

        // Remove old errors
        const errorDivs = document.querySelectorAll('.invalid-feedback');
        errorDivs.forEach(div => div.remove());

        const fields = document.querySelectorAll('.is-invalid');
        fields.forEach(field => field.classList.remove('is-invalid'));

        // Create FormData before use
        const formData = new FormData(recipeFormNew);

        // Update step numbers if necessary before submission
        document.querySelectorAll('.step-list').forEach(list => {
            const steps = list.querySelectorAll('li');
            steps.forEach((step, index) => {
                const stepNumberInput = step.querySelector('input[name*="[stepNumber]"]');
                if (stepNumberInput) {
                    stepNumberInput.value = index + 1;  // Correct step numbers
                }
            });
        });

        const url = recipeFormNew.action;

        fetch(url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resetForm(recipeFormNew); // Reset the form
                showNotification(data.message, 'success'); // Show success notification
        
                // Reload recipes and attach delete handlers
                loadRecipes(recipesList, attachDeleteHandlers);
        
                // Change active tab to recipe list
                const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                tabTrigger.show();
            } else if (data.errors) {
                console.log(data.errors);
                // Loop through each error field
                Object.keys(data.errors).forEach(field => {
                    // Ignorer les erreurs globales sur "name" et "quantity"
                    if (field === 'name' || field === 'quantity' || field === 'stepDescription') {
                        return; // Passer à l'erreur suivante
                    }
        
                    // Vérifier si le champ est un ingrédient ou une étape avec un index
                    if (field.startsWith('ingredients[') || field.startsWith('steps[')) {
                        const parts = field.match(/^(\w+)\[(\d+)\]\.(\w+)$/);
                        if (parts) {
                            const fieldType = parts[1];  // 'ingredients' or 'steps'
                            const fieldIndex = parts[2]; // Collection index
                            const fieldName = parts[3];  // Field name
        
                            // Sélectionner le champ correspondant
                            const inputField = recipeFormNew.querySelector(`[name="recipe[${fieldType}][${fieldIndex}][${fieldName}]"]`);
        
                            if (inputField) {
                                const errorId = `error-${fieldType}-${fieldIndex}-${fieldName}`;
        
                                // Supprimer le message d'erreur existant
                                let existingError = document.getElementById(errorId);
                                if (existingError) {
                                    existingError.remove();
                                }
        
                                // Créer et ajouter le message d'erreur
                                const errorDiv = document.createElement('div');
                                errorDiv.classList.add('invalid-feedback');
                                errorDiv.setAttribute('id', errorId);  // Assigner un ID unique pour l'erreur
                                errorDiv.innerText = data.errors[field].join(', ');
                                inputField.classList.add('is-invalid');  // Marquer le champ comme invalide
                                inputField.insertAdjacentElement('afterend', errorDiv);  // Insérer le message d'erreur après le champ input
                            }
                        }
                    } else {
                        // Gérer les erreurs globales (non imbriquées)
                        const inputField = recipeFormNew.querySelector(`[name*="[${field}]"]`);
                        if (inputField) {
                            const errorId = `error-${field}-global`;
        
                            // Supprimer l'erreur existante
                            let existingError = document.getElementById(errorId);
                            if (existingError) {
                                existingError.remove();
                            }
        
                            // Créer et ajouter le message d'erreur
                            const errorDiv = document.createElement('div');
                            errorDiv.classList.add('invalid-feedback');
                            errorDiv.setAttribute('id', errorId);
                            errorDiv.innerText = data.errors[field].join(', ');
                            inputField.classList.add('is-invalid');
                            inputField.insertAdjacentElement('afterend', errorDiv);
                        }
                    }
                });
            } else {
                showNotification('Erreur lors de la création de la recette.', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la soumission:', error);
            showNotification('Erreur technique. Réessayez plus tard.', 'danger');
        });
        
    });

    handleAddIngredient();
    handleAddStep();
}
// Fonction pour réinitialiser le formulaire et ses éléments dynamiques
function resetForm(form) {
    form.reset();

    // Réinitialiser les listes d'ingrédients et d'étapes
    document.querySelectorAll('.ingredient-list').forEach(list => {
        list.innerHTML = ''; // Vider la liste des ingrédients
    });
    document.querySelectorAll('.step-list').forEach(list => {
        list.innerHTML = ''; // Vider la liste des étapes
    });

    // Réinitialiser les gestionnaires d'événements
    handleAddIngredient();
    handleAddStep();
}
// Fonction pour gérer la sélection des niveaux de difficulté
function handleDifficultyIcons() {
    document.querySelectorAll('.icon-difficulty').forEach((icon) => {
        icon.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            
            // Mettre à jour l'input caché avec la valeur choisie
            const difficultyInput = document.getElementById('recipe_difficulty');
            difficultyInput.value = value;
            
            
            // Déclencher l'événement 'change' sur l'input pour que la validation Symfony soit activée
            difficultyInput.dispatchEvent(new Event('change'));

            // Mettre à jour les icônes sélectionnées
            document.querySelectorAll('.icon-difficulty').forEach((icon) => {
                icon.classList.remove('selected');
            });
            for (let i = 0; i < value; i++) {
                document.querySelectorAll('.icon-difficulty')[i].classList.add('selected');
            }
        });
    });
}

// Appeler la fonction pour gérer les icônes de difficulté lors de l'initialisation
handleDifficultyIcons();