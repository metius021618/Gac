/**
 * GAC - JavaScript para Lista de Administradores
 */

(function() {
    'use strict';

    const passwordModal = document.getElementById('passwordModal');
    const passwordForm = document.getElementById('passwordForm');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const passwordUserId = document.getElementById('passwordUserId');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Abrir modal de contraseña
    document.querySelectorAll('.btn-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            const username = this.dataset.username;
            
            passwordUserId.value = userId;
            const modalTitle = passwordModal.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = `Cambiar Contraseña - ${username}`;
            }
            passwordModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    });

    // Cerrar modal
    function closeModal() {
        passwordModal.classList.add('hidden');
        document.body.style.overflow = '';
        passwordForm.reset();
    }

    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', closeModal);
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', closeModal);
    }

    // Cerrar al hacer clic fuera
    passwordModal?.addEventListener('click', function(e) {
        if (e.target === passwordModal) {
            closeModal();
        }
    });

    // Enviar formulario de contraseña
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }

            if (newPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }

            const formData = new FormData(passwordForm);
            formData.append('id', passwordUserId.value);

            try {
                const response = await fetch('/admin/administrators/update-password', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Contraseña actualizada correctamente');
                    closeModal();
                } else {
                    alert(data.message || 'Error al actualizar contraseña');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión. Por favor intenta nuevamente.');
            }
        });
    }
})();
