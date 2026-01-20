@extends('layouts.app')

@section('content')
<div id="admin-webhooks-root" 
     data-api-base="{{ url('/api') }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-webhooks.tsx')
@endpush
