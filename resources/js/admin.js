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
