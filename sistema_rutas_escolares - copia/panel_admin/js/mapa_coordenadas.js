// panel_admin/js/mapa_coordenadas.js

let mapCreator;
let markers = L.featureGroup(); // Grupo para manejar los marcadores
let polyline = L.polyline([], {color: 'blue'}).addTo(mapCreator); // Línea que une los marcadores

function initializeMapCreator() {
    // Asegurarse de que el mapa no se inicialice dos veces
    if (mapCreator) {
        mapCreator.remove();
    }

    // Coordenadas de Tulcán, Ecuador
    const tulcanCoords = [0.8116, -77.7178];

    mapCreator = L.map('map-creator').setView(tulcanCoords, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapCreator);

    markers.addTo(mapCreator); // Añadir el grupo de marcadores al mapa
    polyline.addTo(mapCreator); // Añadir la polilínea al mapa

    mapCreator.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        
        // Añadir marcador
        let newMarker = L.marker([lat, lng]).addTo(markers)
            .bindPopup(`Pin: [${lat}, ${lng}]`)
            .openPopup();
        
        // Añadir a la polilínea
        polyline.addLatLng(new L.LatLng(lat, lng));

        updateCoordinatesOutput();
    });
}

function updateCoordinatesOutput() {
    const coordsArray = [];
    markers.eachLayer(function(marker) {
        coordsArray.push([marker.getLatLng().lat, marker.getLatLng().lng]);
    });
    // Formato de salida: [[lat, lng],[lat, lng],...]
    document.getElementById('coords-output').value = JSON.stringify(coordsArray);
}

function copyCoordinates() {
    const coordsOutput = document.getElementById('coords-output');
    if (coordsOutput.value) {
        navigator.clipboard.writeText(coordsOutput.value)
            .then(() => alert("Coordenadas copiadas: " + coordsOutput.value))
            .catch(err => alert("Error al copiar: " + err));
    } else {
        alert("No hay coordenadas para copiar. Haz clic en el mapa para añadir pines.");
    }
}

function clearMap() {
    markers.clearLayers(); // Elimina todos los marcadores
    polyline.setLatLngs([]); // Limpia la polilínea
    document.getElementById('coords-output').value = ''; // Limpia el textarea
    alert("Mapa limpiado. Puedes empezar a añadir nuevos pines.");
}

// Inicializar el mapa cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeMapCreator);