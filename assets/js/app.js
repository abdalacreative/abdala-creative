/**
 * Global application JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {

    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
    }

    // Generic delete confirmation for any element with [data-confirm-delete]
    document.querySelectorAll('[data-confirm-delete]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this record?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(() => {
            if (window.bootstrap) {
                const instance = bootstrap.Alert.getOrCreateInstance(alert);
                instance.close();
            }
        }, 5000);
    });

    // Live search-as-you-type for tables (data-search-target points to a table body)
    document.querySelectorAll('[data-live-search]').forEach(function (input) {
        const targetSelector = input.getAttribute('data-live-search');
        const rows = document.querySelectorAll(targetSelector + ' tr');
        input.addEventListener('keyup', function () {
            const term = input.value.toLowerCase();
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    });
});

/** Simple client-side form validator. Adds .is-invalid to empty required fields. */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    return valid;
}
