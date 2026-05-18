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

const paymentCustomerControl = document.querySelector('[data-payment-customer]');
const paymentEnrollmentControl = document.querySelector('[data-payment-enrollment]');
const paymentInstallmentControl = document.querySelector('[data-payment-installment]');
const paymentTypeControl = document.querySelector('[data-payment-type]');
const paymentAmountControl = document.querySelector('[data-payment-amount]');
const paymentLateFeeControl = document.querySelector('[data-payment-late-fee]');
const paymentModeControl = document.querySelector('[data-payment-mode]');
const paymentTransactionControl = document.querySelector('[data-payment-transaction]');
const paymentTransactionHelp = document.querySelector('[data-payment-transaction-help]');
const paymentSummary = document.querySelector('[data-payment-summary]');

const parseOptionJson = (option, key) => {
    if (!option?.dataset?.[key]) {
        return null;
    }

    try {
        return JSON.parse(option.dataset[key]);
    } catch {
        return null;
    }
};

const selectedPaymentEnrollment = () => parseOptionJson(paymentEnrollmentControl?.selectedOptions?.[0], 'enrollment');
const selectedPaymentInstallment = () => parseOptionJson(paymentInstallmentControl?.selectedOptions?.[0], 'installment');

const refreshPaymentEnrollmentOptions = () => {
    if (!paymentCustomerControl || !paymentEnrollmentControl) {
        return;
    }

    const customerId = paymentCustomerControl.value;

    paymentEnrollmentControl.querySelectorAll('option[data-customer]').forEach((option) => {
        option.hidden = !!customerId && option.dataset.customer !== customerId;
    });

    if (paymentEnrollmentControl.selectedOptions?.[0]?.hidden) {
        paymentEnrollmentControl.value = '';
    }
};

const refreshPaymentInstallmentOptions = () => {
    if (!paymentEnrollmentControl || !paymentInstallmentControl) {
        return;
    }

    const enrollmentId = paymentEnrollmentControl.value;

    paymentInstallmentControl.querySelectorAll('option[data-enrollment]').forEach((option) => {
        option.hidden = !!enrollmentId && option.dataset.enrollment !== enrollmentId;
    });

    if (paymentInstallmentControl.selectedOptions?.[0]?.hidden) {
        paymentInstallmentControl.value = '';
    }
};

const refreshPaymentModeRequirement = () => {
    if (!paymentModeControl || !paymentTransactionControl) {
        return;
    }

    const selectedCode = paymentModeControl.selectedOptions?.[0]?.dataset.code || '';
    const requiresTransaction = selectedCode && selectedCode !== 'cash';

    paymentTransactionControl.required = requiresTransaction;

    if (paymentTransactionHelp) {
        paymentTransactionHelp.textContent = requiresTransaction ? 'Transaction ID is required for this payment mode.' : 'Optional for cash payments.';
    }
};

const refreshPaymentSummary = () => {
    if (!paymentSummary) {
        return;
    }

    const enrollment = selectedPaymentEnrollment();
    const installment = selectedPaymentInstallment();
    const amount = Number(paymentAmountControl?.value || 0);
    const lateFee = Number(paymentLateFeeControl?.value || 0);
    const total = amount + lateFee;

    if (paymentTypeControl?.value === 'full' && installment && paymentAmountControl && document.activeElement !== paymentAmountControl) {
        paymentAmountControl.value = Number(installment.balance_amount || 0).toFixed(2);
    }

    paymentSummary.innerHTML = [
        enrollment ? `<strong>${enrollment.chit_no}</strong>` : '<strong>No chit selected</strong>',
        installment ? `Installment #${installment.installment_no}: balance Rs. ${Number(installment.balance_amount || 0).toFixed(2)}` : 'Auto select first pending installment',
        `Payment amount Rs. ${Number(paymentAmountControl?.value || 0).toFixed(2)}`,
        `Late fee Rs. ${lateFee.toFixed(2)}`,
        `Total collection Rs. ${total.toFixed(2)}`,
    ].join('<br>');
};

[
    paymentCustomerControl,
    paymentEnrollmentControl,
    paymentInstallmentControl,
    paymentTypeControl,
    paymentAmountControl,
    paymentLateFeeControl,
    paymentModeControl,
].forEach((control) => {
    control?.addEventListener('change', () => {
        refreshPaymentEnrollmentOptions();
        refreshPaymentInstallmentOptions();
        refreshPaymentModeRequirement();
        refreshPaymentSummary();
    });
    control?.addEventListener('input', refreshPaymentSummary);
});

refreshPaymentEnrollmentOptions();
refreshPaymentInstallmentOptions();
refreshPaymentModeRequirement();
refreshPaymentSummary();

const paymentsTableElement = document.getElementById('payments-table');
let paymentsTable = null;

if (paymentsTableElement && window.jQuery?.fn?.DataTable) {
    const formatPaymentMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    paymentsTable = window.jQuery(paymentsTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: paymentsTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('payment-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('payment-enrollment-filter')?.value || '';
                payload.payment_mode_id = document.getElementById('payment-mode-filter')?.value || '';
                payload.staff_id = document.getElementById('payment-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('payment-branch-filter')?.value || '';
                payload.status = document.getElementById('payment-status-filter')?.value || '';
                payload.from_date = document.getElementById('payment-from-filter')?.value || '';
                payload.to_date = document.getElementById('payment-to-filter')?.value || '';
            },
        },
        order: [[3, 'desc']],
        columns: [
            { data: 'payment_no', name: 'payment_no' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'payment_date', name: 'payment_date' },
            { data: 'payment_mode_name', name: 'paymentMode.name' },
            { data: 'amount', name: 'amount', className: 'text-end', render: formatPaymentMoney },
            { data: 'late_fee_amount', name: 'late_fee_amount', className: 'text-end', render: formatPaymentMoney },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: formatPaymentMoney },
            { data: 'staff_name', name: 'staff.name' },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'receipt_no', name: 'receipt.receipt_no', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'payment-customer-filter',
        'payment-enrollment-filter',
        'payment-mode-filter',
        'payment-staff-filter',
        'payment-branch-filter',
        'payment-status-filter',
        'payment-from-filter',
        'payment-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            paymentsTable?.ajax.reload();
        });
    });
}

const receiptsTableElement = document.getElementById('receipts-table');
let receiptsTable = null;

if (receiptsTableElement && window.jQuery?.fn?.DataTable) {
    const formatReceiptMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    receiptsTable = window.jQuery(receiptsTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: receiptsTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('receipt-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('receipt-enrollment-filter')?.value || '';
                payload.payment_mode_id = document.getElementById('receipt-mode-filter')?.value || '';
                payload.staff_id = document.getElementById('receipt-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('receipt-branch-filter')?.value || '';
                payload.status = document.getElementById('receipt-status-filter')?.value || '';
                payload.from_date = document.getElementById('receipt-from-filter')?.value || '';
                payload.to_date = document.getElementById('receipt-to-filter')?.value || '';
            },
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'receipt_no', name: 'receipt_no' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'payment_no', name: 'payment.payment_no' },
            { data: 'receipt_date', name: 'receipt_date' },
            { data: 'payment_mode_name', name: 'payment.paymentMode.name' },
            { data: 'amount', name: 'amount', className: 'text-end', render: formatReceiptMoney },
            { data: 'staff_name', name: 'payment.staff.name' },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'print_count', name: 'print_count', className: 'text-end' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'receipt-customer-filter',
        'receipt-enrollment-filter',
        'receipt-mode-filter',
        'receipt-staff-filter',
        'receipt-branch-filter',
        'receipt-status-filter',
        'receipt-from-filter',
        'receipt-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            receiptsTable?.ajax.reload();
        });
    });
}

const ledgersTableElement = document.getElementById('ledgers-table');
let ledgersTable = null;

if (ledgersTableElement && window.jQuery?.fn?.DataTable) {
    const formatLedgerMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    ledgersTable = window.jQuery(ledgersTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ledgersTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('ledger-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('ledger-enrollment-filter')?.value || '';
                payload.transaction_type = document.getElementById('ledger-type-filter')?.value || '';
                payload.branch_id = document.getElementById('ledger-branch-filter')?.value || '';
                payload.staff_id = document.getElementById('ledger-staff-filter')?.value || '';
                payload.from_date = document.getElementById('ledger-from-filter')?.value || '';
                payload.to_date = document.getElementById('ledger-to-filter')?.value || '';
            },
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'transaction_type_badge', name: 'transaction_type' },
            { data: 'debit', name: 'debit', className: 'text-end', render: formatLedgerMoney },
            { data: 'credit', name: 'credit', className: 'text-end', render: formatLedgerMoney },
            { data: 'balance', name: 'balance', className: 'text-end', render: formatLedgerMoney },
            { data: 'reference', name: 'reference_id', orderable: false },
            { data: 'remarks', name: 'remarks' },
            { data: 'created_by_name', name: 'creator.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'ledger-customer-filter',
        'ledger-enrollment-filter',
        'ledger-type-filter',
        'ledger-branch-filter',
        'ledger-staff-filter',
        'ledger-from-filter',
        'ledger-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            ledgersTable?.ajax.reload();
        });
    });
}

const pendingDuesTableElement = document.getElementById('pending-dues-table');
let pendingDuesTable = null;

if (pendingDuesTableElement && window.jQuery?.fn?.DataTable) {
    const formatPendingMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    pendingDuesTable = window.jQuery(pendingDuesTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pendingDuesTableElement.dataset.source,
            data: (payload) => {
                payload.due_type = document.getElementById('pending-due-type-filter')?.value || '';
                payload.customer_id = document.getElementById('pending-customer-filter')?.value || '';
                payload.staff_id = document.getElementById('pending-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('pending-branch-filter')?.value || '';
                payload.scheme_id = document.getElementById('pending-scheme-filter')?.value || '';
                payload.status = document.getElementById('pending-status-filter')?.value || '';
                payload.followup_status = document.getElementById('pending-followup-filter')?.value || '';
                payload.from_date = document.getElementById('pending-from-filter')?.value || '';
                payload.to_date = document.getElementById('pending-to-filter')?.value || '';
            },
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'select_box', name: 'select_box', orderable: false, searchable: false },
            { data: 'due_date', name: 'due_date' },
            { data: 'customer_code', name: 'enrollment.customer.customer_code' },
            { data: 'customer_name', name: 'enrollment.customer.name' },
            { data: 'mobile', name: 'enrollment.customer.mobile' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'scheme_name', name: 'enrollment.scheme.name' },
            { data: 'installment_no', name: 'installment_no' },
            { data: 'due_amount', name: 'due_amount', className: 'text-end', render: formatPendingMoney },
            { data: 'paid_amount', name: 'paid_amount', className: 'text-end', render: formatPendingMoney },
            { data: 'balance_amount', name: 'balance_amount', className: 'text-end', render: formatPendingMoney },
            { data: 'late_fee', name: 'late_fee', className: 'text-end', render: formatPendingMoney },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'staff_name', name: 'enrollment.assignedStaff.name' },
            { data: 'branch_name', name: 'enrollment.branch.name' },
            { data: 'followup_badge', name: 'followup_status', orderable: true, searchable: false },
            { data: 'promise_to_pay_date', name: 'promise_to_pay_date' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'pending-due-type-filter',
        'pending-customer-filter',
        'pending-staff-filter',
        'pending-branch-filter',
        'pending-scheme-filter',
        'pending-status-filter',
        'pending-followup-filter',
        'pending-from-filter',
        'pending-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            pendingDuesTable?.ajax.reload();
        });
    });
}

document.querySelector('[data-pending-due-select-all]')?.addEventListener('change', (event) => {
    document.querySelectorAll('[data-pending-due-select]').forEach((checkbox) => {
        checkbox.checked = event.target.checked;
    });
});

const maturityClosingsTableElement = document.getElementById('maturity-closings-table');
let maturityClosingsTable = null;

if (maturityClosingsTableElement && window.jQuery?.fn?.DataTable) {
    const formatMaturityMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    maturityClosingsTable = window.jQuery(maturityClosingsTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: maturityClosingsTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('maturity-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('maturity-enrollment-filter')?.value || '';
                payload.closure_type = document.getElementById('maturity-type-filter')?.value || '';
                payload.status = document.getElementById('maturity-status-filter')?.value || '';
                payload.from_date = document.getElementById('maturity-from-filter')?.value || '';
                payload.to_date = document.getElementById('maturity-to-filter')?.value || '';
            },
        },
        order: [[12, 'desc']],
        columns: [
            { data: 'closure_no', name: 'closure_no' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'scheme_name', name: 'enrollment.scheme.name' },
            { data: 'closure_type_badge', name: 'closure_type', searchable: false },
            { data: 'total_paid', name: 'total_paid', className: 'text-end', render: formatMaturityMoney },
            { data: 'shop_bonus', name: 'shop_bonus', className: 'text-end', render: formatMaturityMoney },
            { data: 'final_maturity_value', name: 'final_maturity_value', className: 'text-end', render: formatMaturityMoney },
            { data: 'refund_amount', name: 'refund_amount', className: 'text-end', render: formatMaturityMoney },
            { data: 'jewellery_adjustment_amount', name: 'jewellery_adjustment_amount', className: 'text-end', render: formatMaturityMoney },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'approver_name', name: 'approver.name' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'maturity-customer-filter',
        'maturity-enrollment-filter',
        'maturity-type-filter',
        'maturity-status-filter',
        'maturity-from-filter',
        'maturity-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            maturityClosingsTable?.ajax.reload();
        });
    });
}

const maturityForm = document.querySelector('[data-maturity-form]');
const maturityEnrollmentControl = document.querySelector('[data-maturity-enrollment]');
const maturityDeductionsControl = document.querySelector('[data-maturity-deductions]');
const maturitySummaryPanel = document.querySelector('[data-maturity-summary]');
let maturitySummaryData = null;

const renderMaturitySummary = (summary) => {
    if (!maturitySummaryPanel || !summary) {
        return;
    }

    const deductions = Number(maturityDeductionsControl?.value || summary.deductions || 0);
    const totalPaid = Number(summary.total_paid || 0);
    const shopBonus = Number(summary.shop_bonus || 0);
    const finalValue = Math.max(0, totalPaid + shopBonus - deductions);
    const money = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    maturitySummaryPanel.innerHTML = `
        <div class="small text-muted mb-2">${summary.customer?.name || 'Customer'} / ${summary.enrollment?.chit_no || 'Chit'}</div>
        <div class="d-flex justify-content-between mb-1"><span>Total paid</span><strong>${money(totalPaid)}</strong></div>
        <div class="d-flex justify-content-between mb-1"><span>Shop bonus</span><strong>${money(shopBonus)}</strong></div>
        <div class="d-flex justify-content-between mb-1"><span>Deductions</span><strong>${money(deductions)}</strong></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between"><span>Final value</span><strong>${money(finalValue)}</strong></div>
        <div class="small text-muted mt-2">${summary.paid_months || 0} paid months / ${summary.pending_months || 0} pending months</div>
    `;
};

const loadMaturityCalculation = async () => {
    if (!maturityForm || !maturityEnrollmentControl || !maturitySummaryPanel || !maturityEnrollmentControl.value) {
        return;
    }

    const url = maturityForm.dataset.calculateTemplate.replace('__ID__', maturityEnrollmentControl.value);
    maturitySummaryPanel.textContent = 'Calculating maturity value...';

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to calculate maturity value');
        }

        maturitySummaryData = payload.data;
        if (maturityDeductionsControl && (!maturityDeductionsControl.value || Number(maturityDeductionsControl.value) === 0)) {
            maturityDeductionsControl.value = Number(payload.data.deductions || 0).toFixed(2);
        }
        renderMaturitySummary(maturitySummaryData);
    } catch (error) {
        maturitySummaryPanel.textContent = error.message;
    }
};

maturityEnrollmentControl?.addEventListener('change', loadMaturityCalculation);
maturityDeductionsControl?.addEventListener('input', () => renderMaturitySummary(maturitySummaryData));

if (maturityEnrollmentControl?.value) {
    loadMaturityCalculation();
}

const jewelleryInvoicesTableElement = document.getElementById('jewellery-invoices-table');
let jewelleryInvoicesTable = null;

if (jewelleryInvoicesTableElement && window.jQuery?.fn?.DataTable) {
    const formatJewelleryMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const formatJewelleryWeight = (value) => Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 3, maximumFractionDigits: 3 });

    jewelleryInvoicesTable = window.jQuery(jewelleryInvoicesTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: jewelleryInvoicesTableElement.dataset.source,
            data: (payload) => {
                payload.customer_id = document.getElementById('jewellery-customer-filter')?.value || '';
                payload.enrollment_id = document.getElementById('jewellery-enrollment-filter')?.value || '';
                payload.status = document.getElementById('jewellery-status-filter')?.value || '';
                payload.from_date = document.getElementById('jewellery-from-filter')?.value || '';
                payload.to_date = document.getElementById('jewellery-to-filter')?.value || '';
            },
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'invoice_no', name: 'invoice_no' },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'chit_no', name: 'enrollment.chit_no' },
            { data: 'scheme_name', name: 'enrollment.scheme.name' },
            { data: 'invoice_date', name: 'invoice_date' },
            { data: 'gold_rate', name: 'gold_rate', className: 'text-end', render: formatJewelleryMoney },
            { data: 'net_weight', name: 'net_weight', className: 'text-end', render: formatJewelleryWeight },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: formatJewelleryMoney },
            { data: 'chit_adjustment_amount', name: 'chit_adjustment_amount', className: 'text-end', render: formatJewelleryMoney },
            { data: 'balance_payable', name: 'balance_payable', className: 'text-end', render: formatJewelleryMoney },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'jewellery-customer-filter',
        'jewellery-enrollment-filter',
        'jewellery-status-filter',
        'jewellery-from-filter',
        'jewellery-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            jewelleryInvoicesTable?.ajax.reload();
        });
    });
}

const jewelleryForm = document.querySelector('[data-jewellery-form]');
const jewelleryCustomerControl = document.querySelector('[data-jewellery-customer]');
const jewelleryEnrollmentControl = document.querySelector('[data-jewellery-enrollment]');
const jewelleryAdjustmentControl = document.querySelector('[data-jewellery-adjustment]');
const jewelleryAdjustmentHelp = document.querySelector('[data-jewellery-adjustment-help]');
const jewelleryDiscountControl = document.querySelector('[data-jewellery-discount]');
const jewellerySummaryPanel = document.querySelector('[data-jewellery-summary]');
const jewelleryItemsTable = document.querySelector('[data-jewellery-items-table]');
const jewelleryRowTemplate = document.querySelector('[data-jewellery-row-template]');

const jewelleryNumber = (value) => Number(value || 0);
const jewelleryMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const updateJewelleryTotals = () => {
    if (!jewelleryForm || !jewelleryItemsTable) {
        return;
    }

    const totals = {
        gross_weight: 0,
        net_weight: 0,
        making_charge: 0,
        wastage: 0,
        gst_amount: 0,
        item_total: 0,
    };

    jewelleryItemsTable.querySelectorAll('[data-jewellery-item-row]').forEach((row) => {
        const grossWeight = jewelleryNumber(row.querySelector('[data-jewellery-item="gross_weight"]')?.value);
        const netWeight = jewelleryNumber(row.querySelector('[data-jewellery-item="net_weight"]')?.value);
        const rate = jewelleryNumber(row.querySelector('[data-jewellery-item="rate"]')?.value);
        const makingCharge = jewelleryNumber(row.querySelector('[data-jewellery-item="making_charge"]')?.value);
        const wastage = jewelleryNumber(row.querySelector('[data-jewellery-item="wastage"]')?.value);
        const gstAmount = jewelleryNumber(row.querySelector('[data-jewellery-item="gst_amount"]')?.value);
        const itemTotal = Math.max(0, (netWeight * rate) + makingCharge + wastage + gstAmount);

        row.querySelector('[data-jewellery-item-total]').value = itemTotal.toFixed(2);
        totals.gross_weight += grossWeight;
        totals.net_weight += netWeight;
        totals.making_charge += makingCharge;
        totals.wastage += wastage;
        totals.gst_amount += gstAmount;
        totals.item_total += itemTotal;
    });

    const discount = jewelleryNumber(jewelleryDiscountControl?.value);
    const invoiceTotal = Math.max(0, totals.item_total - discount);
    const adjustment = Math.min(jewelleryNumber(jewelleryAdjustmentControl?.value), invoiceTotal);
    const balancePayable = Math.max(0, invoiceTotal - adjustment);

    if (jewelleryAdjustmentControl && jewelleryNumber(jewelleryAdjustmentControl.value) !== adjustment) {
        jewelleryAdjustmentControl.value = adjustment.toFixed(2);
    }

    const setTotal = (name, value, decimals = 2) => {
        const control = jewelleryForm.querySelector(`[data-jewellery-total="${name}"]`);
        if (control) {
            control.value = Number(value || 0).toFixed(decimals);
        }
    };

    setTotal('gross_weight', totals.gross_weight, 3);
    setTotal('net_weight', totals.net_weight, 3);
    setTotal('making_charge', totals.making_charge);
    setTotal('wastage', totals.wastage);
    setTotal('gst_amount', totals.gst_amount);
    setTotal('total_amount', invoiceTotal);
    setTotal('balance_payable', balancePayable);

    if (jewellerySummaryPanel) {
        jewellerySummaryPanel.innerHTML = `
            <div class="d-flex justify-content-between mb-1"><span>Items total</span><strong>${jewelleryMoney(totals.item_total)}</strong></div>
            <div class="d-flex justify-content-between mb-1"><span>Discount</span><strong>${jewelleryMoney(discount)}</strong></div>
            <div class="d-flex justify-content-between mb-1"><span>Chit adjustment</span><strong>${jewelleryMoney(adjustment)}</strong></div>
            <hr class="my-2">
            <div class="d-flex justify-content-between"><span>Balance payable</span><strong>${jewelleryMoney(balancePayable)}</strong></div>
        `;
    }
};

const loadCustomerMaturedChits = async () => {
    if (!jewelleryForm || !jewelleryCustomerControl || !jewelleryEnrollmentControl || !jewelleryCustomerControl.value) {
        return;
    }

    const selectedEnrollment = jewelleryEnrollmentControl.value;
    const url = jewelleryForm.dataset.maturedChitsTemplate.replace('__ID__', jewelleryCustomerControl.value);

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load matured chits');
        }

        jewelleryEnrollmentControl.innerHTML = '<option value="">No chit adjustment</option>';
        payload.data.chits.forEach((chit) => {
            const option = document.createElement('option');
            option.value = chit.id;
            option.dataset.available = chit.available_adjustment;
            option.textContent = `${chit.chit_no} - ${chit.scheme_name || 'Scheme'} - Available ${jewelleryMoney(chit.available_adjustment)}`;
            option.selected = String(chit.id) === String(selectedEnrollment);
            jewelleryEnrollmentControl.appendChild(option);
        });
        jewelleryEnrollmentControl.dispatchEvent(new Event('change'));
    } catch (error) {
        if (jewelleryAdjustmentHelp) {
            jewelleryAdjustmentHelp.textContent = error.message;
        }
    }
};

const updateJewelleryAdjustmentHelp = () => {
    const option = jewelleryEnrollmentControl?.selectedOptions?.[0];
    const available = jewelleryNumber(option?.dataset?.available);

    if (jewelleryAdjustmentHelp) {
        jewelleryAdjustmentHelp.textContent = available > 0
            ? `Available adjustment: ${jewelleryMoney(available)}`
            : 'Select a matured chit to apply adjustment.';
    }

    if (jewelleryAdjustmentControl) {
        jewelleryAdjustmentControl.max = available > 0 ? available : '';
    }
};

if (jewelleryForm) {
    jewelleryForm.addEventListener('input', (event) => {
        if (event.target.closest('[data-jewellery-items-table]') || event.target.matches('[data-jewellery-discount], [data-jewellery-adjustment]')) {
            updateJewelleryTotals();
        }
    });

    jewelleryCustomerControl?.addEventListener('change', loadCustomerMaturedChits);
    jewelleryEnrollmentControl?.addEventListener('change', () => {
        updateJewelleryAdjustmentHelp();
        updateJewelleryTotals();
    });

    document.querySelector('[data-jewellery-add-row]')?.addEventListener('click', () => {
        if (!jewelleryRowTemplate || !jewelleryItemsTable) {
            return;
        }

        const index = jewelleryItemsTable.querySelectorAll('[data-jewellery-item-row]').length;
        const html = jewelleryRowTemplate.innerHTML.replaceAll('__INDEX__', String(index));
        jewelleryItemsTable.querySelector('tbody')?.insertAdjacentHTML('beforeend', html);
        updateJewelleryTotals();
    });

    jewelleryItemsTable?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-jewellery-remove-row]');

        if (!button) {
            return;
        }

        const rows = jewelleryItemsTable.querySelectorAll('[data-jewellery-item-row]');
        if (rows.length <= 1) {
            return;
        }

        button.closest('[data-jewellery-item-row]')?.remove();
        updateJewelleryTotals();
    });

    updateJewelleryAdjustmentHelp();
    updateJewelleryTotals();

    if (jewelleryCustomerControl?.value) {
        loadCustomerMaturedChits();
    }
}

const goldRatesTableElement = document.getElementById('gold-rates-table');
let goldRatesTable = null;

if (goldRatesTableElement && window.jQuery?.fn?.DataTable) {
    const formatRateMoney = (value) => value === null || value === undefined || value === ''
        ? '-'
        : `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    goldRatesTable = window.jQuery(goldRatesTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: goldRatesTableElement.dataset.source,
            data: (payload) => {
                payload.rate_date = document.getElementById('gold-rate-date-filter')?.value || '';
                payload.status = document.getElementById('gold-rate-status-filter')?.value || '';
                payload.rate_locked = document.getElementById('gold-rate-lock-filter')?.value || '';
            },
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'rate_date', name: 'rate_date' },
            { data: 'gold_22k', name: 'gold_22k', className: 'text-end', render: formatRateMoney },
            { data: 'gold_24k', name: 'gold_24k', className: 'text-end', render: formatRateMoney },
            { data: 'silver_rate', name: 'silver_rate', className: 'text-end', render: formatRateMoney },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'lock_badge', name: 'rate_locked', searchable: false },
            { data: 'creator_name', name: 'creator.name' },
            { data: 'approver_name', name: 'approver.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'gold-rate-date-filter',
        'gold-rate-status-filter',
        'gold-rate-lock-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            goldRatesTable?.ajax.reload();
        });
    });
}

const branchesTableElement = document.getElementById('branches-table');
let branchesTable = null;

if (branchesTableElement && window.jQuery?.fn?.DataTable) {
    branchesTable = window.jQuery(branchesTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: branchesTableElement.dataset.source,
            data: (payload) => {
                payload.status = document.getElementById('branch-status-filter')?.value || '';
                payload.city = document.getElementById('branch-city-filter')?.value || '';
            },
        },
        order: [[8, 'desc']],
        columns: [
            { data: 'branch_code', name: 'branch_code' },
            { data: 'name', name: 'name' },
            { data: 'mobile', name: 'mobile', defaultContent: '-' },
            { data: 'city', name: 'city', defaultContent: '-' },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'users_count', name: 'users_count', searchable: false, className: 'text-end' },
            { data: 'enrollments_count', name: 'enrollments_count', searchable: false, className: 'text-end' },
            { data: 'payments_count', name: 'payments_count', searchable: false, className: 'text-end' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    ['branch-status-filter', 'branch-city-filter'].forEach((id) => {
        const eventName = id === 'branch-city-filter' ? 'input' : 'change';
        document.getElementById(id)?.addEventListener(eventName, () => {
            branchesTable?.ajax.reload();
        });
    });
}

const staffTableElement = document.getElementById('staff-table');
let staffTable = null;

if (staffTableElement && window.jQuery?.fn?.DataTable) {
    staffTable = window.jQuery(staffTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: staffTableElement.dataset.source,
            data: (payload) => {
                payload.role = document.getElementById('staff-role-filter')?.value || '';
                payload.branch_id = document.getElementById('staff-branch-filter')?.value || '';
                payload.status = document.getElementById('staff-status-filter')?.value || '';
            },
        },
        order: [[8, 'desc']],
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'mobile', name: 'mobile', defaultContent: '-' },
            { data: 'role_name', name: 'roles.name', searchable: false },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'staff_collections_count', name: 'staff_collections_count', searchable: false, className: 'text-end' },
            { data: 'assigned_chit_enrollments_count', name: 'assigned_chit_enrollments_count', searchable: false, className: 'text-end' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    ['staff-role-filter', 'staff-branch-filter', 'staff-status-filter'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            staffTable?.ajax.reload();
        });
    });
}

const handoversTableElement = document.getElementById('handovers-table');
let handoversTable = null;
const handoverMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

if (handoversTableElement && window.jQuery?.fn?.DataTable) {
    handoversTable = window.jQuery(handoversTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: handoversTableElement.dataset.source,
            data: (payload) => {
                payload.staff_id = document.getElementById('handover-staff-filter')?.value || '';
                payload.branch_id = document.getElementById('handover-branch-filter')?.value || '';
                payload.status = document.getElementById('handover-status-filter')?.value || '';
                payload.from_date = document.getElementById('handover-from-filter')?.value || '';
                payload.to_date = document.getElementById('handover-to-filter')?.value || '';
            },
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'handover_no', name: 'handover_no' },
            { data: 'handover_date', name: 'handover_date' },
            { data: 'staff_name', name: 'staff.name' },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'cash_amount', name: 'cash_amount', className: 'text-end', render: handoverMoney },
            { data: 'upi_amount', name: 'upi_amount', className: 'text-end', render: handoverMoney },
            { data: 'card_amount', name: 'card_amount', className: 'text-end', render: handoverMoney },
            { data: 'bank_amount', name: 'bank_amount', className: 'text-end', render: handoverMoney },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: handoverMoney },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'receiver_name', name: 'receiver.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'handover-staff-filter',
        'handover-branch-filter',
        'handover-status-filter',
        'handover-from-filter',
        'handover-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            handoversTable?.ajax.reload();
        });
    });
}

const refreshHandoverTotal = () => {
    const totalElement = document.querySelector('[data-handover-total]');

    if (!totalElement) {
        return;
    }

    const total = Array.from(document.querySelectorAll('[data-handover-amount]'))
        .reduce((sum, field) => sum + Number(field.value || 0), 0);

    totalElement.textContent = handoverMoney(total);
};

document.querySelectorAll('[data-handover-amount]').forEach((field) => {
    field.addEventListener('input', refreshHandoverTotal);
});

document.getElementById('staff_id')?.addEventListener('change', (event) => {
    const selected = event.target.selectedOptions?.[0];
    const branchId = selected?.dataset.branch;
    const branchSelect = document.getElementById('branch_id');

    if (branchId && branchSelect && !branchSelect.value) {
        branchSelect.value = branchId;
    }
});

refreshHandoverTotal();

const cashbooksTableElement = document.getElementById('cashbooks-table');
let cashbooksTable = null;
const cashbookMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const cashbookFilterPayload = () => ({
    branch_id: document.getElementById('cashbook-branch-filter')?.value || '',
    transaction_type: document.getElementById('cashbook-type-filter')?.value || '',
    payment_mode_id: document.getElementById('cashbook-mode-filter')?.value || '',
    from_date: document.getElementById('cashbook-from-filter')?.value || '',
    to_date: document.getElementById('cashbook-to-filter')?.value || '',
});

const updateCashbookSummaryCards = (summary = {}) => {
    Object.entries(summary).forEach(([key, value]) => {
        const element = document.querySelector(`[data-cashbook-summary="${key}"]`);

        if (element && typeof value === 'number') {
            element.textContent = cashbookMoney(value);
        }
    });
};

const updatePaymentModeSummary = (items = []) => {
    const container = document.querySelector('[data-payment-mode-summary]');

    if (!container) {
        return;
    }

    if (items.length === 0) {
        container.innerHTML = '<div class="col-12 text-muted">No payment mode entries for this period.</div>';

        return;
    }

    container.innerHTML = items.map((item) => `
        <div class="col-md-3">
            <div class="scheme-info-panel h-100">
                <div class="text-muted small">${item.payment_mode}</div>
                <div class="fw-semibold">${cashbookMoney(item.credit_total)}</div>
                <div class="small text-muted">Net ${cashbookMoney(item.net_total)}</div>
            </div>
        </div>
    `).join('');
};

const refreshCashbookSummaries = async () => {
    if (!cashbooksTableElement) {
        return;
    }

    const filters = cashbookFilterPayload();
    const params = new URLSearchParams(filters);

    try {
        const [rangeResponse, modeResponse] = await Promise.all([
            fetch(`${cashbooksTableElement.dataset.rangeSummary}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            }),
            fetch(`${cashbooksTableElement.dataset.modeSummary}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            }),
        ]);
        const rangePayload = await rangeResponse.json();
        const modePayload = await modeResponse.json();

        if (rangePayload.success) {
            updateCashbookSummaryCards(rangePayload.data?.summary || {});
        }

        if (modePayload.success) {
            updatePaymentModeSummary(modePayload.data?.payment_modes || []);
        }
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: 'Unable to refresh cashflow summary.',
        });
    }
};

if (cashbooksTableElement && window.jQuery?.fn?.DataTable) {
    cashbooksTable = window.jQuery(cashbooksTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cashbooksTableElement.dataset.source,
            data: (payload) => Object.assign(payload, cashbookFilterPayload()),
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'cashbook_date', name: 'cashbook_date' },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'transaction_type_label', name: 'transaction_type' },
            { data: 'payment_mode_name', name: 'paymentMode.name' },
            { data: 'debit', name: 'debit', className: 'text-end', render: cashbookMoney },
            { data: 'credit', name: 'credit', className: 'text-end', render: cashbookMoney },
            { data: 'balance', name: 'balance', className: 'text-end', render: cashbookMoney },
            {
                data: null,
                name: 'reference_type',
                render: (row) => row.reference_type ? `${row.reference_type.split('\\').pop()} #${row.reference_id || ''}` : '-',
            },
            { data: 'creator_name', name: 'creator.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    [
        'cashbook-branch-filter',
        'cashbook-type-filter',
        'cashbook-mode-filter',
        'cashbook-from-filter',
        'cashbook-to-filter',
    ].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            cashbooksTable?.ajax.reload();
            refreshCashbookSummaries();
        });
    });
}

const reportTableElement = document.querySelector('[data-report-table]');
let reportTable = null;
const reportMoney = (value) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const reportFilterPayload = () => {
    const payload = {};

    document.querySelectorAll('[data-report-filter]').forEach((field) => {
        payload[field.dataset.reportFilter] = field.value || '';
    });

    return payload;
};

const reportParams = () => new URLSearchParams(reportFilterPayload());

const refreshReportExportLinks = () => {
    const params = reportParams().toString();

    document.querySelectorAll('[data-report-export]').forEach((link) => {
        if (!link.dataset.baseHref) {
            link.dataset.baseHref = link.getAttribute('href');
        }

        link.setAttribute('href', `${link.dataset.baseHref}${params ? `?${params}` : ''}`);
    });
};

const refreshReportSummary = async () => {
    if (!reportTableElement) {
        return;
    }

    const params = reportParams();
    params.set('summary', '1');

    try {
        const response = await fetch(`${reportTableElement.dataset.source}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to refresh report summary');
        }

        const summaryContainer = document.querySelector('[data-report-summary]');
        const cards = payload.data?.summary || [];

        if (summaryContainer) {
            summaryContainer.innerHTML = cards.map((card) => `
                <div class="col-md-3">
                    <div class="admin-card h-100">
                        <div class="text-muted small">${card.label}</div>
                        <div class="metric-value">${card.value}</div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
};

if (reportTableElement && window.jQuery?.fn?.DataTable) {
    const reportColumns = JSON.parse(reportTableElement.dataset.columns || '[]').map((column) => ({
        data: column.data,
        name: column.data,
        className: column.className || '',
        render: column.money ? reportMoney : undefined,
    }));

    reportTable = window.jQuery(reportTableElement).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: reportTableElement.dataset.source,
            data: (payload) => Object.assign(payload, reportFilterPayload()),
        },
        columns: reportColumns,
    });

    document.querySelectorAll('[data-report-filter]').forEach((field) => {
        field.addEventListener('change', () => {
            refreshReportExportLinks();
            refreshReportSummary();
            reportTable?.ajax.reload();
        });
    });

    refreshReportExportLinks();
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
    const button = event.target.closest('[data-payment-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.paymentAction;

    if (action === 'cancel') {
        const modalElement = document.getElementById('paymentCancelModal');
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
        title: 'Approve payment edit?',
        text: 'Approved edits will re-apply payment allocations, receipt, ledger, and cashbook effects.',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#198754',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('approved', '1');

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

        if (paymentsTable) {
            paymentsTable.ajax.reload(null, false);
        } else if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
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
    const button = event.target.closest('[data-receipt-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.receiptAction;
    const isCancel = action === 'cancel';

    const result = await Swal.fire({
        icon: isCancel ? 'warning' : 'question',
        title: isCancel ? 'Cancel receipt?' : 'Send WhatsApp receipt?',
        text: isCancel ? 'This cancels only the receipt. Payment cancellation must be done from the payment flow.' : 'The current implementation records a WhatsApp sharing placeholder.',
        input: isCancel ? 'textarea' : undefined,
        inputPlaceholder: isCancel ? 'Cancellation reason' : undefined,
        showCancelButton: true,
        confirmButtonText: isCancel ? 'Cancel Receipt' : 'Send',
        confirmButtonColor: isCancel ? '#dc3545' : '#198754',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(isCancel ? { cancellation_reason: result.value || '' } : {}),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process receipt');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        if (receiptsTable) {
            receiptsTable.ajax.reload(null, false);
        } else if (payload.data?.redirect) {
            window.location.href = payload.data.redirect;
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
    const button = event.target.closest('[data-ledger-action="rebuild"]');

    if (!button) {
        return;
    }

    const result = await Swal.fire({
        icon: 'warning',
        title: 'Rebuild ledger?',
        text: 'Missing due and payment entries will be added and balances recalculated. Existing ledger rows are not deleted.',
        showCancelButton: true,
        confirmButtonText: 'Rebuild',
        confirmButtonColor: '#d9a441',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({}),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to rebuild ledger');
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
    const button = event.target.closest('[data-pending-due-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.pendingDueAction;

    if (action === 'followup') {
        const modalElement = document.getElementById('pendingDueFollowupModal');
        const form = modalElement?.querySelector('form');

        if (!modalElement || !form) {
            return;
        }

        clearFormErrors(form);
        form.action = button.dataset.url;
        form.querySelector('[name="followup_status"]').value = button.dataset.followupStatus || 'pending';
        form.querySelector('[name="promise_to_pay_date"]').value = button.dataset.promiseDate || '';
        form.querySelector('[name="remarks"]').value = button.dataset.remarks || '';
        Modal.getOrCreateInstance(modalElement).show();

        return;
    }

    const result = await Swal.fire({
        icon: 'question',
        title: 'Send reminder?',
        text: 'Choose the reminder channel for this pending installment.',
        input: 'select',
        inputOptions: {
            whatsapp: 'WhatsApp',
            sms: 'SMS',
        },
        inputValue: 'whatsapp',
        showCancelButton: true,
        confirmButtonText: 'Send',
        confirmButtonColor: '#198754',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ channel: result.value }),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to send reminder');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        pendingDuesTable?.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.getElementById('pendingDueFollowupModal')?.querySelector('form')?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.currentTarget;
    clearFormErrors(form);

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

            throw new Error(payload.message || 'Unable to update follow-up');
        }

        Modal.getInstance(document.getElementById('pendingDueFollowupModal'))?.hide();

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        pendingDuesTable?.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-pending-due-bulk]');

    if (!button || !pendingDuesTableElement) {
        return;
    }

    const selectedIds = Array.from(document.querySelectorAll('[data-pending-due-select]:checked')).map((checkbox) => checkbox.value);

    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Select dues',
            text: 'Choose at least one pending due to send reminders.',
        });

        return;
    }

    const channel = button.dataset.channel || 'whatsapp';
    const result = await Swal.fire({
        icon: 'warning',
        title: `Send ${channel} reminders?`,
        text: `${selectedIds.length} pending due reminders will be queued as placeholders.`,
        showCancelButton: true,
        confirmButtonText: 'Send',
        confirmButtonColor: '#198754',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(pendingDuesTableElement.dataset.bulkUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                installment_ids: selectedIds,
                channel,
            }),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to send bulk reminders');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        pendingDuesTable?.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-maturity-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.maturityAction;
    const confirmation = {
        approve: {
            icon: 'question',
            title: 'Approve closing?',
            text: 'This closing will move to approval-ready status.',
            confirmButtonText: 'Approve',
            confirmButtonColor: '#198754',
        },
        complete: {
            icon: 'warning',
            title: 'Complete closing?',
            text: 'This will close the enrollment and create ledger, refund, and adjustment entries as applicable.',
            confirmButtonText: 'Complete',
            confirmButtonColor: '#0d6efd',
        },
        cancel: {
            icon: 'warning',
            title: 'Cancel closing?',
            text: 'Enter the cancellation reason.',
            input: 'textarea',
            inputPlaceholder: 'Cancellation reason',
            inputValidator: (value) => (!value ? 'Cancellation reason is required.' : undefined),
            confirmButtonText: 'Cancel Closing',
            confirmButtonColor: '#dc3545',
        },
    }[action];

    if (!confirmation) {
        return;
    }

    const result = await Swal.fire({
        ...confirmation,
        showCancelButton: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const body = action === 'cancel' ? JSON.stringify({ cancellation_reason: result.value }) : JSON.stringify({});
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body,
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process maturity closing');
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
        } else {
            maturityClosingsTable?.ajax.reload(null, false);
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
    const button = event.target.closest('[data-jewellery-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.jewelleryAction;
    const confirmation = {
        finalize: {
            icon: 'question',
            title: 'Finalize invoice?',
            text: 'Final invoices cannot be edited. Chit adjustment, ledger, and cashbook entries will be created if applicable.',
            confirmButtonText: 'Finalize',
            confirmButtonColor: '#198754',
        },
        cancel: {
            icon: 'warning',
            title: 'Cancel invoice?',
            text: 'Enter the cancellation reason.',
            input: 'textarea',
            inputPlaceholder: 'Cancellation reason',
            inputValidator: (value) => (!value ? 'Cancellation reason is required.' : undefined),
            confirmButtonText: 'Cancel Invoice',
            confirmButtonColor: '#dc3545',
        },
    }[action];

    if (!confirmation) {
        return;
    }

    const result = await Swal.fire({
        ...confirmation,
        showCancelButton: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: action === 'cancel'
                ? JSON.stringify({ cancellation_reason: result.value })
                : JSON.stringify({}),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process jewellery invoice');
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
        } else {
            jewelleryInvoicesTable?.ajax.reload(null, false);
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
    const button = event.target.closest('[data-gold-rate-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.goldRateAction;
    const confirmation = {
        approve: {
            icon: 'question',
            title: 'Approve rate?',
            text: 'Approved rates become available for jewellery billing.',
            confirmButtonText: 'Approve',
            confirmButtonColor: '#198754',
        },
        reject: {
            icon: 'warning',
            title: 'Reject rate?',
            text: 'Add a rejection reason if needed.',
            input: 'textarea',
            inputPlaceholder: 'Reason',
            confirmButtonText: 'Reject',
            confirmButtonColor: '#d9a441',
        },
        lock: {
            icon: 'warning',
            title: 'Lock rate?',
            text: 'Locked rates cannot be edited.',
            confirmButtonText: 'Lock',
            confirmButtonColor: '#212529',
        },
    }[action];

    if (!confirmation) {
        return;
    }

    const result = await Swal.fire({
        ...confirmation,
        showCancelButton: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: action === 'reject' ? JSON.stringify({ reason: result.value || '' }) : JSON.stringify({}),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process gold rate');
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
        } else {
            goldRatesTable?.ajax.reload(null, false);
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
    const button = event.target.closest('[data-branch-action]');

    if (!button) {
        return;
    }

    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete branch?',
        text: 'Branches with linked users, enrollments, or payments will be marked inactive.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();
    formData.append('_method', 'DELETE');

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
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to delete branch');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        branchesTable?.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-staff-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.staffAction;
    const isDelete = action === 'delete';
    const nextStatus = button.dataset.status;
    const result = await Swal.fire({
        icon: 'warning',
        title: isDelete ? 'Delete staff user?' : `Mark staff ${nextStatus}?`,
        text: isDelete ? 'Staff with linked collections will be marked inactive.' : 'This changes web and API login availability.',
        showCancelButton: true,
        confirmButtonText: isDelete ? 'Delete' : 'Update status',
        confirmButtonColor: isDelete ? '#dc3545' : '#d9a441',
    });

    if (!result.isConfirmed) {
        return;
    }

    const formData = new FormData();

    if (isDelete) {
        formData.append('_method', 'DELETE');
    } else {
        formData.append('status', nextStatus);
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
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process staff user');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        staffTable?.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Action needed',
            text: error.message,
        });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-handover-action]');

    if (!button) {
        return;
    }

    const action = button.dataset.handoverAction;
    const confirmation = {
        receive: {
            icon: 'question',
            title: 'Receive handover?',
            text: 'This marks the pending handover as received.',
            confirmButtonText: 'Receive',
            confirmButtonColor: '#198754',
        },
        reject: {
            icon: 'warning',
            title: 'Reject handover?',
            text: 'Enter the rejection reason.',
            input: 'textarea',
            inputPlaceholder: 'Reason',
            inputValidator: (value) => (!value ? 'Reason is required' : undefined),
            confirmButtonText: 'Reject',
            confirmButtonColor: '#dc3545',
        },
    }[action];

    if (!confirmation) {
        return;
    }

    const result = await Swal.fire({
        ...confirmation,
        showCancelButton: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: action === 'reject' ? JSON.stringify({ remarks: result.value }) : JSON.stringify({}),
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const firstError = payload.data?.errors ? Object.values(payload.data.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to process handover');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        handoversTable?.ajax.reload(null, false);
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
