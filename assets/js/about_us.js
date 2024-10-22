import '../styles/site/about_us.scss';
let map;

// Initialisation de la carte
async function initMap() {
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

    // Créer la carte centrée sur Valenciennes
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 50.35, lng: 3.5333 }, // Coordonnées de Valenciennes
        zoom: 10,
        mapId: 'DEMO_MAP_ID' 
    });

    fetch('/api/distribution-points')
        .then(response => response.json())
        .then(data => {
            console.log('Points de distribution récupérés :', data);

            // Afficher les points sur la carte
            data.forEach(point => {
                console.log('Adresse à géocoder :', point.address);
                geocodeAddress(point, AdvancedMarkerElement); 
            });

            // Charger les points sous forme de cartes avec pagination
            loadDistributionCards(data);
        })
        .catch(error => console.error('Erreur lors de la récupération des points de distribution :', error));
}

// Fonction pour géocoder une adresse et placer un repère sur la carte
function geocodeAddress(point, AdvancedMarkerElement) {
    const geocoder = new google.maps.Geocoder();

    geocoder.geocode({ address: point.address }, function (results, status) {
        if (status === 'OK') {
            console.log('Résultat du géocodage :', results);

            const customMarker = document.createElement('div');
            customMarker.className = 'custom-marker';

            const markerImage = document.createElement('img');
            markerImage.src = '/favicon/shroom-marker-min.png';
            markerImage.alt = 'Custom Icon';
            markerImage.style.width = '50px'; 
            markerImage.style.height = '50px';

            customMarker.appendChild(markerImage);

            const marker = new AdvancedMarkerElement({
                map: map,
                position: results[0].geometry.location,
                title: point.name,
                content: customMarker
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<h5>${point.name}</h5><p class="h6">${point.type}</p><p>${point.description}</p><p>${point.address}</p><a href="${point.site}" class="btn btn-blue" target="_blank">Site internet</a>`
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
        } else {
            console.error('Erreur de géocodage pour l\'adresse : ' + point.address + ' - Statut : ' + status);
        }
    });
}

// Fonction pour charger et afficher les points de distribution sous forme de cartes avec pagination et filtres
function loadDistributionCards(data) {
    const grid = document.getElementById('distribution-grid');
    const paginationContainer = document.getElementById('pagination');
    const itemsPerPage = 9;
    let currentPage = 1;
    let filteredData = [...data];

    // Fonction pour afficher les cartes avec pagination
    function displayCards(page = 1) {
        grid.innerHTML = ''; // Vider la grille
        paginationContainer.innerHTML = ''; // Vider la pagination

        // Calculer les index de début et de fin pour la pagination
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedData = filteredData.slice(startIndex, endIndex);

        if (paginatedData.length === 0) {
            grid.innerHTML = '<p>Aucun résultat ne correspond à votre recherche.</p>';
            return;
        }

        paginatedData.forEach(point => {
            const card = document.createElement('div');
            card.classList.add('col-md-4', 'mb-4');
            card.innerHTML = `
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title h3 bg-text fw-bold">${point.name}</h5>
                        <p class="h6 fw-bold">Type: ${point.type || 'Non spécifié'}</p>
                        <p class="card-text">${point.description}</p>
                        <p><strong>Adresse:</strong> ${point.address}</p>
                        <a href="${point.site}" class="btn btn-blue fw-bold">Visiter le site</a>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });

        createPagination(filteredData.length, page);
    }

    // Fonction pour créer les boutons de pagination
    function createPagination(totalItems, currentPage) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);

        const prevButton = document.createElement('button');
        prevButton.textContent = 'Précédent';
        prevButton.classList.add('btn', 'btn-light', 'm-0');
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener('click', () => {
            displayCards(currentPage - 1);
        });
        paginationContainer.appendChild(prevButton);

        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.classList.add('btn', 'btn-light', 'm-0', 'rounded-0');
            if (i === currentPage) {
                pageButton.classList.add('btn-blue'); 
            }
            pageButton.addEventListener('click', () => {
                displayCards(i);
            });
            paginationContainer.appendChild(pageButton);
        }

        const nextButton = document.createElement('button');
        nextButton.textContent = 'Suivant';
        nextButton.classList.add('btn', 'btn-light', 'm-0');
        nextButton.disabled = currentPage === totalPages;
        nextButton.addEventListener('click', () => {
            displayCards(currentPage + 1);
        });
        paginationContainer.appendChild(nextButton);
    }

    // Fonction pour filtrer les données par type
    function filterData(type) {
        if (type === 'all') {
            filteredData = [...data];
        } else {
            filteredData = data.filter(point => {
                if (point.type) {
                    return point.type.trim().toLowerCase() === type.toLowerCase();
                }
                return false;
            });
        }
        currentPage = 1; 
        displayCards(currentPage);
    }

    // Ajouter des événements de clic pour les boutons de filtrage
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const type = button.getAttribute('data-type');
            filterData(type);
        });
    });

    displayCards(currentPage);
}

window.initMap = initMap;