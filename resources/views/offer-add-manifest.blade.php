@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Add Manifest')

@section('content')
<div id="offer-add-manifest-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-add-manifest.tsx')
@endpush
