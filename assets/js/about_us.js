import '../styles/site/about_us.scss';
let map;

// Initialisation de la carte
async function initMap() {
    // Charger la bibliothèque de repères avancés
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

    // Créer la carte centrée sur Paris (ou tout autre emplacement par défaut)
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 50.35, lng: 3.5333 }, // Coordonnées de Valenciennes
        zoom: 10,
        mapId: 'DEMO_MAP_ID' // Utilisation d'un Map ID (tu peux créer le tien dans la console Google Maps)
    });

    // Récupérer les points de distribution via l'API
    fetch('/api/distribution-points')
        .then(response => response.json())
        .then(data => {
            console.log('Points de distribution récupérés :', data);

            // Géocoder chaque adresse et ajouter un repère
            data.forEach(point => {
                console.log('Adresse à géocoder :', point.address);
                geocodeAddress(point, AdvancedMarkerElement); // Passer AdvancedMarkerElement
            });
        })
        .catch(error => console.error('Erreur lors de la récupération des points de distribution :', error));
}

// Fonction pour géocoder une adresse et placer un repère sur la carte
function geocodeAddress(point, AdvancedMarkerElement) {
    const geocoder = new google.maps.Geocoder();

    // Géocoder l'adresse
    geocoder.geocode({ address: point.address }, function(results, status) {
        if (status === 'OK') {
            console.log('Résultat du géocodage :', results);

            // Créer un élément DOM pour le contenu du repère
            const customMarker = document.createElement('div');
            customMarker.className = 'custom-marker';

            const markerImage = document.createElement('img');
            markerImage.src = '/favicon/shroom-marker-min.png';
            markerImage.alt = 'Custom Icon';
            markerImage.style.width = '50px'; // Définir la largeur de l'image
            markerImage.style.height = '50px'; // Définir la hauteur de l'image

            // Ajouter l'image au repère personnalisé
            customMarker.appendChild(markerImage);

            // Ajouter un repère avancé avec un contenu DOM
            const marker = new AdvancedMarkerElement({
                map: map,
                position: results[0].geometry.location,
                title: point.name,
                content: customMarker // Utilisation de l'élément DOM créé pour le contenu
            });

            // Créer une fenêtre d'informations pour le repère
            const infoWindow = new google.maps.InfoWindow({
                content: `<h5>${point.name}</h5><p>${point.description}</p><p>${point.address}</p>`
            });

            // Ouvrir la fenêtre d'informations au clic sur le repère
            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
        } else {
            console.error('Erreur de géocodage pour l\'adresse : ' + point.address + ' - Statut : ' + status);
        }
    });
}

// Appel de la fonction d'initialisation lors du chargement de la page
window.initMap = initMap;