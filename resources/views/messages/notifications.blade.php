@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-eyebrow', 'WhatsApp/SMS')

@section('content')
    @include('messages.partials.log-page', [
        'title' => 'Notification Logs',
        'copy' => 'Review notification records created by due reminders, receipts, maturity reminders, wishes, and general messages.',
        'tableId' => 'notifications-table',
        'source' => route('messages.notifications'),
        'channel' => null,
        'showRetry' => false,
    ])
@endsection
