// formHandlers.js
export function handleAddIngredient() {
    document.querySelectorAll('.ingredient-container').forEach(container => {
        const list = container.querySelector('.ingredient-list');
        const addButton = container.querySelector('.add-ingredient');
        const newWidget = list.dataset.prototype;
        let index = list.children.length;

        addButton.addEventListener('click', () => {
            const newLi = document.createElement('li');
            newLi.classList.add('list-group-item');
            newLi.innerHTML = newWidget.replace(/__name__/g, index) + '<button type="button" class="remove-ingredient btn btn-danger mt-1">Supprimer</button>';
            list.appendChild(newLi);
            index++;
        });

        list.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-ingredient')) {
                e.target.parentElement.remove();
            }
        });
    });
}

export function handleAddStep() {
    document.querySelectorAll('.step-container').forEach(container => {
        const list = container.querySelector('.step-list');
        const addButton = container.querySelector('.add-step');
        const newWidget = list.dataset.prototype;
        let index = list.children.length;

        addButton.addEventListener('click', () => {
            const newLi = document.createElement('li');
            newLi.classList.add('list-group-item');
            newLi.innerHTML = newWidget.replace(/__name__/g, index) + '<button type="button" class="remove-step btn btn-danger mt-1">Supprimer</button>';
            list.appendChild(newLi);
            index++;
        });

        list.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-step')) {
                e.target.parentElement.remove();
            }
        });
    });
}