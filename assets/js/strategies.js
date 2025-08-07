/**
 * Strategy Management JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize strategy conditions
    initializeConditions();
    
    // Initialize image upload
    initializeImageUpload();
    
    // Initialize form validation
    initializeFormValidation();
});

// Condition Management
let conditionCounter = 0;

function initializeConditions() {
    const addConditionBtn = document.getElementById('addCondition');
    const conditionsContainer = document.getElementById('conditionsContainer');
    
    if (!addConditionBtn || !conditionsContainer) return;
    
    // Add condition button click handler
    addConditionBtn.addEventListener('click', function() {
        addCondition();
    });
    
    // Load existing conditions if any (for edit mode)
    const existingConditions = window.existingConditions || [];
    existingConditions.forEach(condition => {
        addCondition(condition.type, condition.description);
    });
    
    // Add at least one condition by default
    if (conditionsContainer.children.length === 0) {
        addCondition();
    }
}

function addCondition(type = '', description = '') {
    const conditionsContainer = document.getElementById('conditionsContainer');
    conditionCounter++;
    
    const conditionHtml = `
        <div class="condition-item condition-type-${type}" data-condition-id="${conditionCounter}">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" name="conditions[${conditionCounter}][type]" required>
                        <option value="">Select type</option>
                        <option value="entry" ${type === 'entry' ? 'selected' : ''}>Entry</option>
                        <option value="exit" ${type === 'exit' ? 'selected' : ''}>Exit</option>
                        <option value="invalidation" ${type === 'invalidation' ? 'selected' : ''}>Invalidation</option>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <textarea class="form-control form-control-sm" 
                              name="conditions[${conditionCounter}][description]" 
                              rows="2" 
                              placeholder="Describe the condition..."
                              required>${description}</textarea>
                </div>
                <div class="col-12 col-md-1">
                    <button type="button" class="btn btn-phoenix-danger btn-sm btn-remove w-100" 
                            onclick="removeCondition(${conditionCounter})">
                        <span class="fas fa-trash"></span>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    conditionsContainer.insertAdjacentHTML('beforeend', conditionHtml);
    
    // Add event listener for type change to update styling
    const newCondition = conditionsContainer.lastElementChild;
    const typeSelect = newCondition.querySelector('select[name*="[type]"]');
    typeSelect.addEventListener('change', function() {
        updateConditionStyling(newCondition, this.value);
    });
    
    updateConditionStyling(newCondition, type);
}

function removeCondition(conditionId) {
    const condition = document.querySelector(`[data-condition-id="${conditionId}"]`);
    if (condition) {
        condition.remove();
    }
    
    // Ensure at least one condition remains
    const conditionsContainer = document.getElementById('conditionsContainer');
    if (conditionsContainer.children.length === 0) {
        addCondition();
    }
}

function updateConditionStyling(conditionElement, type) {
    // Remove existing type classes
    conditionElement.classList.remove('condition-type-entry', 'condition-type-exit', 'condition-type-invalidation');
    
    // Add new type class
    if (type) {
        conditionElement.classList.add(`condition-type-${type}`);
    }
}

// Image Upload Management
function initializeImageUpload() {
    const uploadZone = document.getElementById('chartUploadZone');
    const fileInput = document.getElementById('chart_image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImageBtn = document.getElementById('removeImage');
    
    if (!uploadZone || !fileInput) return;
    
    // Click to browse
    uploadZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            handleImageSelection(this.files[0]);
        }
    });
    
    // Remove image
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            clearImagePreview();
        });
    }
    
    // Drag and drop functionality is handled by the main app.js
}

function handleImageSelection(file) {
    // Validate file
    if (!TradeLogger.upload.validateImage(file)) {
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        showImagePreview(e.target.result);
    };
    reader.readAsDataURL(file);
}

function showImagePreview(imageSrc) {
    const uploadZone = document.getElementById('chartUploadZone');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (previewImg) {
        previewImg.src = imageSrc;
    }
    
    if (uploadZone) {
        uploadZone.classList.add('d-none');
    }
    
    if (imagePreview) {
        imagePreview.classList.remove('d-none');
    }
}

function clearImagePreview() {
    const uploadZone = document.getElementById('chartUploadZone');
    const imagePreview = document.getElementById('imagePreview');
    const fileInput = document.getElementById('chart_image');
    const previewImg = document.getElementById('previewImg');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    if (previewImg) {
        previewImg.src = '';
    }
    
    if (uploadZone) {
        uploadZone.classList.remove('d-none');
    }
    
    if (imagePreview) {
        imagePreview.classList.add('d-none');
    }
}

// Form Validation
function initializeFormValidation() {
    const form = document.querySelector('form[data-validate]');
    if (!form) return;
    
    form.addEventListener('submit', function(event) {
        // Custom validation for conditions
        if (!validateConditions()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Custom validation for at least one timeframe or session
        if (!validateTimeframesAndSessions()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Standard HTML5 validation
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
}

function validateConditions() {
    const conditionsContainer = document.getElementById('conditionsContainer');
    if (!conditionsContainer) return true;
    
    const conditions = conditionsContainer.querySelectorAll('.condition-item');
    let hasValidCondition = false;
    
    conditions.forEach(condition => {
        const typeSelect = condition.querySelector('select[name*="[type]"]');
        const descriptionTextarea = condition.querySelector('textarea[name*="[description]"]');
        
        if (typeSelect && descriptionTextarea) {
            if (typeSelect.value && descriptionTextarea.value.trim()) {
                hasValidCondition = true;
            }
        }
    });
    
    if (!hasValidCondition) {
        TradeLogger.utils.showToast('Please add at least one complete strategy condition', 'warning');
        return false;
    }
    
    return true;
}

function validateTimeframesAndSessions() {
    const timeframeChecked = document.querySelectorAll('input[name="timeframes[]"]:checked').length > 0;
    const sessionChecked = document.querySelectorAll('input[name="sessions[]"]:checked').length > 0;
    
    if (!timeframeChecked && !sessionChecked) {
        TradeLogger.utils.showToast('Please select at least one timeframe or session', 'warning');
        return false;
    }
    
    return true;
}

// Strategy Statistics Chart (for view page)
function initializeStrategyChart(chartData) {
    const ctx = document.getElementById('strategyChart');
    if (!ctx || !chartData) return;
    
    const config = {
        type: 'doughnut',
        data: {
            labels: ['Wins', 'Losses', 'Break-even'],
            datasets: [{
                data: [
                    chartData.winning_trades,
                    chartData.losing_trades,
                    chartData.breakeven_trades
                ],
                backgroundColor: [
                    'rgba(37, 176, 3, 0.8)',
                    'rgba(250, 59, 29, 0.8)',
                    'rgba(229, 120, 11, 0.8)'
                ],
                borderColor: [
                    'rgba(37, 176, 3, 1)',
                    'rgba(250, 59, 29, 1)',
                    'rgba(229, 120, 11, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Utility Functions
function confirmDelete(strategyId, strategyName) {
    if (confirm(`Are you sure you want to delete the strategy "${strategyName}"?\n\nThis will remove the strategy from all associated trades, but the trades themselves will not be deleted.`)) {
        // Create and submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${TradeLogger.baseUrl}/views/strategies/delete.php`;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = strategyId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Export for global access
window.StrategyManager = {
    addCondition,
    removeCondition,
    confirmDelete,
    initializeStrategyChart
};