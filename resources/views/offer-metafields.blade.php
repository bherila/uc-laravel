@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Metafields')

@section('content')
<div id="offer-metafields-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-metafields.tsx')
@endpush
