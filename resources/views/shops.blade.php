@extends('layouts.app')

@section('content')
<div id="shops-root" data-api-base="{{ url('/api') }}"></div>
@endsection

@push('head')
@vite('resources/js/shops.tsx')
@endpush
