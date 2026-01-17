@extends('layouts.app')

@section('content')
<div id="shop-dashboard-root" 
     data-api-base="{{ url('/api') }}"
     data-shop-id="{{ $shopId }}">
</div>
@endsection

@push('head')
@vite('resources/js/shop-dashboard.tsx')
@endpush
