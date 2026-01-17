@extends('layouts.app')

@section('content')
<div id="offer-new-root" data-api-base="{{ url('/api') }}"></div>
@endsection

@push('head')
@vite('resources/js/offer-new.tsx')
@endpush
