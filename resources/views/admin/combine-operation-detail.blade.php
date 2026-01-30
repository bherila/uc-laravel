@extends('layouts.app')

@section('title', 'Admin - Combine Operation Detail')

@section('content')
<div id="admin-combine-operation-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-id="{{ $id }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-combine-operation-detail.tsx')
@endpush
