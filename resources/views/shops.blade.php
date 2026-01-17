@extends('layouts.app')

@section('content')
<div id="shops-root" 
     data-api-base="{{ url('/api') }}"
     data-is-admin="{{ auth()->user()->isAdmin() ? 'true' : 'false' }}">
</div>
@endsection

@push('head')
@vite('resources/js/shops.tsx')
@endpush
