@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Order Manifests')

@section('content')
<div id="offer-manifests-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-manifests.tsx')
@endpush
