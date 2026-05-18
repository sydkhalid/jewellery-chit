import ApexCharts from 'apexcharts';
import Swal from 'sweetalert2';

const body = document.body;

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        body.classList.add('admin-sidebar-open');
    });
});

document.querySelectorAll('[data-sidebar-dismiss]').forEach((button) => {
    button.addEventListener('click', () => {
        body.classList.remove('admin-sidebar-open');
    });
});

const flash = window.adminFlash || {};

if (flash.success) {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: flash.success,
        timer: 2200,
        showConfirmButton: false,
    });
}

if (flash.error || flash.validation) {
    Swal.fire({
        icon: 'error',
        title: 'Action needed',
        text: flash.error || flash.validation,
    });
}

const chartDataElement = document.getElementById('dashboard-chart-data');

const parseChartData = () => {
    if (!chartDataElement) {
        return null;
    }

    try {
        return JSON.parse(chartDataElement.textContent);
    } catch {
        return null;
    }
};

const chartData = parseChartData();

const moneyFormatter = new Intl.NumberFormat('en-IN', {
    maximumFractionDigits: 0,
});

const renderChart = (selector, options) => {
    const element = document.querySelector(selector);

    if (!element || !chartData || typeof ApexCharts === 'undefined') {
        return;
    }

    element.textContent = '';
    new ApexCharts(element, options).render();
};

if (chartData) {
    renderChart('#staffWiseCollectionChart', {
        chart: {
            type: 'bar',
            height: 305,
            toolbar: { show: false },
        },
        colors: ['#1d4ed8'],
        plotOptions: {
            bar: {
                borderRadius: 5,
                columnWidth: '48%',
            },
        },
        dataLabels: { enabled: false },
        series: [
            {
                name: 'Collection',
                data: chartData.staffWiseCollection.series,
            },
        ],
        xaxis: {
            categories: chartData.staffWiseCollection.labels,
        },
        yaxis: {
            labels: {
                formatter: (value) => `Rs. ${moneyFormatter.format(value)}`,
            },
        },
    });

    renderChart('#schemeWiseCollectionChart', {
        chart: {
            type: 'donut',
            height: 305,
        },
        labels: chartData.schemeWiseCollection.labels,
        series: chartData.schemeWiseCollection.series,
        colors: ['#d9a441', '#15803d', '#0e7490', '#7e22ce'],
        legend: {
            position: 'bottom',
        },
    });

    renderChart('#monthlyCollectionTrendChart', {
        chart: {
            type: 'area',
            height: 305,
            toolbar: { show: false },
        },
        colors: ['#15803d'],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3,
        },
        series: [
            {
                name: 'Collection',
                data: chartData.monthlyCollectionTrend.series,
            },
        ],
        xaxis: {
            categories: chartData.monthlyCollectionTrend.labels,
        },
        yaxis: {
            labels: {
                formatter: (value) => `Rs. ${moneyFormatter.format(value)}`,
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.35,
                opacityTo: 0.04,
            },
        },
    });

    renderChart('#paymentModeCollectionChart', {
        chart: {
            type: 'radialBar',
            height: 305,
        },
        labels: chartData.paymentModeCollection.labels,
        series: chartData.paymentModeCollection.series,
        colors: ['#111827', '#1d4ed8', '#d9a441', '#0e7490'],
        plotOptions: {
            radialBar: {
                dataLabels: {
                    total: {
                        show: true,
                        label: 'Modes',
                    },
                },
            },
        },
    });
}
