@extends('layouts.app')

@section('title', $offerName ?? 'Offer Detail')

@section('content')
<div id="offer-detail-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-detail.tsx')
@endpush
