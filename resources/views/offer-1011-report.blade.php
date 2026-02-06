@extends('layouts.app')

@section('title', $offerName . ' - 1011 Report')

@section('content')
<div id="offer-1011-report-root" 
     data-api-base="{{ url('/api') }}" 
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}"
     data-offer-name="{{ $offerName }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-1011-report.tsx')
@endpush
