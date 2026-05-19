# User Manual

This manual covers daily usage for shop staff and managers.

## Login

Open `/login`, enter email and password, then access the dashboard. If login fails, confirm the staff user is active and has a role.

## Dashboard

The dashboard shows:

- Total customers
- Active chits
- Today and monthly collection
- Pending dues and overdue customers
- Matured and closed chits
- Staff, scheme, month, and payment mode charts
- Recent activity

## Customer Management

Use `Customers > Customer List` to search and filter customers.

Common actions:

- Add customer with name, mobile, address, and nominee details.
- Upload photo, Aadhaar, PAN, or other documents.
- View customer profile, active chits, payment history, outstanding balance, and ledger.
- Deactivate customers with active chits instead of deleting.

## Chit Schemes

Use `Chit Schemes` to view scheme rules.

Scheme types:

- Fixed amount
- Flexible amount
- Gold weight

Staff generally view schemes; Admin or Manager can create or update based on permissions.

## Enrollments

Use `Chit Enrollments > New Enrollment`.

Steps:

1. Select customer.
2. Select active scheme.
3. Select branch and staff if needed.
4. Choose start date.
5. Confirm monthly amount.
6. Upload agreement if available.
7. Submit.

The installment schedule is generated automatically.

## Installments

Installments show due date, due amount, paid amount, balance, late fee, and status.

Statuses:

- Pending
- Partial
- Paid
- Overdue
- Advance

## Payment Collection

Use `Payments > Collect Payment`.

Steps:

1. Select customer and active chit.
2. Select pending installment.
3. Choose payment type: full, partial, advance, or multiple month.
4. Select payment mode.
5. Enter transaction ID for non-cash modes.
6. Submit.

The system updates installment, enrollment totals, receipt, ledger, and cashbook.

## Receipts

Receipts are generated after payment.

Available actions:

- View receipt
- Thermal print
- A4 print
- PDF download
- Duplicate copy
- WhatsApp share placeholder or integration send
- Cancel receipt if permitted

## Pending Dues

Use `Pending Dues` to follow up:

- Today dues
- Weekly dues
- Monthly dues
- Overdue dues

Users with permission can update follow-up status, promise-to-pay date, and send WhatsApp/SMS reminders.

## Maturity Closing

Use `Maturity Closing` for normal, early, defaulted, or cancelled closings.

Closing calculates:

- Total paid
- Shop bonus
- Deductions
- Final maturity value
- Refund amount
- Jewellery adjustment amount

Approval is required before completion.

## Jewellery Billing

Use `Jewellery Billing` to create invoices and adjust matured chit value when applicable.

Draft invoices can be edited. Final invoices cannot be edited except cancellation by authorized users.

## Reports

Reports support filters, print, Excel export, and PDF export.

Examples:

- Customer report
- Active chit report
- Collection report
- Pending and overdue reports
- Receipt report
- Cashflow report

## Troubleshooting

- Missing menu: ask Admin to check your permissions.
- Payment blocked: check active enrollment, selected installment, and transaction ID.
- Receipt PDF missing: regenerate PDF or check storage permissions.
- Reminder not delivered: check message logs and integration settings.
