// panel_estudiante/js/mapa_rutas.js

let mapStudent;
let routingControl = null;
const tulcanCoords = [0.8116, -77.7178]; // Coordenadas de Tulcán

function initializeStudentMap() {
    if (mapStudent) {
        mapStudent.remove();
    }

    mapStudent = L.map('map-student').setView(tulcanCoords, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapStudent);

    // Cargar la ruta si hay datos
    if (rutaData && rutaData.coordenadas) {
        try {
            const coords = JSON.parse(rutaData.coordenadas);
            if (Array.isArray(coords) && coords.length >= 2) {
                // Convertir el formato [lat, lon] a L.LatLng para Leaflet Routing Machine
                const waypoints = coords.map(c => L.latLng(c[0], c[1]));

                // Remover el control de ruteo si ya existe
                if (routingControl) {
                    mapStudent.removeControl(routingControl);
                }

                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: true,
                    showAlternatives: false,
                    addWaypoints: false, // No permitir añadir waypoints arrastrando
                    fitSelectedRoutes: true,
                    altLineOptions: {
                        styles: [
                            {color: 'black', opacity: 0.15, weight: 9},
                            {color: 'white', opacity: 0.8, weight: 6},
                            {color: 'blue', opacity: 1, weight: 2}
                        ]
                    },
                    lineOptions: {
                        styles: [
                            {color: 'blue', opacity: 0.8, weight: 5}
                        ]
                    },
                    // Deshabilitar la interfaz de texto de ruteo para usar nuestro panel
                    show: false,
                    collapsible: false,
                    // No hay servicio de ruteo por defecto, usamos el de OpenStreetMap
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1'
                    })
                }).addTo(mapStudent);

                // Obtener y mostrar las instrucciones de la ruta
                routingControl.on('routesfound', function(e) {
                    const routes = e.routes;
                    const instructionsContainer = document.getElementById('instructions-container');
                    const distanceSpan = document.getElementById('distance');
                    const durationSpan = document.getElementById('duration');
                    const instructionsPanel = document.getElementById('instructionsPanel');

                    if (routes.length > 0) {
                        const route = routes[0];
                        instructionsContainer.innerHTML = ''; // Limpiar instrucciones anteriores
                        let ul = document.createElement('ul');
                        ul.className = 'instructions-list';

                        route.instructions.forEach(inst => {
                            let li = document.createElement('li');
                            li.innerHTML = `<i class="fas fa-arrow-right"></i> ${inst.text} (${Math.round(inst.distance / 10) / 100} km)`;
                            ul.appendChild(li);
                        });
                        instructionsContainer.appendChild(ul);
                        instructionsPanel.style.display = 'block'; // Mostrar el panel

                        // Actualizar resumen
                        distanceSpan.textContent = `${(route.summary.totalDistance / 1000).toFixed(2)} km`;
                        durationSpan.textContent = `${Math.round(route.summary.totalTime / 60)} min`;
                    } else {
                        instructionsContainer.innerHTML = '<div class="no-route-selected"><i class="fas fa-route"></i><p>No se encontraron instrucciones para la ruta.</p></div>';
                        instructionsPanel.style.display = 'none'; // Ocultar si no hay instrucciones
                    }
                });

                routingControl.on('routingerror', function(e) {
                    console.error('Error de ruteo:', e.error.message);
                    document.getElementById('instructions-container').innerHTML = '<div class="no-route-selected"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar la ruta. Coordenadas inválidas o problema del servicio de ruteo.</p></div>';
                    document.getElementById('instructionsPanel').style.display = 'block';
                    document.getElementById('distance').textContent = '-- km';
                    document.getElementById('duration').textContent = '-- min';
                });

            } else {
                console.error("Coordenadas de ruta no válidas o insuficientes:", coords);
                document.getElementById('instructions-container').innerHTML = '<div class="no-route-selected"><i class="fas fa-route"></i><p>Las coordenadas de la ruta asignada no son válidas.</p></div>';
                document.getElementById('instructionsPanel').style.display = 'block';
            }
        } catch (e) {
            console.error("Error al parsear coordenadas de ruta:", e);
            document.getElementById('instructions-container').innerHTML = '<div class="no-route-selected"><i class="fas fa-route"></i><p>Error al procesar las coordenadas de la ruta.</p></div>';
            document.getElementById('instructionsPanel').style.display = 'block';
        }
    } else {
        document.getElementById('instructions-container').innerHTML = '<div class="no-route-selected"><i class="fas fa-route"></i><p>No tienes una ruta asignada para mostrar.</p></div>';
        document.getElementById('instructionsPanel').style.display = 'none';
    }
}

// Función para mostrar/ocultar el panel de instrucciones (si lo deseas)
function toggleInstructions() {
    const panel = document.getElementById('instructionsPanel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', initializeStudentMap);