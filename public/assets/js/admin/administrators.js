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

    // --- Modal Nuevo Usuario ---
    const newUserModal = document.getElementById('newUserModal');
    const btnNewUser = document.getElementById('btnNewUser');
    const closeNewUserModal = document.getElementById('closeNewUserModal');
    const cancelNewUserBtn = document.getElementById('cancelNewUserBtn');
    const newUserForm = document.getElementById('newUserForm');
    const newUserUsername = document.getElementById('newUserUsername');
    const newUserPassword = document.getElementById('newUserPassword');
    const newUserRole = document.getElementById('newUserRole');
    const previewUsernamePlaceholder = document.getElementById('previewUsernamePlaceholder');
    const previewDashboardEmpty = document.getElementById('previewDashboardEmpty');
    const previewDashboardContent = document.getElementById('previewDashboardContent');
    const btnNewUserPreviewFull = document.getElementById('btnNewUserPreviewFull');
    const newUserPreviewModal = document.getElementById('newUserPreviewModal');
    const closeNewUserPreviewModal = document.getElementById('closeNewUserPreviewModal');
    const newUserPreviewFrame = document.getElementById('newUserPreviewFrame');

    var currentRoleViewKeys = [];

    function openNewUserModal() {
        if (newUserModal) {
            newUserModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            updatePreviewView();
        }
    }

    function closeNewUserModalFn() {
        if (newUserModal) {
            newUserModal.classList.add('hidden');
            document.body.style.overflow = '';
            if (newUserForm) newUserForm.reset();
            if (previewDashboardContent) previewDashboardContent.classList.add('hidden');
            if (previewDashboardEmpty) previewDashboardEmpty.classList.remove('hidden');
        }
    }

    function updatePreviewView() {
        const username = (newUserUsername && newUserUsername.value.trim()) ? newUserUsername.value.trim() : '—';
        if (previewUsernamePlaceholder) previewUsernamePlaceholder.textContent = username;

        const roleId = newUserRole && newUserRole.value ? parseInt(newUserRole.value, 10) : 0;
        if (!previewDashboardContent || !previewDashboardEmpty) return;

        if (!roleId) {
            currentRoleViewKeys = [];
            previewDashboardContent.classList.add('hidden');
            if (previewDashboardEmpty) {
                previewDashboardEmpty.classList.remove('hidden');
                previewDashboardEmpty.textContent = 'Seleccione un rol para ver las vistas asignadas.';
            }
            return;
        }

        fetch('/admin/settings/role-views?role_id=' + encodeURIComponent(roleId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                currentRoleViewKeys = data.view_keys || [];
                const config = (typeof window.GAC_ROLE_VIEWS_CONFIG !== 'undefined') ? window.GAC_ROLE_VIEWS_CONFIG : {};
                previewDashboardEmpty.classList.add('hidden');
                previewDashboardContent.classList.remove('hidden');
                previewDashboardContent.innerHTML = '';
                const iconSvg = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>';
                currentRoleViewKeys.forEach(function (key) {
                    const v = config[key];
                    const label = (v && v.label) ? v.label : key;
                    const item = document.createElement('span');
                    item.className = 'preview-dashboard-item';
                    item.innerHTML = iconSvg + ' ' + label;
                    previewDashboardContent.appendChild(item);
                });
                if (currentRoleViewKeys.length === 0) {
                    previewDashboardContent.innerHTML = '<span class="preview-dashboard-item">Sin vistas asignadas (configúralas en Configuración → Personalización de roles)</span>';
                }
            })
            .catch(function () {
                currentRoleViewKeys = [];
                previewDashboardEmpty.classList.remove('hidden');
                previewDashboardEmpty.textContent = 'Error al cargar vistas. Verifica la conexión.';
                previewDashboardContent.classList.add('hidden');
            });
    }

    function openNewUserPreviewFull() {
        if (!newUserPreviewModal || !newUserPreviewFrame) return;
        const q = currentRoleViewKeys.length ? 'views=' + encodeURIComponent(currentRoleViewKeys.join(',')) : 'views=';
        newUserPreviewFrame.src = '/admin/role-preview?' + q;
        newUserPreviewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeNewUserPreviewFull() {
        if (newUserPreviewModal) newUserPreviewModal.classList.add('hidden');
        if (newUserPreviewFrame) newUserPreviewFrame.src = 'about:blank';
        document.body.style.overflow = (newUserModal && !newUserModal.classList.contains('hidden')) ? 'hidden' : '';
    }

    if (btnNewUser) btnNewUser.addEventListener('click', openNewUserModal);
    if (closeNewUserModal) closeNewUserModal.addEventListener('click', closeNewUserModalFn);
    if (cancelNewUserBtn) cancelNewUserBtn.addEventListener('click', closeNewUserModalFn);
    if (newUserModal) {
        newUserModal.addEventListener('click', function (e) {
            if (e.target === newUserModal) closeNewUserModalFn();
        });
    }
    if (newUserUsername) newUserUsername.addEventListener('input', updatePreviewView);
    if (newUserUsername) newUserUsername.addEventListener('change', updatePreviewView);
    if (newUserRole) newUserRole.addEventListener('change', updatePreviewView);
    if (btnNewUserPreviewFull) btnNewUserPreviewFull.addEventListener('click', openNewUserPreviewFull);
    if (closeNewUserPreviewModal) closeNewUserPreviewModal.addEventListener('click', closeNewUserPreviewFull);
    if (newUserPreviewModal) {
        newUserPreviewModal.addEventListener('click', function (e) {
            if (e.target === newUserPreviewModal) closeNewUserPreviewFull();
        });
    }

    if (newUserForm) {
        newUserForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(newUserForm);
            try {
                const response = await fetch('/admin/administrators/store', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    closeNewUserModalFn();
                    if (typeof window.GAC !== 'undefined' && window.GAC.success) {
                        await window.GAC.success('Usuario creado correctamente', 'Éxito');
                    }
                    window.location.reload();
                } else {
                    if (typeof window.GAC !== 'undefined' && window.GAC.error) {
                        await window.GAC.error(data.message || 'Error al crear usuario', 'Error');
                    } else {
                        alert(data.message || 'Error al crear usuario');
                    }
                }
            } catch (err) {
                console.error(err);
                if (typeof window.GAC !== 'undefined' && window.GAC.error) {
                    await window.GAC.error('Error de conexión. Intenta de nuevo.', 'Error');
                } else {
                    alert('Error de conexión.');
                }
            }
        });
    }

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
                await window.GAC.error('Las contraseñas no coinciden', 'Error de Validación');
                return;
            }

            if (newPassword.length < 6) {
                await window.GAC.error('La contraseña debe tener al menos 6 caracteres', 'Error de Validación');
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
                    // Cerrar el modal de cambio de contraseña primero
                    closeModal();
                    // Esperar un momento para que el modal se cierre completamente
                    await new Promise(resolve => setTimeout(resolve, 200));
                    // Mostrar popup de éxito
                    await window.GAC.success('Contraseña actualizada correctamente', 'Éxito');
                    // Redirigir a la vista de administradores después de aceptar
                    window.location.href = '/admin/administrators';
                } else {
                    await window.GAC.error(data.message || 'Error al actualizar contraseña', 'Error');
                }
            } catch (error) {
                console.error('Error:', error);
                await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
            }
        });
    }
})();
