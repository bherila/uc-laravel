@extends('layouts.app')

@section('title', 'Admin - Combine Operations')

@section('content')
<div id="admin-combine-operations-root" 
     data-api-base="{{ url('/api') }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-combine-operations.tsx')
@endpush
