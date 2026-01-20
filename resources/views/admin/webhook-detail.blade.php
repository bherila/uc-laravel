@extends('layouts.app')

@section('title', 'Admin - Webhook #' . $webhookId)

@section('content')
<div id="admin-webhook-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-webhook-id="{{ $webhookId }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-webhook-detail.tsx')
@endpush
