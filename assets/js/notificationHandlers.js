import * as bootstrap from 'bootstrap';

export function showNotification(message, type) {
    let notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    let notificationBody = document.getElementById('notificationModal').querySelector('.modal-body');

    // Injecter le message dans le corps de la modale
    notificationBody.innerHTML = `<div class="alert alert-${type}">${message}</div>`;

    // Afficher la modale
    notificationModal.show();
}