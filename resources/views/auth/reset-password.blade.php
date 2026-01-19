@extends('layouts.app')

@section('content')
<div id="reset-password-root" data-token="{{ $token }}" data-email="{{ $email }}"></div>
@endsection

@push('scripts')
    @vite(['resources/js/reset-password.tsx'])
@endpush
