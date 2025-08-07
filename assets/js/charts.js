/**
 * Analytics Charts JavaScript
 */

const AnalyticsCharts = {
    // Chart instances
    monthlyChart: null,
    outcomeChart: null,
    equityChart: null,
    
    // Chart colors
    colors: {
        primary: 'rgba(56, 116, 255, 0.8)',
        primaryBorder: 'rgba(56, 116, 255, 1)',
        success: 'rgba(37, 176, 3, 0.8)',
        successBorder: 'rgba(37, 176, 3, 1)',
        danger: 'rgba(250, 59, 29, 0.8)',
        dangerBorder: 'rgba(250, 59, 29, 1)',
        warning: 'rgba(229, 120, 11, 0.8)',
        warningBorder: 'rgba(229, 120, 11, 1)',
        info: 'rgba(0, 151, 235, 0.8)',
        infoBorder: 'rgba(0, 151, 235, 1)',
        gray: 'rgba(110, 120, 145, 0.8)',
        grayBorder: 'rgba(110, 120, 145, 1)'
    },

    // Initialize all charts
    initializeAllCharts: function(data) {
        this.initializeMonthlyTradesChart(data);
        this.initializeOutcomeChart(data);
        this.initializeEquityChart(data);
    },

    // Monthly trades bar chart
    initializeMonthlyTradesChart: function(data) {
        const ctx = document.getElementById('monthlyTradesChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.monthlyChart) {
            this.monthlyChart.destroy();
        }

        const config = {
            type: 'bar',
            data: {
                labels: data.monthlyLabels,
                datasets: [{
                    label: 'Trades',
                    data: data.monthlyTrades,
                    backgroundColor: this.colors.primary,
                    borderColor: this.colors.primaryBorder,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: this.colors.primaryBorder,
                        borderWidth: 1,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return `${value} trade${value !== 1 ? 's' : ''}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#6e7891'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(110, 120, 145, 0.1)'
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            stepSize: 1,
                            color: '#6e7891',
                            callback: function(value) {
                                return Math.floor(value) === value ? value : '';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        };

        this.monthlyChart = new Chart(ctx, config);
    },

    // Outcome pie chart
    initializeOutcomeChart: function(data) {
        const ctx = document.getElementById('outcomeChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.outcomeChart) {
            this.outcomeChart.destroy();
        }

        const total = data.outcomeData.wins + data.outcomeData.losses + data.outcomeData.breakeven;
        
        if (total === 0) {
            // Show empty state
            ctx.getContext('2d').fillText('No completed trades yet', 10, 50);
            return;
        }

        const config = {
            type: 'doughnut',
            data: {
                labels: ['Wins', 'Losses', 'Break-even'],
                datasets: [{
                    data: [
                        data.outcomeData.wins,
                        data.outcomeData.losses,
                        data.outcomeData.breakeven
                    ],
                    backgroundColor: [
                        this.colors.success,
                        this.colors.danger,
                        this.colors.warning
                    ],
                    borderColor: [
                        this.colors.successBorder,
                        this.colors.dangerBorder,
                        this.colors.warningBorder
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        display: false // We have custom legend in HTML
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderWidth: 1,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1000
                }
            }
        };

        this.outcomeChart = new Chart(ctx, config);
    },

    // Equity curve line chart
    initializeEquityChart: function(data) {
        const ctx = document.getElementById('equityChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.equityChart) {
            this.equityChart.destroy();
        }

        // Calculate equity values based on account size
        const accountSize = data.accountSize || 10000; // Default to $10k if not set
        const equityValues = data.equityData.map(pnl => {
            const equityPercent = pnl / 100; // Convert percentage to decimal
            return accountSize * (1 + equityPercent);
        });

        // Add starting point
        equityValues.unshift(accountSize);
        const labels = ['Start', ...data.monthlyLabels];

        const config = {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Account Value',
                    data: equityValues,
                    borderColor: this.colors.primaryBorder,
                    backgroundColor: this.colors.primary,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.1,
                    pointBackgroundColor: this.colors.primaryBorder,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: this.colors.primaryBorder,
                        borderWidth: 1,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const startValue = accountSize;
                                const change = value - startValue;
                                const changePercent = ((change / startValue) * 100).toFixed(2);
                                
                                return [
                                    `Account Value: $${value.toLocaleString()}`,
                                    `Change: ${change >= 0 ? '+' : ''}$${change.toLocaleString()} (${changePercent}%)`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#6e7891'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(110, 120, 145, 0.1)'
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#6e7891',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart'
                }
            }
        };

        this.equityChart = new Chart(ctx, config);
    },

    // Update charts with new data
    updateCharts: function(newData) {
        if (this.monthlyChart) {
            this.monthlyChart.data.datasets[0].data = newData.monthlyTrades;
            this.monthlyChart.update();
        }

        if (this.outcomeChart) {
            this.outcomeChart.data.datasets[0].data = [
                newData.outcomeData.wins,
                newData.outcomeData.losses,
                newData.outcomeData.breakeven
            ];
            this.outcomeChart.update();
        }

        if (this.equityChart && newData.equityData) {
            const accountSize = newData.accountSize || 10000;
            const equityValues = newData.equityData.map(pnl => {
                const equityPercent = pnl / 100;
                return accountSize * (1 + equityPercent);
            });
            equityValues.unshift(accountSize);
            
            this.equityChart.data.datasets[0].data = equityValues;
            this.equityChart.update();
        }
    },

    // Destroy all charts
    destroyAllCharts: function() {
        if (this.monthlyChart) {
            this.monthlyChart.destroy();
            this.monthlyChart = null;
        }
        if (this.outcomeChart) {
            this.outcomeChart.destroy();
            this.outcomeChart = null;
        }
        if (this.equityChart) {
            this.equityChart.destroy();
            this.equityChart = null;
        }
    },

    // Resize charts (useful for responsive behavior)
    resizeCharts: function() {
        if (this.monthlyChart) this.monthlyChart.resize();
        if (this.outcomeChart) this.outcomeChart.resize();
        if (this.equityChart) this.equityChart.resize();
    }
};

// Handle window resize
window.addEventListener('resize', function() {
    setTimeout(() => {
        AnalyticsCharts.resizeCharts();
    }, 100);
});

// Export for global access
window.AnalyticsCharts = AnalyticsCharts;