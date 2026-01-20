@extends('layouts.app')

@section('title', ($shopName ?? 'Shop') . ' - Offers')

@section('content')
<div id="offers-root" 
     data-api-base="{{ url('/api') }}"
     data-shop-id="{{ $shopId }}"
     data-can-write-shop="{{ $canWrite ? 'true' : 'false' }}">
</div>
@endsection

@push('head')
@vite('resources/js/offers.tsx')
@endpush