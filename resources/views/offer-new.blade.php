@extends('layouts.app')

@section('title', ($shopName ?? 'Shop') . ' - Create New Offer')

@section('content')
<div id="offer-new-root" 
     data-api-base="{{ url('/api') }}"
     data-shop-id="{{ $shopId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-new.tsx')
@endpush
