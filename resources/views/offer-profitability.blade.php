@extends('layouts.app')

@section('content')
<div id="offer-profitability-root" 
     data-api-base="{{ url('/api') }}" 
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-profitability.tsx')
@endpush
