@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Order Manifests')

@section('content')
<div id="offer-manifests-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}"
     data-is-admin="{{ auth()->user()->is_admin || auth()->id() === 1 ? 'true' : 'false' }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-manifests.tsx')
@endpush
