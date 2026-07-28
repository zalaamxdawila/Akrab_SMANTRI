// main.js

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 3 seconds
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 3000);
    });

    // Helper for SweetAlert confirmation on delete/action
    const confirmButtons = document.querySelectorAll('.btn-confirm-action');
    confirmButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const actionText = this.getAttribute('data-action-text') || 'melakukan aksi ini';
            
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: `Anda akan ${actionText}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2e7d32',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, lanjutkan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // Initialize tooltips (if any exist in the future)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Navbar scroll interaction
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.boxShadow = '0 1px 2px 0 rgba(0, 0, 0, 0.05)';
                navbar.style.background = 'rgba(255, 255, 255, 0.85)';
            }
        });
    }
});

// Function to render Chart.js (Dashboard UKS)
function renderKepatuhanChart(ctxId, labels, dataPatuh, dataTidakPatuh) {
    const ctx = document.getElementById(ctxId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Patuh Minum TTD',
                    data: dataPatuh,
                    backgroundColor: 'rgba(46, 125, 50, 0.7)',
                    borderColor: 'rgba(46, 125, 50, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Belum Minum TTD',
                    data: dataTidakPatuh,
                    backgroundColor: 'rgba(239, 83, 80, 0.7)',
                    borderColor: 'rgba(239, 83, 80, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}
