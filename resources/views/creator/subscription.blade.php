@extends('layout')

@section('title', 'Membresia de Creador')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-3">Membresia de Creador</h2>
                <p class="text-muted">Activa tu plan para vender contenido desde tu tienda y recibir pagos directos.</p>

                <div class="alert {{ $isActive ? 'alert-success' : 'alert-info' }}">
                    @if($isActive)
                        <strong>Estado:</strong> Activo
                    @else
                        <strong>Estado:</strong> Inactivo
                    @endif
                    <br>
                    <strong>Precio:</strong> $5.00 / mes
                </div>

                @if($isActive)
                    <a href="{{ route('creator.dashboard') }}" class="btn btn-primary me-2">Ir a mi panel</a>
                    <a href="{{ route('creator.subscription.portal') }}" class="btn btn-outline-secondary">Gestionar facturacion</a>
                @else
                    <form method="POST" action="{{ route('creator.subscription.checkout') }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg">Pagar $5/mes y activar creador</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
