@extends('layout')

@section('title', 'Códigos de descuento')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-tag me-2 text-success"></i>Códigos de descuento</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCodeModal">
        <i class="fas fa-plus me-1"></i>Nuevo código
    </button>
</div>

@if($codes->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="fas fa-tag fa-3x mb-3 opacity-25"></i>
        <p>No hay códigos de descuento. Crea uno para empezar.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Mín. compra</th>
                    <th>Usos</th>
                    <th>Expira</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($codes as $code)
                <tr>
                    <td>
                        <span class="badge text-bg-dark font-monospace fs-6">{{ $code->code }}</span>
                    </td>
                    <td>
                        <small>{{ $code->description ?: '—' }}</small>
                    </td>
                    <td>
                        @if($code->type === 'percent')
                            <span class="badge text-bg-info">Porcentaje</span>
                        @else
                            <span class="badge text-bg-warning text-dark">Fijo</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $code->formattedValue() }}</strong>
                    </td>
                    <td>
                        <small>{{ $code->min_amount ? '€' . number_format($code->min_amount, 2) : '—' }}</small>
                    </td>
                    <td>
                        <span class="text-muted small">
                            {{ $code->used_count }}
                            @if($code->max_uses)
                                / {{ $code->max_uses }}
                            @else
                                / ∞
                            @endif
                        </span>
                        @if($code->max_uses && $code->used_count >= $code->max_uses)
                            <span class="badge text-bg-danger ms-1">Agotado</span>
                        @endif
                    </td>
                    <td>
                        @if($code->expires_at)
                            @if($code->expires_at->isPast())
                                <small class="text-danger">{{ $code->expires_at->format('d/m/Y H:i') }}</small>
                            @else
                                <small>{{ $code->expires_at->format('d/m/Y H:i') }}</small>
                            @endif
                        @else
                            <small class="text-muted">Sin expiración</small>
                        @endif
                    </td>
                    <td>
                        @if($code->is_active)
                            <span class="badge text-bg-success">Activo</span>
                        @else
                            <span class="badge text-bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <form method="POST"
                              action="{{ route('admin.discount-codes.toggle', $code) }}"
                              class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-xs {{ $code->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $code->is_active ? 'Desactivar' : 'Activar' }}">
                                <i class="fas {{ $code->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                            </button>
                        </form>
                        <form method="POST"
                              action="{{ route('admin.discount-codes.destroy', $code) }}"
                              class="d-inline"
                              onsubmit="return confirm('¿Eliminar el código {{ $code->code }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-outline-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Modal: Añadir código --}}
<div class="modal fade" id="addCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tag me-1"></i>Nuevo código de descuento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.discount-codes.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace text-uppercase"
                                   placeholder="VERANO25" required maxlength="50"
                                   pattern="[A-Za-z0-9_-]+"
                                   oninput="this.value=this.value.toUpperCase()"
                                   value="{{ old('code') }}">
                            <div class="form-text">Solo letras, números, guiones y guiones bajos.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="type" id="codeType" class="form-select" onchange="toggleCodeValue()" required>
                                <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
                                <option value="fixed"   {{ old('type') === 'fixed'   ? 'selected' : '' }}>Importe fijo (€)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Valor <span class="text-danger">*</span>
                                <small id="valueUnit" class="text-muted">(máx. 100%)</small>
                            </label>
                            <div class="input-group">
                                <input type="number" name="value" class="form-control"
                                       min="0.01" max="100" step="0.01"
                                       placeholder="10" required
                                       value="{{ old('value') }}">
                                <span class="input-group-text" id="valueSymbol">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mínimo de compra (€)</label>
                            <input type="number" name="min_amount" class="form-control"
                                   min="0" step="0.01" placeholder="0.00 (sin mínimo)"
                                   value="{{ old('min_amount') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción (visible al usuario)</label>
                            <input type="text" name="description" class="form-control"
                                   placeholder="10% de descuento en tu primera compra" maxlength="255"
                                   value="{{ old('description') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Máx. de usos</label>
                            <input type="number" name="max_uses" class="form-control"
                                   min="1" placeholder="Sin límite"
                                   value="{{ old('max_uses') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expira el</label>
                            <input type="datetime-local" name="expires_at" class="form-control"
                                   value="{{ old('expires_at') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Crear código
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Test code section --}}
<div class="card mt-4">
    <div class="card-body">
        <h6 class="card-title"><i class="fas fa-flask me-1 text-info"></i>Probar código</h6>
        <p class="text-muted small">Simula la aplicación de un código a un importe determinado.</p>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Código</label>
                <input type="text" id="testCode" class="form-control font-monospace text-uppercase"
                       placeholder="VERANO25" oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Importe (€)</label>
                <input type="number" id="testAmount" class="form-control" min="0" step="0.01" placeholder="29.99">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-info w-100" onclick="testDiscount()">
                    <i class="fas fa-search me-1"></i>Verificar
                </button>
            </div>
            <div class="col-12">
                <div id="testResult" class="d-none"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleCodeValue() {
    const type   = document.getElementById('codeType').value;
    const symbol = document.getElementById('valueSymbol');
    const hint   = document.getElementById('valueUnit');
    const input  = document.querySelector('[name="value"]');
    if (type === 'percent') {
        symbol.textContent = '%';
        hint.textContent   = '(máx. 100%)';
        input.max          = 100;
    } else {
        symbol.textContent = '€';
        hint.textContent   = '(importe fijo)';
        input.max          = 9999;
    }
}

function testDiscount() {
    const code   = document.getElementById('testCode').value.trim();
    const amount = parseFloat(document.getElementById('testAmount').value);
    const result = document.getElementById('testResult');

    if (!code || isNaN(amount)) {
        result.className = 'alert alert-warning py-2 px-3 small';
        result.textContent = 'Introduce un código y un importe.';
        return;
    }

    fetch('{{ route('admin.discount-codes.validate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code, amount }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            result.className = 'alert alert-success py-2 px-3 small';
            result.innerHTML = `<strong>✓ Código válido</strong> — ${data.description || ''}<br>
                Descuento: <strong>${data.formatted}</strong> &nbsp;|&nbsp;
                Importe final: <strong>€${data.final_amount.toFixed(2)}</strong>`;
        } else {
            result.className = 'alert alert-danger py-2 px-3 small';
            result.textContent = '✗ ' + (data.message || 'Código inválido.');
        }
    })
    .catch(() => {
        result.className = 'alert alert-danger py-2 px-3 small';
        result.textContent = 'Error al verificar el código.';
    });
}
</script>
@endsection
