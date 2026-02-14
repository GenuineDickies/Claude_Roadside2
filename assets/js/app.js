// RoadRunner — App-level JavaScript (tooltips, alerts, validation, search)

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el);
    });

    // Auto dismiss alerts after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Confirm delete actions
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Form validation
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var requiredFields = form.querySelectorAll('[required]');
            var isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

    // Real-time search functionality
    document.querySelectorAll('.search-input').forEach(function(input) {
        input.addEventListener('keyup', function() {
            var searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.searchable-row').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    });
});
