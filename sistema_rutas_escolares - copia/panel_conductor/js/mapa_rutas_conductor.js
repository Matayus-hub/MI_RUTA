// panel_conductor/js/mapa_rutas_conductor.js

let mapConductor;
let routingControlConductor = null;
const tulcanCoordsConductor = [0.8116, -77.7178]; // Coordenadas de Tulcán

function initializeConductorMap() {
    if (mapConductor) {
        mapConductor.remove();
    }

    mapConductor = L.map('map-conductor').setView(tulcanCoordsConductor, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapConductor);

    // Cargar la ruta si hay datos
    if (rutaDataConductor && rutaDataConductor.coordenadas) {
        try {
            const coords = JSON.parse(rutaDataConductor.coordenadas);
            if (Array.isArray(coords) && coords.length >= 2) {
                const waypoints = coords.map(c => L.latLng(c[0], c[1]));

                if (routingControlConductor) {
                    mapConductor.removeControl(routingControlConductor);
                }

                routingControlConductor = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false, // El conductor no debe modificar la ruta
                    showAlternatives: false,
                    addWaypoints: false,
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
                    show: false, // No mostrar el panel de ruteo por defecto
                    collapsible: false,
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1'
                    })
                }).addTo(mapConductor);

                routingControlConductor.on('routesfound', function(e) {
                    const routes = e.routes;
                    const instructionsContainer = document.getElementById('instructions-container');
                    const distanceSpan = document.getElementById('distance');
                    const durationSpan = document.getElementById('duration');
                    const instructionsPanel = document.getElementById('instructionsPanel');

                    if (routes.length > 0) {
                        const route = routes[0];
                        instructionsContainer.innerHTML = '';
                        let ul = document.createElement('ul');
                        ul.className = 'instructions-list';

                        route.instructions.forEach(inst => {
                            let li = document.createElement('li');
                            li.innerHTML = `<i class="fas fa-arrow-right"></i> ${inst.text} (${Math.round(inst.distance / 10) / 100} km)`;
                            ul.appendChild(li);
                        });
                        instructionsContainer.appendChild(ul);
                        instructionsPanel.style.display = 'block';

                        distanceSpan.textContent = `${(route.summary.totalDistance / 1000).toFixed(2)} km`;
                        durationSpan.textContent = `${Math.round(route.summary.totalTime / 60)} min`;
                    } else {
                        instructionsContainer.innerHTML = '<div class="no-route-selected"><i class="fas fa-route"></i><p>No se encontraron instrucciones para la ruta.</p></div>';
                        instructionsPanel.style.display = 'none';
                    }
                });

                routingControlConductor.on('routingerror', function(e) {
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


document.addEventListener('DOMContentLoaded', initializeConductorMap);