const sign_in_btn = document.querySelector("#sign-in-btn");
const sign_up_btn = document.querySelector("#sign-up-btn");
const container = document.querySelector(".container");

const btnEstudiante = document.querySelector("#btn-estudiante");
const btnConductor = document.querySelector("#btn-conductor");
const formEstudiante = document.querySelector("#form-estudiante");
const formConductor = document.querySelector("#form-conductor");

const signInForm = document.querySelector(".sign-in-form");
const signUpForm = document.querySelector(".sign-up-form"); // Formulario principal de registro

const selectedRolInput = document.querySelector("#selected_rol"); // Obtener el campo oculto

sign_up_btn.addEventListener('click', () => {
    container.classList.add("sign-up-mode");
    // Al ir a la vista de registro, asegurar que el formulario de estudiante esté activo por defecto
    formEstudiante.classList.add("active");
    formConductor.classList.remove("active");
    btnEstudiante.classList.add("solid"); // Para simular el botón seleccionado por defecto
    btnConductor.classList.remove("solid");
    selectedRolInput.value = 'estudiante'; // Establecer rol por defecto al cambiar a modo registro
});

sign_in_btn.addEventListener('click', () => {
    container.classList.remove("sign-up-mode");
});

// Lógica para cambiar entre formularios de registro
btnEstudiante.addEventListener('click', () => {
    formEstudiante.classList.add("active");
    formConductor.classList.remove("active");
    btnEstudiante.classList.add("solid");
    btnConductor.classList.remove("solid");
    selectedRolInput.value = 'estudiante'; // Establecer el rol al seleccionar estudiante
});

btnConductor.addEventListener('click', () => {
    formConductor.classList.add("active");
    formEstudiante.classList.remove("active");
    btnConductor.classList.add("solid");
    btnEstudiante.classList.remove("solid");
    selectedRolInput.value = 'conductor'; // Establecer el rol al seleccionar conductor
});

// Validación básica de formularios de INICIO DE SESIÓN (frontend)
signInForm.addEventListener('submit', (e) => {
    // La validación real se hará en procesar_auth.php
    // Puedes añadir validaciones de JS aquí si quieres retroalimentación instantánea al usuario
    // Por ejemplo, para verificar que los campos no estén vacíos.
});

// Validación básica de formularios de REGISTRO (frontend)
signUpForm.addEventListener('submit', (e) => {
    const passwordInput = signUpForm.querySelector('input[type="password"][name="password"]');
    const confirmPasswordInput = signUpForm.querySelector('input[type="password"][name="confirm_password"]');

    if (passwordInput && confirmPasswordInput && passwordInput.value !== confirmPasswordInput.value) {
        e.preventDefault(); // Detener el envío si las contraseñas no coinciden en JS
        alert("Las contraseñas no coinciden. Por favor, verifique.");
        return;
    }

    // Asegurarse de que el campo oculto tenga un valor antes de enviar
    if (selectedRolInput.value === '') {
        e.preventDefault();
        alert("Por favor, seleccione si es Estudiante o Conductor.");
        return;
    }
    // Si la validación de JS pasa, el formulario se enviará automáticamente al PHP.
});

// Nuevo: Manejar la visibilidad de los campos de registro según el rol
const selectRol = document.getElementById('select-rol');
const institucionContainer = document.getElementById('institucion-container');
const estudianteFields = document.getElementById('estudiante-fields');
const conductorFields = document.getElementById('conductor-fields');
const institucionRegistro = document.getElementById('institucion_registro'); // El select de institución

if (selectRol) { // Asegurarse de que el elemento exista
    selectRol.addEventListener('change', function() {
        const selectedRol = this.value;

        // Mostrar u ocultar la institución común
        if (selectedRol === 'estudiante' || selectedRol === 'conductor') {
            institucionContainer.style.display = 'block';
            institucionRegistro.setAttribute('required', 'required'); // Hacerlo requerido
        } else {
            institucionContainer.style.display = 'none';
            institucionRegistro.removeAttribute('required'); // No requerido si no hay rol
            institucionRegistro.value = ''; // Limpiar selección
        }

        // Ocultar todos los campos específicos primero
        estudianteFields.style.display = 'none';
        conductorFields.style.display = 'none';

        // Mostrar campos según el rol seleccionado
        if (selectedRol === 'estudiante') {
            // Estudiante solo necesita la institución (ya manejada arriba)
            // No hay campos específicos adicionales que ocultar/mostrar aquí
        } else if (selectedRol === 'conductor') {
            conductorFields.style.display = 'block';
            // Hacer que los campos del conductor sean requeridos si el rol es conductor
            document.querySelector('[name="telefono_conductor"]').setAttribute('required', 'required');
            document.querySelector('[name="licencia_conductor"]').setAttribute('required', 'required');
            document.querySelector('[name="matricula_autobus"]').setAttribute('required', 'required');
            document.getElementById('foto_perfil').setAttribute('required', 'required');
            document.getElementById('foto_autobus').setAttribute('required', 'required');
        } else {
            // Si no se selecciona ningún rol, quitar el 'required' a los campos de conductor
            document.querySelector('[name="telefono_conductor"]').removeAttribute('required');
            document.querySelector('[name="licencia_conductor"]').removeAttribute('required');
            document.querySelector('[name="matricula_autobus"]').removeAttribute('required');
            document.getElementById('foto_perfil').removeAttribute('required');
            document.getElementById('foto_autobus').removeAttribute('required');
        }
    });
}

// Actualizar nombre de archivo en los botones de subida
document.querySelectorAll('.file-upload-input').forEach(inputElement => {
    inputElement.addEventListener('change', function() {
        const fileNameSpan = document.getElementById(this.id + '_name');
        if (this.files && this.files.length > 0) {
            fileNameSpan.textContent = this.files[0].name;
        } else {
            fileNameSpan.textContent = 'Ningún archivo seleccionado';
        }
    });
});