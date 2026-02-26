@extends('layout')

@section('title', 'Compras de mi tienda')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Compras de mi tienda</h2>
    <a href="{{ route('creator.dashboard') }}" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Fecha</th><th>Video</th><th>Usuario TG</th><th>Metodo</th><th>Referencia</th><th>Comprobante</th><th>Estado</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $purchase->video->title ?? 'N/A' }}</td>
                    <td>{{ '@' . $purchase->telegram_username }}</td>
                    <td>{{ $purchase->payment_method ?? 'N/A' }}</td>
                    <td>{{ $purchase->payment_reference ?? '-' }}</td>
                    <td>
                        @if($purchase->proof_url)
                            <a href="{{ $purchase->proof_url }}" target="_blank">Ver</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($purchase->verification_status === 'verified')
                            <span class="badge text-bg-success">Aprobado</span>
                        @elseif($purchase->verification_status === 'invalid')
                            <span class="badge text-bg-danger">Rechazado</span>
                        @else
                            <span class="badge text-bg-warning">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        @if($purchase->verification_status === 'pending')
                            <form method="POST" action="{{ route('creator.purchases.approve', $purchase) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Aprobar</button>
                            </form>
                            <form method="POST" action="{{ route('creator.purchases.reject', $purchase) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="delivery_notes" value="Pago rechazado por el creador">
                                <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay compras aun.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div>{{ $purchases->links() }}</div>
@endsection
