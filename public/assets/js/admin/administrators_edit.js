/**
 * GAC - JavaScript para Edición de Administrador
 */

(function() {
    'use strict';

    const editForm = document.getElementById('editAdminForm');

    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(editForm);

            try {
                const response = await fetch('/admin/administrators/update', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Administrador actualizado correctamente');
                    window.location.href = '/admin/administrators';
                } else {
                    alert(data.message || 'Error al actualizar administrador');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión. Por favor intenta nuevamente.');
            }
        });
    }
})();
