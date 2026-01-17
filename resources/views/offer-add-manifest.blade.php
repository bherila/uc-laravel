@extends('layouts.app')

@section('content')
<div id="offer-add-manifest-root" 
     data-api-base="{{ url('/api') }}" 
     data-offer-id="{{ $offerId }}">
</div>
@endsection

@push('head')
@vite('resources/js/offer-add-manifest.tsx')
@endpush
