document.addEventListener('DOMContentLoaded', function () {
    // Toggle de subusuarios en Lista de cuentas
    document.querySelectorAll('.revendedor-toggle-subusers').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var accessId = this.getAttribute('data-access-id');
            var row = document.querySelector('.revendedor-subusers-row[data-parent-id="' + accessId + '"]');
            if (!row) return;
            var isHidden = row.style.display === 'none' || row.style.display === '';
            row.style.display = isHidden ? 'table-row' : 'none';
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    });

    // Mapeo correo → plataformas para formulario de Accesos
    var emailSelect = document.getElementById('emailSelect');
    var platformSelect = document.getElementById('platformSelect');
    var map = window.REV_EMAIL_PLATFORM_MAP || [];

    function findPlatformsByEmail(email) {
        for (var i = 0; i < map.length; i++) {
            if (map[i].email === email) {
                return map[i].platforms || [];
            }
        }
        return [];
    }

    function populatePlatforms(email) {
        if (!platformSelect) return;
        platformSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'SELECCIONE PLATAFORMA';
        platformSelect.appendChild(placeholder);

        if (!email) {
            return;
        }

        var platforms = findPlatformsByEmail(email);
        if (platforms.length === 1) {
            // Una sola plataforma: seleccionarla automáticamente
            var p = platforms[0];
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.name || '';
            platformSelect.appendChild(opt);
            platformSelect.value = String(p.id);
            return;
        }

        platforms.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.name || '';
            platformSelect.appendChild(opt);
        });
    }

    if (emailSelect && platformSelect) {
        emailSelect.addEventListener('change', function () {
            populatePlatforms(this.value);
        });
    }
});

