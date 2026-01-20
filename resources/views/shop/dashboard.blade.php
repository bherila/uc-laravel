@extends('layouts.app')

@section('title', ($shopName ?? 'Shop') . ' - Dashboard')

@section('content')
<div id="shop-dashboard-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-is-admin="{{ auth()->user()->isAdmin() ? 'true' : 'false' }}">
</div>
@endsection
@push('head')
@vite('resources/js/shop-dashboard.tsx')
@endpush
