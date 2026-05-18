@php
    $alertPayload = [
        'success' => session('success') ?? session('status'),
        'error' => session('error'),
        'validation' => $errors->any() ? $errors->first() : null,
    ];
@endphp

<script>
    window.adminFlash = @json($alertPayload);
</script>
