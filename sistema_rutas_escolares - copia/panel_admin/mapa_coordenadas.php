<?php
// panel_admin/mapa_coordenadas.php
// Incluido dentro de panelA.php, así que ya tiene las sesiones y conexión DB.
?>

<h2>Generador de Coordenadas de Ruta</h2>
<p>Haz clic en el mapa para añadir pines. Copia las coordenadas generadas para usarlas en la gestión de rutas.</p>

<div class="map-coordinates-container">
    <div id="map-creator" style="height: 600px; width: 100%;"></div>
    <div class="map-info-panel">
        <h3>Coordenadas de la Ruta</h3>
        <textarea id="coords-output" rows="10" placeholder="Las coordenadas de tus pines aparecerán aquí..." readonly></textarea>
        <button onclick="copyCoordinates()" class="btn-primary"><i class="fas fa-copy"></i> Copiar Coordenadas</button>
        <button onclick="clearMap()" class="btn-secondary"><i class="fas fa-trash-alt"></i> Limpiar Mapa</button>
        <p class="map-hint">Haz clic en el mapa para añadir pines. El orden de los pines es el orden de las coordenadas.</p>
    </div>
</div>