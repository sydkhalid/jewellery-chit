@extends('layouts.admin')

@section('title', 'SMS Logs')
@section('page-title', 'SMS Logs')
@section('page-eyebrow', 'WhatsApp/SMS')

@section('content')
    @include('messages.partials.log-page', [
        'title' => 'SMS Delivery Logs',
        'copy' => 'Review SMS messages, provider placeholder responses, and failed-message retry status.',
        'tableId' => 'sms-logs-table',
        'source' => route('messages.sms-logs'),
        'channel' => 'sms',
        'showRetry' => true,
    ])
@endsection
