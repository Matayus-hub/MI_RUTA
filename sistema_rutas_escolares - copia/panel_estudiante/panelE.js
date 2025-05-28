// panel_estudiante/panelE.js

function showSection(sectionId) {
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId + '-section').classList.add('active');

    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.menu-item[data-section="${sectionId}"]`).classList.add('active');

    // Si la sección activa es 'mi-ruta', inicializa el mapa
    if (sectionId === 'mi-ruta') {
        initializeMap();
    }
}

// Función para cerrar sesión
async function logout() {
    try {
        const response = await fetch('../panel_admin/panelA.php?action=logout'); // Usamos la acción de logout del panel de administración
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '../index.php'; // Redirigir al login
        } else {
            alert('Error al cerrar sesión: ' + (result.message || result.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        alert('Ocurrió un error al intentar cerrar sesión.');
    }
}

let map = null; // Variable para almacenar la instancia del mapa

function initializeMap() {
    // Solo inicializa el mapa si no existe
    if (map !== null) {
        map.remove(); // Elimina el mapa existente antes de crear uno nuevo
    }

    // Inicializa el mapa solo si routeCoordinates está disponible y es un array válido
    if (routeCoordinates && Array.isArray(routeCoordinates) && routeCoordinates.length > 0) {
        map = L.map('mapid').setView(routeCoordinates[0], 13); // Centra en la primera coordenada de la ruta

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Añadir las coordenadas de la ruta al mapa
        L.polyline(routeCoordinates, { color: 'blue' }).addTo(map);

        // Opcional: ajustar el zoom para que toda la ruta sea visible
        map.fitBounds(L.polyline(routeCoordinates).getBounds());
    } else {
        // Si no hay coordenadas, muestra un mensaje o un mapa por defecto
        const mapDiv = document.getElementById('mapid');
        if (mapDiv) {
            mapDiv.innerHTML = '<p>No hay datos de ruta disponibles para mostrar el mapa.</p>';
            mapDiv.style.display = 'flex';
            mapDiv.style.justifyContent = 'center';
            mapDiv.style.alignItems = 'center';
        }
    }
}

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    showSection('mi-ruta'); // Muestra la sección de mi ruta por defecto
});