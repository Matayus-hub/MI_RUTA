// panel_conductor/panelC.js

// Función para mostrar la sección activa
function showSection(sectionId) {
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId + '-section').classList.add('active');

    // Resaltar el elemento de menú activo
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.menu-item[data-section="${sectionId}"]`).classList.add('active');

    // Inicializar mapas si la sección es 'mis-rutas'
    if (sectionId === 'mis-rutas') {
        initializeMaps(assignedRoutesData);
    }
}

// Función para cerrar sesión
async function logout() {
    try {
        const response = await fetch('../procesar_auth.php?action=logout'); // Asumiendo que procesar_auth.php maneja el logout
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '../index.php'; // Redirigir al login
        } else {
            alert('Error al cerrar sesión: ' + (result.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        alert('Error de red o servidor al intentar cerrar sesión.');
    }
}

// Función para inicializar los mapas (ejecutar cuando se carga la página y al mostrar la sección)
function initializeMaps(routes) {
    routes.forEach(route => {
        const mapId = `mapid-${route.id}`;
        const mapContainer = document.getElementById(mapId);

        if (mapContainer && !mapContainer._leaflet_id) { // Solo inicializar si no ha sido inicializado
            const coordinatesArray = parseCoordinates(route.coordenadas);

            if (coordinatesArray.length > 0) {
                const map = L.map(mapId).setView(coordinatesArray[0], 13); // Centrar en la primera coordenada

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Añadir un marcador al inicio y al final de la ruta (opcional)
                L.marker(coordinatesArray[0]).addTo(map)
                    .bindPopup(`<b>Inicio de Ruta:</b> ${route.nombre}`)
                    .openPopup();

                L.marker(coordinatesArray[coordinatesArray.length - 1]).addTo(map)
                    .bindPopup(`<b>Fin de Ruta:</b> ${route.nombre}`);

                // Dibujar la polilínea de la ruta
                L.polyline(coordinatesArray, { color: 'blue' }).addTo(map);

                // Ajustar el mapa para que se ajusten todos los puntos de la ruta
                map.fitBounds(L.polyline(coordinatesArray).getBounds());
            } else {
                console.warn(`No hay coordenadas válidas para la ruta ${route.nombre} (ID: ${route.id}).`);
                mapContainer.innerHTML = '<p>No hay coordenadas disponibles para esta ruta.</p>';
            }
        }
    });
}

// Función auxiliar para parsear las coordenadas (maneja el formato de string en la BD)
function parseCoordinates(coordString) {
    try {
        // Eliminar los corchetes exteriores y dividir por '],[' para obtener pares de lat/lon
        const cleanedString = coordString.replace(/^\[|\]$/g, '');
        const pairs = cleanedString.split('],[');
        const parsedCoords = pairs.map(pair => {
            const [lon, lat] = pair.split(',').map(Number); // Asegurarse de que son números
            return [lat, lon]; // Leaflet espera [lat, lon]
        });
        return parsedCoords;
    } catch (e) {
        console.error("Error al parsear las coordenadas:", coordString, e);
        return [];
    }
}


// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Mostrar la sección de Mis Rutas por defecto y cargar sus datos
    showSection('mis-rutas');
});

// Listener para cuando se cambia de sección, asegurar que el mapa se inicialice correctamente
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', (event) => {
        const sectionId = event.target.dataset.section;
        showSection(sectionId);
    });
});