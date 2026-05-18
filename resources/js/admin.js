import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

const body = document.body;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

const resolveInputName = (errorKey) => {
    if (!errorKey.includes('.')) {
        return errorKey;
    }

    const [root, ...segments] = errorKey.split('.');

    return `${root}${segments.map((segment) => `[${segment}]`).join('')}`;
};

const clearFormErrors = (form) => {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    form.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });
};

const applyFormErrors = (form, errors = {}) => {
    Object.entries(errors).forEach(([key, messages]) => {
        const field = form.querySelector(`[name="${resolveInputName(key)}"]`);
        const errorElement = form.querySelector(`[data-error-for="${key}"]`);

        field?.classList.add('is-invalid');

        if (errorElement) {
            errorElement.textContent = Array.isArray(messages) ? messages[0] : messages;
        }
    });
};

const submitAjaxForm = async (form) => {
    clearFormErrors(form);

    const submitButton = form.querySelector('[type="submit"]');
    submitButton?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: new FormData(form),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            if (response.status === 422 && payload.data?.errors) {
                applyFormErrors(form, payload.data.errors);
            }

            throw new Error(payload.message || 'Unable to complete the request');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
        } else if (form.dataset.ajaxForm === 'customer-document') {
            window.location.reload();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    } finally {
        submitButton?.removeAttribute('disabled');
    }
};

document.querySelectorAll('[data-ajax-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitAjaxForm(form);
    });
});

const customersTableElement = document.getElementById('customers-table');
let customersTable = null;

if (customersTableElement && window.jQuery?.fn?.DataTable) {
    customersTable = window.jQuery(customersTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: customersTableElement.dataset.source,
            data: (payload) => {
                payload.status = document.getElementById('customer-status-filter')?.value || '';
            },
        },
        order: [[7, 'desc']],
        columns: [
            { data: 'customer_code', name: 'customer_code' },
            { data: 'name', name: 'name' },
            { data: 'mobile', name: 'mobile' },
            { data: 'city', name: 'city', defaultContent: '-' },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'enrollments_count', name: 'enrollments_count', searchable: false },
            { data: 'documents_count', name: 'documents_count', searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    document.getElementById('customer-status-filter')?.addEventListener('change', () => {
        customersTable?.ajax.reload();
    });
}

const schemeTypeControl = document.querySelector('[data-scheme-type]');

const refreshSchemeTypeFields = () => {
    if (!schemeTypeControl) {
        return;
    }

    const selectedType = schemeTypeControl.value;

    document.querySelectorAll('[data-scheme-field]').forEach((wrapper) => {
        const isVisible = wrapper.dataset.schemeField === selectedType;

        wrapper.classList.toggle('d-none', !isVisible);
        wrapper.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = !isVisible;

            if (!isVisible) {
                field.classList.remove('is-invalid');
            }
        });
    });
};

schemeTypeControl?.addEventListener('change', refreshSchemeTypeFields);
refreshSchemeTypeFields();

const schemesTableElement = document.getElementById('chit-schemes-table');
let schemesTable = null;

if (schemesTableElement && window.jQuery?.fn?.DataTable) {
    schemesTable = window.jQuery(schemesTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: schemesTableElement.dataset.source,
            data: (payload) => {
                payload.scheme_type = document.getElementById('scheme-type-filter')?.value || '';
                payload.status = document.getElementById('scheme-status-filter')?.value || '';
            },
        },
        order: [[7, 'desc']],
        columns: [
            { data: 'scheme_code', name: 'scheme_code' },
            { data: 'name', name: 'name' },
            { data: 'scheme_type_label', name: 'scheme_type' },
            { data: 'amount_summary', name: 'monthly_amount', searchable: false },
            { data: 'duration_months', name: 'duration_months' },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'enrollments_count', name: 'enrollments_count', searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    document.getElementById('scheme-type-filter')?.addEventListener('change', () => {
        schemesTable?.ajax.reload();
    });

    document.getElementById('scheme-status-filter')?.addEventListener('change', () => {
        schemesTable?.ajax.reload();
    });
}

const enrollmentSchemeControl = document.querySelector('[data-enrollment-scheme]');
const enrollmentStartDateControl = document.querySelector('[data-enrollment-start-date]');
const enrollmentDueDateControl = document.querySelector('[data-enrollment-due-date]');
const enrollmentMaturityDateControl = document.querySelector('[data-enrollment-maturity-date]');
const enrollmentMonthlyAmountControl = document.querySelector('[data-enrollment-monthly-amount]');
const enrollmentAmountHelp = document.querySelector('[data-enrollment-amount-help]');
const enrollmentSchemeInfo = document.querySelector('[data-enrollment-scheme-info]');

const parseSelectedEnrollmentScheme = () => {
    const option = enrollmentSchemeControl?.selectedOptions?.[0];

    if (!option?.dataset.scheme) {
        return null;
    }

    try {
        return JSON.parse(option.dataset.scheme);
    } catch {
        return null;
    }
};

const formatDateInput = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const addMonthsNoOverflow = (date, months) => {
    const next = new Date(date.getTime());
    const originalDay = next.getDate();

    next.setDate(1);
    next.setMonth(next.getMonth() + months);
    const lastDay = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
    next.setDate(Math.min(originalDay, lastDay));

    return next;
};

const refreshEnrollmentCalculatedFields = () => {
    if (!enrollmentSchemeControl || !enrollmentStartDateControl) {
        return;
    }

    const scheme = parseSelectedEnrollmentScheme();
    const startDateValue = enrollmentStartDateControl.value;

    if (startDateValue) {
        const startDate = new Date(`${startDateValue}T00:00:00`);

        if (enrollmentDueDateControl) {
            enrollmentDueDateControl.value = startDate.getDate();
        }

        if (scheme?.duration_months && enrollmentMaturityDateControl) {
            enrollmentMaturityDateControl.value = formatDateInput(addMonthsNoOverflow(startDate, Number(scheme.duration_months)));
        }
    }

    if (!scheme || !enrollmentMonthlyAmountControl) {
        return;
    }

    const type = scheme.scheme_type;

    enrollmentMonthlyAmountControl.readOnly = type !== 'flexible_amount';

    if (type === 'fixed_amount') {
        enrollmentMonthlyAmountControl.value = Number(scheme.monthly_amount || 0).toFixed(2);
        enrollmentAmountHelp.textContent = 'Fixed amount scheme. Monthly amount is controlled by the scheme.';
        enrollmentSchemeInfo.textContent = `Duration ${scheme.duration_months} months. Fixed monthly amount Rs. ${Number(scheme.monthly_amount || 0).toFixed(2)}.`;
    } else if (type === 'flexible_amount') {
        if (!enrollmentMonthlyAmountControl.value) {
            enrollmentMonthlyAmountControl.value = Number(scheme.min_amount || 0).toFixed(2);
        }

        enrollmentMonthlyAmountControl.min = scheme.min_amount || 0;
        enrollmentMonthlyAmountControl.max = scheme.max_amount || '';
        enrollmentAmountHelp.textContent = `Allowed range: Rs. ${Number(scheme.min_amount || 0).toFixed(2)} to Rs. ${Number(scheme.max_amount || 0).toFixed(2)}.`;
        enrollmentSchemeInfo.textContent = `Duration ${scheme.duration_months} months. Flexible monthly amount allowed.`;
    } else if (type === 'gold_weight') {
        enrollmentMonthlyAmountControl.value = '';
        enrollmentAmountHelp.textContent = 'Gold weight scheme. Amount can be calculated later from gold rate.';
        enrollmentSchemeInfo.textContent = `Duration ${scheme.duration_months} months. Gold weight ${Number(scheme.gold_weight || 0).toFixed(3)} g.`;
    }
};

enrollmentSchemeControl?.addEventListener('change', refreshEnrollmentCalculatedFields);
enrollmentStartDateControl?.addEventListener('change', refreshEnrollmentCalculatedFields);
refreshEnrollmentCalculatedFields();

const enrollmentsTableElement = document.getElementById('chit-enrollments-table');
let enrollmentsTable = null;

if (enrollmentsTableElement && window.jQuery?.fn?.DataTable) {
    enrollmentsTable = window.jQuery(enrollmentsTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: enrollmentsTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('enrollment-customer-filter')?.value || '';
                payload.scheme_id = document.getElementById('enrollment-scheme-filter')?.value || '';
                payload.assigned_staff_id = document.getElementById('enrollment-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('enrollment-branch-filter')?.value || '';
                payload.status = document.getElementById('enrollment-status-filter')?.value || '';
                payload.from_date = document.getElementById('enrollment-from-filter')?.value || '';
                payload.to_date = document.getElementById('enrollment-to-filter')?.value || '';
            },
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'chit_no', name: 'chit_no' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'scheme_name', name: 'scheme.name' },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'staff_name', name: 'assignedStaff.name' },
            { data: 'start_date', name: 'start_date' },
            { data: 'maturity_date', name: 'maturity_date' },
            {
                data: 'total_payable',
                name: 'total_payable',
                className: 'text-end',
                render: (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
            },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'enrollment-customer-filter',
        'enrollment-scheme-filter',
        'enrollment-staff-filter',
        'enrollment-branch-filter',
        'enrollment-status-filter',
        'enrollment-from-filter',
        'enrollment-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            enrollmentsTable?.ajax.reload();
        });
    });
}

const installmentsTableElement = document.getElementById('installments-table');
let installmentsTable = null;

if (installmentsTableElement && window.jQuery?.fn?.DataTable) {
    const formatMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    installmentsTable = window.jQuery(installmentsTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: installmentsTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('installment-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('installment-enrollment-filter')?.value || '';
                payload.assigned_staff_id = document.getElementById('installment-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('installment-branch-filter')?.value || '';
                payload.status = document.getElementById('installment-status-filter')?.value || '';
                payload.from_date = document.getElementById('installment-from-filter')?.value || '';
                payload.to_date = document.getElementById('installment-to-filter')?.value || '';
            },
        },
        order: [[4, 'asc']],
        columns: [
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'customer_name', name: 'enrollment.customer.name' },
            { data: 'scheme_name', name: 'enrollment.scheme.name' },
            { data: 'installment_no', name: 'installment_no' },
            { data: 'due_date', name: 'due_date' },
            { data: 'due_amount', name: 'due_amount', className: 'text-end', render: formatMoney },
            { data: 'paid_amount', name: 'paid_amount', className: 'text-end', render: formatMoney },
            { data: 'balance_amount', name: 'balance_amount', className: 'text-end', render: formatMoney },
            { data: 'late_fee', name: 'late_fee', className: 'text-end', render: formatMoney },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'installment-customer-filter',
        'installment-enrollment-filter',
        'installment-staff-filter',
        'installment-branch-filter',
        'installment-status-filter',
        'installment-from-filter',
        'installment-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            installmentsTable?.ajax.reload();
        });
    });
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-customer-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.customerAction;
    const isDelete = action === 'delete';
    const result = await Swal.fire({
        icon: 'warning',
        title: isDelete ? 'Delete customer?' : 'Deactivate customer?',
        text: isDelete ? 'This is allowed only when the customer has no chit accounts.' : 'The customer will be marked inactive.',
        showCancelButton: true,
        confirmButtonText: isDelete ? 'Delete' : 'Deactivate',
        confirmButtonColor: isDelete ? '#dc3545' : '#d9a441',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('_method', button.dataset.method || 'POST');

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors)[0]?.[0] : null;

            throw new Error(firstError || payload.message || 'Unable to complete the request');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (customersTable) {
            customersTable.ajax.reload(null, false);
        } else if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-installment-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.installmentAction;
    const isRegenerate = action === 'regenerate';
    const result = await Swal.fire({
        icon: 'warning',
        title: isRegenerate ? 'Regenerate schedule?' : 'Mark overdue installments?',
        text: isRegenerate ? 'This is blocked when payments exist for the enrollment.' : 'Pending installments with past due dates will be marked overdue.',
        showCancelButton: true,
        confirmButtonText: isRegenerate ? 'Regenerate' : 'Update',
        confirmButtonColor: isRegenerate ? '#d9a441' : '#dc3545',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors)[0]?.[0] : null;

            throw new Error(firstError || payload.message || 'Unable to complete the request');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (installmentsTable) {
            installmentsTable.ajax.reload(null, false);
        } else {
            window.location.reload();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-enrollment-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.enrollmentAction;

    if (action === 'cancel') {
        const modalElement = document.getElementById('enrollmentCancelModal');
        const form = modalElement?.querySelector('form');

        if (modalElement && form) {
            clearFormErrors(form);
            form.action = button.dataset.url;
            Modal.getOrCreateInstance(modalElement).show();
        }

        return;
    }

    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete enrollment?',
        text: 'This is blocked when payments exist.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('_method', button.dataset.method || 'DELETE');

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors)[0]?.[0] : null;

            throw new Error(firstError || payload.message || 'Unable to complete the request');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (enrollmentsTable) {
            enrollmentsTable.ajax.reload(null, false);
        } else if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-scheme-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.schemeAction;
    const isDelete = action === 'delete';
    const targetStatus = button.dataset.status;
    const result = await Swal.fire({
        icon: 'warning',
        title: isDelete ? 'Delete scheme?' : `Mark scheme ${targetStatus}?`,
        text: isDelete ? 'This is blocked when active enrollments exist.' : 'Scheme availability will be updated immediately.',
        showCancelButton: true,
        confirmButtonText: isDelete ? 'Delete' : 'Update status',
        confirmButtonColor: isDelete ? '#dc3545' : '#d9a441',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('_method', button.dataset.method || 'POST');

    if (targetStatus) {
        formData.append('status', targetStatus);
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors)[0]?.[0] : null;

            throw new Error(firstError || payload.message || 'Unable to complete the request');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (schemesTable) {
            schemesTable.ajax.reload(null, false);
        } else if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});
