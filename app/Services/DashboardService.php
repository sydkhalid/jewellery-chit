<?php

namespace App\Services;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return [
            'summaryCards' => [
                [
                    'label' => 'Total customers',
                    'value' => '1,248',
                    'icon' => 'bi-people',
                    'tone' => 'primary',
                    'trend' => '+8.2% this month',
                ],
                [
                    'label' => 'Active chits',
                    'value' => '342',
                    'icon' => 'bi-journal-check',
                    'tone' => 'success',
                    'trend' => '18 new enrollments',
                ],
                [
                    'label' => 'Today collection',
                    'value' => 'Rs. 1,84,500',
                    'icon' => 'bi-cash-stack',
                    'tone' => 'warning',
                    'trend' => '64 receipts posted',
                ],
                [
                    'label' => 'Monthly collection',
                    'value' => 'Rs. 42,65,000',
                    'icon' => 'bi-calendar3',
                    'tone' => 'info',
                    'trend' => '+11.4% vs last month',
                ],
                [
                    'label' => 'Pending dues',
                    'value' => 'Rs. 6,75,200',
                    'icon' => 'bi-hourglass-split',
                    'tone' => 'danger',
                    'trend' => '96 installments due',
                ],
                [
                    'label' => 'Matured chits',
                    'value' => '24',
                    'icon' => 'bi-award',
                    'tone' => 'purple',
                    'trend' => 'Ready for closing',
                ],
                [
                    'label' => 'Closed chits',
                    'value' => '18',
                    'icon' => 'bi-check2-circle',
                    'tone' => 'dark',
                    'trend' => 'This month',
                ],
                [
                    'label' => 'Overdue customers',
                    'value' => '31',
                    'icon' => 'bi-exclamation-triangle',
                    'tone' => 'orange',
                    'trend' => 'Needs follow-up',
                ],
            ],
            'charts' => [
                'staffWiseCollection' => [
                    'labels' => ['Arun', 'Meena', 'Ravi', 'Priya', 'Kumar'],
                    'series' => [420000, 360000, 310000, 285000, 240000],
                ],
                'schemeWiseCollection' => [
                    'labels' => ['Gold 12M', 'Gold 18M', 'Diamond Plan', 'Silver Plus'],
                    'series' => [46, 28, 16, 10],
                ],
                'monthlyCollectionTrend' => [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'series' => [2800000, 3120000, 3560000, 3820000, 4265000, 4470000],
                ],
                'paymentModeCollection' => [
                    'labels' => ['Cash', 'UPI', 'Card', 'Bank Transfer'],
                    'series' => [32, 44, 14, 10],
                ],
            ],
            'recentActivities' => [
                [
                    'title' => 'Receipt RCPT-1024 posted',
                    'description' => 'Monthly installment collected from Lakshmi R.',
                    'time' => '10 minutes ago',
                    'type' => 'Payment',
                ],
                [
                    'title' => 'New chit enrollment created',
                    'description' => 'Gold 18M scheme assigned to Customer ID C-1187.',
                    'time' => '28 minutes ago',
                    'type' => 'Enrollment',
                ],
                [
                    'title' => 'Pending due reminder queued',
                    'description' => 'WhatsApp reminders prepared for 12 overdue customers.',
                    'time' => '1 hour ago',
                    'type' => 'Reminder',
                ],
                [
                    'title' => 'Gold rate updated',
                    'description' => '22K gold rate revised for today billing.',
                    'time' => '2 hours ago',
                    'type' => 'Rates',
                ],
            ],
        ];
    }
}
