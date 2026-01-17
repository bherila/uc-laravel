@extends('layouts.app')

@section('content')
<div id="admin-store-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-store-id="{{ $storeId }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-store-detail.tsx')
@endpush
