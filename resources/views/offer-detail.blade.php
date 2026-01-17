@extends('layouts.app')

@section('content')
<div id="offer-detail-root" 
     data-api-base="{{ url('/api') }}" 
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-detail.tsx')
@endpush
