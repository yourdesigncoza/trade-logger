/**
 * Trade Logger Main Application JavaScript
 */

// Global app configuration
window.TradeLogger = {
    baseUrl: window.location.origin + '/trade-logger',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    
    // Utility functions
    utils: {
        // Format currency
        formatCurrency: function(amount, decimals = 2) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(amount);
        },
        
        // Format percentage
        formatPercentage: function(value, decimals = 2) {
            return value.toFixed(decimals) + '%';
        },
        
        // Show loading state
        showLoading: function(element) {
            element.classList.add('loading');
            element.style.pointerEvents = 'none';
        },
        
        // Hide loading state
        hideLoading: function(element) {
            element.classList.remove('loading');
            element.style.pointerEvents = '';
        },
        
        // Show toast notification
        showToast: function(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        },
        
        // AJAX helper
        ajax: function(url, options = {}) {
            const defaults = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            if (TradeLogger.csrfToken) {
                defaults.headers['X-CSRF-Token'] = TradeLogger.csrfToken;
            }
            
            return fetch(url, Object.assign(defaults, options))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                });
        }
    },
    
    // Form handling
    forms: {
        // Initialize form validation
        initValidation: function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        },
        
        // Serialize form data
        serialize: function(form) {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }
            return data;
        }
    },
    
    // File upload handling
    upload: {
        initDropZone: function(element, callback) {
            element.addEventListener('dragover', function(e) {
                e.preventDefault();
                element.classList.add('dragover');
            });
            
            element.addEventListener('dragleave', function(e) {
                e.preventDefault();
                element.classList.remove('dragover');
            });
            
            element.addEventListener('drop', function(e) {
                e.preventDefault();
                element.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0 && callback) {
                    callback(files[0]);
                }
            });
        },
        
        validateImage: function(file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 4 * 1024 * 1024; // 4MB
            
            if (!allowedTypes.includes(file.type)) {
                TradeLogger.utils.showToast('Please select a valid image file (JPG, PNG, GIF)', 'danger');
                return false;
            }
            
            if (file.size > maxSize) {
                TradeLogger.utils.showToast('File size must be less than 4MB', 'danger');
                return false;
            }
            
            return true;
        }
    },
    
    // Initialize application
    init: function() {
        // Initialize all forms with validation
        document.querySelectorAll('form[data-validate]').forEach(form => {
            TradeLogger.forms.initValidation(form);
        });
        
        // Initialize all file drop zones
        document.querySelectorAll('.upload-zone').forEach(zone => {
            TradeLogger.upload.initDropZone(zone, function(file) {
                if (TradeLogger.upload.validateImage(file)) {
                    const input = zone.querySelector('input[type="file"]');
                    if (input) {
                        // Create a new FileList with the dropped file
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        
                        // Trigger change event
                        input.dispatchEvent(new Event('change'));
                    }
                }
            });
        });
        
        // Initialize date inputs with proper format
        document.querySelectorAll('input[type="date"]').forEach(input => {
            if (!input.value) {
                input.value = new Date().toISOString().split('T')[0];
            }
        });
        
        // Initialize time inputs
        document.querySelectorAll('input[type="time"]').forEach(input => {
            if (!input.value) {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                input.value = `${hours}:${minutes}`;
            }
        });
        
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        console.log('Trade Logger initialized successfully');
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    TradeLogger.init();
});