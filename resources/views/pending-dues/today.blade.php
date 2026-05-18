@extends('layouts.admin')

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-eyebrow', 'Pending Dues')

@section('content')
    @include('pending-dues.partials.page')
@endsection
