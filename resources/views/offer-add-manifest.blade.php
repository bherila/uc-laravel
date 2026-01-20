@extends('layouts.app')

@section('title', ($offerName ?? 'Offer') . ' - Add Manifest')

@section('content')
<div id="offer-add-manifest-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}"
     data-can-write-shop="{{ $canWrite ? 'true' : 'false' }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-add-manifest.tsx')
@endpush
