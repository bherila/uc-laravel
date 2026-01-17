@extends('layouts.app')

@section('content')
<div id="admin-user-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-user-id="{{ $userId }}">
</div>
@endsection

@push('head')
@vite('resources/js/admin-user-detail.tsx')
@endpush
