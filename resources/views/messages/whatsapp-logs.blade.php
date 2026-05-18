@extends('layouts.admin')

@section('title', 'WhatsApp Logs')
@section('page-title', 'WhatsApp Logs')
@section('page-eyebrow', 'WhatsApp/SMS')

@section('content')
    @include('messages.partials.log-page', [
        'title' => 'WhatsApp Delivery Logs',
        'copy' => 'Review WhatsApp messages, provider placeholder responses, and failed-message retry status.',
        'tableId' => 'whatsapp-logs-table',
        'source' => route('messages.whatsapp-logs'),
        'channel' => 'whatsapp',
        'showRetry' => true,
    ])
@endsection
