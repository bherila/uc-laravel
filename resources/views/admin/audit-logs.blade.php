@extends('layouts.app')

@section('title', 'Admin - Audit Log')

@section('content')
<div id="admin-audit-logs-root" 
     data-api-base="{{ url('/api') }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-audit-logs.tsx')
@endpush
