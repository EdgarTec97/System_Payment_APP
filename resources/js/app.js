import './bootstrap';
import 'bootstrap';
import Swal from 'sweetalert2';

// Make SweetAlert2 globally available
window.Swal = Swal;

// Global SweetAlert2 configuration
Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
});

// Global functions for common alerts
window.showSuccess = function(message, title = 'Éxito') {
    return Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false
    });
};

window.showError = function(message, title = 'Error') {
    return Swal.fire({
        icon: 'error',
        title: title,
        text: message
    });
};

window.showWarning = function(message, title = 'Advertencia') {
    return Swal.fire({
        icon: 'warning',
        title: title,
        text: message
    });
};

window.showConfirm = function(message, title = '¿Estás seguro?') {
    return Swal.fire({
        icon: 'question',
        title: title,
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    });
};

window.showDeleteConfirm = function(message = '¡No podrás revertir esto!') {
    return Swal.fire({
        icon: 'warning',
        title: '¿Estás seguro?',
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    });
};

// Cart functionality
window.addToCart = function(productId, quantity = 1) {
    fetch(`/cart/add/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            updateCartCount(data.cart_count);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al agregar el producto al carrito');
    });
};

window.updateCartCount = function(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        if (count > 0) {
            element.classList.remove('d-none');
        } else {
            element.classList.add('d-none');
        }
    });
};

// Load cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/cart/count')
        .then(response => response.json())
        .then(data => {
            updateCartCount(data.count);
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
});

// Form validation helpers
window.validateForm = function(formElement) {
    const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
};

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

