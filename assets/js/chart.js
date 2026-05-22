document.addEventListener('DOMContentLoaded', function() {
    const chartColors = {
        green: '#10b981',
        red: '#ef4444',
        blue: '#3b82f6',
        yellow: '#f59e0b',
        purple: '#8b5cf6',
        gray: '#94a3b8',
    };

    // Chart Keuangan (bar + line)
    const chartKeuangan = document.getElementById('chartKeuangan');
    if (chartKeuangan) {
        try {
            new Chart(chartKeuangan, {
                type: 'bar',
                data: {
                    labels: JSON.parse(chartKeuangan.getAttribute('data-labels') || '[]'),
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: JSON.parse(chartKeuangan.getAttribute('data-pemasukan') || '[]'),
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: chartColors.green,
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Pengeluaran',
                            data: JSON.parse(chartKeuangan.getAttribute('data-pengeluaran') || '[]'),
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: chartColors.red,
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 12, padding: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp' + value.toLocaleString('id-ID');
                                }
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        } catch(e) { console.log('Chart Keuangan error'); }
    }

    // Chart Beban (pie/doughnut)
    const chartBeban = document.getElementById('chartBeban');
    if (chartBeban) {
        try {
            const labels = JSON.parse(chartBeban.getAttribute('data-labels') || '[]');
            const values = JSON.parse(chartBeban.getAttribute('data-values') || '[]');
            const colors = [
                '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6',
                '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'
            ];

            new Chart(chartBeban, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 8,
                                font: { size: 10 }
                            }
                        }
                    },
                    cutout: '60%',
                }
            });
        } catch(e) { console.log('Chart Beban error'); }
    }

    // Chart Publik
    const chartPublik = document.getElementById('chartPublik');
    if (chartPublik) {
        try {
            new Chart(chartPublik, {
                type: 'line',
                data: {
                    labels: JSON.parse(chartPublik.getAttribute('data-labels') || '[]'),
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: JSON.parse(chartPublik.getAttribute('data-pemasukan') || '[]'),
                            borderColor: chartColors.green,
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                        },
                        {
                            label: 'Pengeluaran',
                            data: JSON.parse(chartPublik.getAttribute('data-pengeluaran') || '[]'),
                            borderColor: chartColors.red,
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        } catch(e) { console.log('Chart Publik error'); }
    }
});
