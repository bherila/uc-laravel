@extends('layouts.app')

@section('content')
<div id="offers-root" data-api-base="{{ url('/api') }}"></div>
@endsection

@push('head')
@vite('resources/js/offers.tsx')
@endpush