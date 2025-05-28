// panel_admin/panelA.js

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

    // Ocultar todos los formularios de añadir
    hideAllAddForms();
}

function hideAllAddForms() {
    document.getElementById('add-ruta-form').style.display = 'none';
    document.getElementById('add-horario-form').style.display = 'none';
    document.getElementById('add-estudiante-form').style.display = 'none';
    document.getElementById('add-conductor-form').style.display = 'none';
    document.getElementById('add-vehiculo-form').style.display = 'none';
    document.getElementById('add-usuario-form').style.display = 'none';
}

// --- Funciones de Fetch para obtener datos ---

async function fetchData(action) {
    try {
        const response = await fetch(`panelA.php?action=${action}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.error) {
            alert("Error al cargar datos: " + data.error);
            return [];
        }
        return data;
    } catch (error) {
        console.error(`Error fetching ${action}:`, error);
        alert(`No se pudieron cargar los datos de ${action}. Verifique la conexión o el servidor.`);
        return [];
    }
}

// Función para enviar datos (adaptada para FormData cuando es necesario)
async function postData(action, data) {
    let bodyContent;
    let headersContent = {};

    // Si data es una instancia de FormData, el navegador establecerá Content-Type
    if (data instanceof FormData) {
        bodyContent = data;
        // No establecer Content-Type, el navegador lo hará automáticamente para multipart/form-data
    } else {
        bodyContent = JSON.stringify(data);
        headersContent['Content-Type'] = 'application/json';
    }

    try {
        const response = await fetch(`panelA.php?action=${action}`, {
            method: 'POST',
            headers: headersContent,
            body: bodyContent,
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        if (result.error) {
            alert("Error: " + result.error);
            return { success: false, message: result.error };
        }
        return result;
    } catch (error) {
        console.error(`Error posting ${action}:`, error);
        alert(`No se pudo guardar la información de ${action}.`);
        return { success: false, message: "Error de red o servidor." };
    }
}


// --- Funciones para cargar y mostrar datos en tablas ---

async function fetchRutas() {
    const rutas = await fetchData('rutas');
    const tbody = document.querySelector('#rutas-table tbody');
    tbody.innerHTML = ''; // Limpiar tabla
    rutas.forEach(ruta => {
        const row = tbody.insertRow();
        row.insertCell().textContent = ruta.id;
        row.insertCell().textContent = ruta.nombre;
        row.insertCell().textContent = ruta.descripcion;
        row.insertCell().textContent = ruta.coordenadas; // Esto podría ser un JSON, considera formatearlo
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editRuta('${ruta.id}')">Editar</button> <button onclick="deleteRuta('${ruta.id}')">Eliminar</button>`;
    });
}

async function fetchHorarios() {
    const horarios = await fetchData('horarios');
    const tbody = document.querySelector('#horarios-table tbody');
    tbody.innerHTML = '';
    horarios.forEach(horario => {
        const row = tbody.insertRow();
        row.insertCell().textContent = horario.id; // Asumiendo que 'id' es autoincremental
        row.insertCell().textContent = horario.id_ruta;
        row.insertCell().textContent = horario.escuela; // Coincide con tu DB
        row.insertCell().textContent = horario.id_vehiculo; // Coincide con tu DB
        row.insertCell().textContent = horario.id_conductor; // Coincide con tu DB
        row.insertCell().textContent = horario.hora_salida;
        row.insertCell().textContent = horario.hora_llegada;
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editHorario(${horario.id})">Editar</button> <button onclick="deleteHorario(${horario.id})">Eliminar</button>`;
    });
}

async function fetchEstudiantes() {
    const estudiantes = await fetchData('estudiantes');
    const tbody = document.querySelector('#estudiantes-table tbody');
    tbody.innerHTML = '';
    estudiantes.forEach(estudiante => {
        const row = tbody.insertRow();
        row.insertCell().textContent = estudiante.cedula;
        row.insertCell().textContent = estudiante.nombre;
        row.insertCell().textContent = estudiante.institucion;
        row.insertCell().textContent = estudiante.correo;
        row.insertCell().textContent = estudiante.id_ruta_asignada; // Coincide con tu DB
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editEstudiante('${estudiante.cedula}')">Editar</button> <button onclick="deleteEstudiante('${estudiante.cedula}')">Eliminar</button>`;
    });
}

async function fetchConductores() {
    const conductores = await fetchData('conductores');
    const tbody = document.querySelector('#conductores-table tbody');
    tbody.innerHTML = '';
    conductores.forEach(conductor => {
        const row = tbody.insertRow();
        row.insertCell().textContent = conductor.id;
        row.insertCell().textContent = conductor.nombre;
        row.insertCell().textContent = conductor.licencia;
        row.insertCell().textContent = conductor.telefono; // Coincide con tu DB
        row.insertCell().textContent = conductor.id_vehiculo_asignado; // Coincide con tu DB
        row.insertCell().textContent = conductor.id_ruta_asignada; // Coincide con tu DB
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editConductor(${conductor.id})">Editar</button> <button onclick="deleteConductor(${conductor.id})">Eliminar</button>`;
    });
}

async function fetchVehiculos() {
    const vehiculos = await fetchData('vehiculos');
    const tbody = document.querySelector('#vehiculos-table tbody');
    tbody.innerHTML = '';
    vehiculos.forEach(vehiculo => {
        const row = tbody.insertRow();
        row.insertCell().textContent = vehiculo.id;
        row.insertCell().textContent = vehiculo.placa;
        row.insertCell().textContent = vehiculo.modelo;
        row.insertCell().textContent = vehiculo.capacidad;
        row.insertCell().textContent = vehiculo.estado;
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editVehiculo('${vehiculo.id}')">Editar</button> <button onclick="deleteVehiculo('${vehiculo.id}')">Eliminar</button>`;
    });
}

async function fetchUsuarios() {
    const usuarios = await fetchData('usuarios');
    const tbody = document.querySelector('#usuarios-table tbody');
    tbody.innerHTML = '';
    usuarios.forEach(usuario => {
        const row = tbody.insertRow();
        row.insertCell().textContent = usuario.id;
        row.insertCell().textContent = usuario.username; // Usar username de la tabla usuarios
        row.insertCell().textContent = usuario.rol;
        row.insertCell().textContent = usuario.email; // Usar email de la tabla usuarios
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = `<button onclick="editUsuario(${usuario.id})">Editar</button> <button onclick="deleteUsuario(${usuario.id})">Eliminar</button>`;
    });
}

// --- Funciones para añadir elementos (Formularios) ---

function showAddRutaForm() {
    hideAllAddForms();
    document.getElementById('add-ruta-form').style.display = 'block';
}

async function addRuta(event) {
    event.preventDefault();
    const id = document.getElementById('ruta-id').value;
    const nombre = document.getElementById('ruta-nombre').value;
    const descripcion = document.getElementById('ruta-descripcion').value;
    const coordenadas = document.getElementById('ruta-coordenadas').value;

    // Validación de JSON para coordenadas: DEBE ser un JSON Array de arrays
    try {
        const parsedCoords = JSON.parse(coordenadas);
        if (!Array.isArray(parsedCoords) || parsedCoords.some(c => !Array.isArray(c) || c.length !== 2)) {
            alert("Las coordenadas deben ser un JSON Array de pares [lat, lon] válidos (Ej: [[0.123, -78.456], [0.789, -77.123]] ).");
            return false;
        }
    } catch (e) {
        alert("Las coordenadas deben ser un JSON Array válido. Error: " + e.message);
        return false;
    }

    const result = await postData('rutas', { id, nombre, descripcion, coordenadas });
    if (result.success) {
        alert('Ruta agregada exitosamente!');
        document.getElementById('add-ruta-form').style.display = 'none';
        fetchRutas(); // Refrescar la tabla
    } else {
        alert('Error al agregar ruta: ' + (result.message || 'Error desconocido'));
    }
    return false; // Evita el envío tradicional del formulario
}

function showAddHorarioForm() {
    hideAllAddForms();
    document.getElementById('add-horario-form').style.display = 'block';
}

async function addHorario(event) {
    event.preventDefault();
    const id_ruta = document.getElementById('horario-id-ruta').value;
    const escuela = document.getElementById('horario-escuela').value; // Coincide con tu DB
    const id_vehiculo = document.getElementById('horario-id-vehiculo').value; // Coincide con tu DB
    const id_conductor = document.getElementById('horario-id-conductor').value; // Nuevo campo
    const hora_salida = document.getElementById('horario-salida').value;
    const hora_llegada = document.getElementById('horario-llegada').value;

    const result = await postData('horarios', { id_ruta, escuela, id_vehiculo, id_conductor, hora_salida, hora_llegada });
    if (result.success) {
        alert('Horario agregado exitosamente!');
        document.getElementById('add-horario-form').style.display = 'none';
        fetchHorarios();
    } else {
        alert('Error al agregar horario: ' + (result.message || 'Error desconocido'));
    }
    return false;
}

function showAddEstudianteForm() {
    hideAllAddForms();
    document.getElementById('add-estudiante-form').style.display = 'block';
}

async function addEstudiante(event) {
    event.preventDefault();
    const formData = new FormData(event.target); // Captura todos los campos del formulario, incluyendo files

    // Para la contraseña, necesitas obtenerla y hashearla en el backend
    // Aquí solo asegúrate de que el campo exista
    const passwordInput = document.getElementById('estudiante-password');
    if (passwordInput && passwordInput.value) {
        formData.append('contrasena', passwordInput.value); // Añadir contraseña al FormData
    }

    const result = await postData('estudiantes', formData); // Enviar FormData
    if (result.success) {
        alert('Estudiante agregado exitosamente!');
        document.getElementById('add-estudiante-form').style.display = 'none';
        document.getElementById('add-estudiante-form').reset(); // Limpiar el formulario
        fetchEstudiantes();
    } else {
        alert('Error al agregar estudiante: ' + (result.message || 'Error desconocido'));
    }
    return false;
}

function showAddConductorForm() {
    hideAllAddForms();
    document.getElementById('add-conductor-form').style.display = 'block';
}

async function addConductor(event) {
    event.preventDefault();
    const formData = new FormData(event.target); // Captura todos los campos del formulario, incluyendo files

    // Para la contraseña, necesitas obtenerla y hashearla en el backend
    // Aquí solo asegúrate de que el campo exista (si lo añades al formulario del admin)
    // De lo contrario, este formulario NO maneja la contraseña del usuario (que va en tabla `usuarios`)
    // Si estás creando el conductor y el usuario a la vez, deberías manejar la contraseña aquí
    // y luego en el PHP crear el usuario en `usuarios` y el conductor en `conductores`.
    // Por ahora, asumo que el ID de conductor se refiere a un usuario YA existente en `usuarios`
    // y que solo estamos creando la entrada en la tabla `conductores`.
    // Si quieres crear el usuario y conductor en un solo formulario, la lógica sería más compleja.

    try {
        const response = await fetch(`panelA.php?action=conductores`, {
            method: 'POST',
            body: formData, // FormData no necesita Content-Type, el navegador lo pone
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        if (result.error) {
            alert("Error: " + result.error);
            return { success: false, message: result.error };
        }
        if (result.success) {
            alert('Conductor agregado exitosamente!');
            document.getElementById('add-conductor-form').style.display = 'none';
            document.getElementById('add-conductor-form').reset(); // Limpiar el formulario
            fetchConductores();
        } else {
            alert('Error al agregar conductor: ' + (result.message || 'Error desconocido'));
        }
        return false;
    } catch (error) {
        console.error(`Error posting conductores:`, error);
        alert(`No se pudo guardar la información del conductor.`);
        return { success: false, message: "Error de red o servidor." };
    }
}

function showAddVehiculoForm() {
    hideAllAddForms();
    document.getElementById('add-vehiculo-form').style.display = 'block';
}

async function addVehiculo(event) {
    event.preventDefault();
    const id = document.getElementById('vehiculo-id').value;
    const placa = document.getElementById('vehiculo-placa').value;
    const modelo = document.getElementById('vehiculo-modelo').value;
    const capacidad = document.getElementById('vehiculo-capacidad').value;
    const estado = document.getElementById('vehiculo-estado').value;

    const result = await postData('vehiculos', { id, placa, modelo, capacidad, estado });
    if (result.success) {
        alert('Vehículo agregado exitosamente!');
        document.getElementById('add-vehiculo-form').style.display = 'none';
        fetchVehiculos();
    } else {
        alert('Error al agregar vehículo: ' + (result.message || 'Error desconocido'));
    }
    return false;
}

function showAddUsuarioForm() {
    hideAllAddForms();
    document.getElementById('add-usuario-form').style.display = 'block';
}

async function addUsuario(event) {
    event.preventDefault();
    const id = document.getElementById('usuario-id').value;
    const username = document.getElementById('usuario-username').value; // Nuevo campo para username
    const rol = document.getElementById('usuario-rol').value;
    const email = document.getElementById('usuario-email').value; // Coincide con tu DB
    const contrasena = document.getElementById('usuario-password').value;

    const result = await postData('usuarios', { id, username, rol, email, contrasena });
    if (result.success) {
        alert('Usuario agregado exitosamente!');
        document.getElementById('add-usuario-form').style.display = 'none';
        fetchUsuarios();
    } else {
        alert('Error al agregar usuario: ' + (result.message || 'Error desconocido'));
    }
    return false;
}

// --- Funciones para Editar y Eliminar (manteniendo el placeholder por ahora) ---
function editRuta(id) { console.log('Editar ruta', id); alert('Funcionalidad de edición no implementada aún.'); }
function deleteRuta(id) { console.log('Eliminar ruta', id); alert('Funcionalidad de eliminación no implementada aún.'); }
function editHorario(id) { console.log('Editar horario', id); alert('Funcionalidad de edición no implementada aún.'); }
function deleteHorario(id) { console.log('Eliminar horario', id); alert('Funcionalidad de eliminación no implementada aún.'); }
function editEstudiante(cedula) { console.log('Editar estudiante', cedula); alert('Funcionalidad de edición no implementada aún.'); }
function deleteEstudiante(cedula) { console.log('Eliminar estudiante', cedula); alert('Funcionalidad de eliminación no implementada aún.'); }
function editConductor(id) { console.log('Editar conductor', id); alert('Funcionalidad de edición no implementada aún.'); }
function deleteConductor(id) { console.log('Eliminar conductor', id); alert('Funcionalidad de eliminación no implementada aún.'); }
function editVehiculo(id) { console.log('Editar vehículo', id); alert('Funcionalidad de edición no implementada aún.'); }
function deleteVehiculo(id) { console.log('Eliminar vehículo', id); alert('Funcionalidad de eliminación no implementada aún.'); }
function editUsuario(id) { console.log('Editar usuario', id); alert('Funcionalidad de edición no implementada aún.'); }
function deleteUsuario(id) { console.log('Eliminar usuario', id); alert('Funcionalidad de eliminación no implementada aún.'); }


// Función para cerrar sesión
async function logout() {
    const result = await fetchData('logout'); // Llama a la acción 'logout' en panelA.php
    if (result.success) {
        alert(result.message);
        window.location.href = '../index.php'; // Redirigir al login
    } else {
        alert('Error al cerrar sesión: ' + (result.message || result.error || 'Error desconocido'));
    }
}

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Mostrar la sección de Configuración Rutas por defecto y cargar sus datos
    showSection('configuracion');
    fetchRutas();
});