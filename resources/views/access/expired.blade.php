@extends('layout')

@section('title', 'Acceso expirado')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="alert alert-warning">
                <h4 class="mb-2">Tu acceso ha expirado</h4>
                <p class="mb-2">Este acceso vencio el {{ $access->expires_at->format('Y-m-d H:i') }}.</p>
                <p class="mb-0">Para seguir usando el servicio, debes comprar de nuevo.</p>
            </div>
        </div>
    </div>
</div>
@endsection
