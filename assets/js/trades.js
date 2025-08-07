/**
 * Trade Management JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize trade form validation
    initializeTradeValidation();
    
    // Initialize image upload
    initializeImageUpload();
    
    // Initialize status/outcome logic
    initializeStatusLogic();
});

// Trade Form Validation
function initializeTradeValidation() {
    const form = document.querySelector('form[data-validate]');
    if (!form) return;
    
    const directionSelect = document.getElementById('direction');
    const entryPriceInput = document.getElementById('entry_price');
    const slInput = document.getElementById('sl');
    const tpInput = document.getElementById('tp');
    
    if (!directionSelect || !entryPriceInput || !slInput) return;
    
    // Validate SL and TP whenever direction or prices change
    [directionSelect, entryPriceInput, slInput, tpInput].forEach(element => {
        if (element) {
            element.addEventListener('input', validatePriceLevels);
            element.addEventListener('change', validatePriceLevels);
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(event) {
        if (!validateTradeForm()) {
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

function validatePriceLevels() {
    const direction = document.getElementById('direction')?.value;
    const entryPrice = parseFloat(document.getElementById('entry_price')?.value || 0);
    const sl = parseFloat(document.getElementById('sl')?.value || 0);
    const tp = parseFloat(document.getElementById('tp')?.value || 0);
    
    const slInput = document.getElementById('sl');
    const tpInput = document.getElementById('tp');
    const slFeedback = document.getElementById('slValidationFeedback');
    const tpFeedback = document.getElementById('tpValidationFeedback');
    
    if (!direction || !entryPrice || !sl) return;
    
    let slValid = true;
    let tpValid = true;
    
    // Validate Stop Loss
    if (direction === 'long' && sl >= entryPrice) {
        slValid = false;
        if (slFeedback) slFeedback.textContent = 'For long trades, stop loss must be below entry price';
    } else if (direction === 'short' && sl <= entryPrice) {
        slValid = false;
        if (slFeedback) slFeedback.textContent = 'For short trades, stop loss must be above entry price';
    }
    
    // Validate Take Profit (if provided)
    if (tp > 0) {
        if (direction === 'long' && tp <= entryPrice) {
            tpValid = false;
            if (tpFeedback) tpFeedback.textContent = 'For long trades, take profit must be above entry price';
        } else if (direction === 'short' && tp >= entryPrice) {
            tpValid = false;
            if (tpFeedback) tpFeedback.textContent = 'For short trades, take profit must be below entry price';
        }
    }
    
    // Update UI
    if (slInput) {
        slInput.setCustomValidity(slValid ? '' : 'Invalid stop loss level');
        slInput.classList.toggle('is-invalid', !slValid);
    }
    
    if (tpInput && tp > 0) {
        tpInput.setCustomValidity(tpValid ? '' : 'Invalid take profit level');
        tpInput.classList.toggle('is-invalid', !tpValid);
    }
    
    // Show/hide feedback
    if (slFeedback) {
        slFeedback.style.display = slValid ? 'none' : 'block';
    }
    if (tpFeedback) {
        tpFeedback.style.display = (tpValid || tp === 0) ? 'none' : 'block';
    }
    
    return slValid && tpValid;
}

function validateTradeForm() {
    // Custom validations beyond HTML5
    let valid = true;
    
    // Validate price levels
    if (!validatePriceLevels()) {
        valid = false;
    }
    
    // Validate date is not in future
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const tradeDate = new Date(dateInput.value);
        const today = new Date();
        today.setHours(23, 59, 59, 999); // End of today
        
        if (tradeDate > today) {
            dateInput.setCustomValidity('Trade date cannot be in the future');
            valid = false;
        } else {
            dateInput.setCustomValidity('');
        }
    }
    
    // Validate exit time is after entry time (if both provided)
    const entryTimeInput = document.getElementById('entry_time');
    const exitTimeInput = document.getElementById('exit_time');
    
    if (entryTimeInput && exitTimeInput && entryTimeInput.value && exitTimeInput.value) {
        const entryTime = entryTimeInput.value;
        const exitTime = exitTimeInput.value;
        
        if (exitTime <= entryTime) {
            exitTimeInput.setCustomValidity('Exit time must be after entry time');
            TradeLogger.utils.showToast('Exit time must be after entry time', 'warning');
            valid = false;
        } else {
            exitTimeInput.setCustomValidity('');
        }
    }
    
    if (!valid) {
        TradeLogger.utils.showToast('Please correct the validation errors', 'danger');
    }
    
    return valid;
}

// Status and Outcome Logic
function initializeStatusLogic() {
    const statusSelect = document.getElementById('status');
    const outcomeSelect = document.getElementById('outcome');
    const exitTimeInput = document.getElementById('exit_time');
    
    if (!statusSelect || !outcomeSelect) return;
    
    // When status changes, adjust outcome options
    statusSelect.addEventListener('change', function() {
        const status = this.value;
        
        if (status === 'open') {
            // For open trades, outcome should be empty
            outcomeSelect.value = '';
            outcomeSelect.disabled = true;
            
            // Clear exit time for open trades
            if (exitTimeInput) {
                exitTimeInput.value = '';
            }
        } else {
            // For closed/cancelled trades, enable outcome selection
            outcomeSelect.disabled = false;
            
            if (status === 'cancelled') {
                // For cancelled trades, no outcome needed
                outcomeSelect.value = '';
            }
        }
    });
    
    // When outcome changes, adjust status
    outcomeSelect.addEventListener('change', function() {
        const outcome = this.value;
        
        if (outcome && statusSelect.value === 'open') {
            // If outcome is selected, status should be closed
            statusSelect.value = 'closed';
        }
    });
    
    // Trigger initial setup
    statusSelect.dispatchEvent(new Event('change'));
}

// Image Upload Management
function initializeImageUpload() {
    const uploadZone = document.getElementById('screenshotUploadZone');
    const fileInput = document.getElementById('screenshot');
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
    const uploadZone = document.getElementById('screenshotUploadZone');
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
    const uploadZone = document.getElementById('screenshotUploadZone');
    const imagePreview = document.getElementById('imagePreview');
    const fileInput = document.getElementById('screenshot');
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

// Trade Statistics Chart (for analytics)
function initializeTradeChart(chartData, chartType = 'outcome') {
    const ctx = document.getElementById('tradeChart');
    if (!ctx || !chartData) return;
    
    let config;
    
    if (chartType === 'outcome') {
        config = {
            type: 'doughnut',
            data: {
                labels: ['Wins', 'Losses', 'Break-even'],
                datasets: [{
                    data: [
                        chartData.winning_trades || 0,
                        chartData.losing_trades || 0,
                        chartData.breakeven_trades || 0
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
    } else if (chartType === 'monthly') {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        config = {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Trades',
                    data: chartData.monthly_data || new Array(12).fill(0),
                    backgroundColor: 'rgba(56, 116, 255, 0.8)',
                    borderColor: 'rgba(56, 116, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        };
    }
    
    new Chart(ctx, config);
}

// RRR Calculator (optional helper)
function calculateRRR(entryPrice, stopLoss, takeProfit, direction) {
    if (!entryPrice || !stopLoss || !takeProfit) return null;
    
    let risk, reward;
    
    if (direction === 'long') {
        risk = entryPrice - stopLoss;
        reward = takeProfit - entryPrice;
    } else {
        risk = stopLoss - entryPrice;
        reward = entryPrice - takeProfit;
    }
    
    if (risk <= 0) return null;
    
    return reward / risk;
}

// Auto-calculate RRR when prices change (optional feature)
function enableRRRCalculation() {
    const entryInput = document.getElementById('entry_price');
    const slInput = document.getElementById('sl');
    const tpInput = document.getElementById('tp');
    const directionSelect = document.getElementById('direction');
    const rrrInput = document.getElementById('rrr');
    
    if (!entryInput || !slInput || !tpInput || !directionSelect || !rrrInput) return;
    
    function updateRRR() {
        const entry = parseFloat(entryInput.value || 0);
        const sl = parseFloat(slInput.value || 0);
        const tp = parseFloat(tpInput.value || 0);
        const direction = directionSelect.value;
        
        if (entry && sl && tp && direction) {
            const rrr = calculateRRR(entry, sl, tp, direction);
            if (rrr && rrr > 0) {
                rrrInput.value = rrr.toFixed(2);
            }
        }
    }
    
    [entryInput, slInput, tpInput, directionSelect].forEach(element => {
        element.addEventListener('input', updateRRR);
        element.addEventListener('change', updateRRR);
    });
}

// Utility Functions
function confirmTradeDelete(tradeId, tradeName) {
    if (confirm(`Are you sure you want to delete this trade?\n\n${tradeName}\n\nThis action cannot be undone.`)) {
        // Create and submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${TradeLogger.baseUrl}/views/trades/delete.php`;
        
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
        idInput.value = tradeId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Export for global access
window.TradeManager = {
    validatePriceLevels,
    calculateRRR,
    confirmTradeDelete,
    initializeTradeChart,
    enableRRRCalculation
};