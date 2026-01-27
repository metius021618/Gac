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
                    await window.GAC.success('Administrador actualizado correctamente', 'Éxito');
                    window.location.href = '/admin/administrators';
                } else {
                    await window.GAC.error(data.message || 'Error al actualizar administrador', 'Error');
                }
            } catch (error) {
                console.error('Error:', error);
                await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
            }
        });
    }
})();
