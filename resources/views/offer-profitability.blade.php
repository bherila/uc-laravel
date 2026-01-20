@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Profitability')

@section('content')
<div id="offer-profitability-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-profitability.tsx')
@endpush
