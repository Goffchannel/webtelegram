@extends('admin.layout')

@section('title', 'Gestión de Compras')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="mb-4">
                    <h2 class="fw-bold mb-0">
                        <i class="fas fa-shopping-cart me-2 text-primary"></i>
                        Compras
                    </h2>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white" style="background-color: #0d6efd;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Compras</h6>
                                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background-color: #198754;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Ingresos Totales</h6>
                                        <h3 class="mb-0">${{ number_format($stats['total_revenue'], 2) }}</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background-color: #ffc107;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Verificación Pendiente</h6>
                                        <h3 class="mb-0">{{ $stats['pending_verification'] }}</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background-color: #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Entregas Fallidas</h6>
                                        <h3 class="mb-0">{{ $stats['failed_delivery'] }}</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3 mt-md-0">
                        <div class="card text-white" style="background-color: #6f42c1;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Reportes Abiertos</h6>
                                        <h3 class="mb-0">{{ $stats['open_creator_reports'] ?? 0 }}</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-flag fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.purchases.index') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="{{ request('search') }}" placeholder="Usuario, email, ID, UUID o producto">
                            </div>
                            <div class="col-md-2">
                                <label for="purchase_status" class="form-label">Estado Compra</label>
                                <select class="form-select" id="purchase_status" name="purchase_status">
                                    <option value="">Todos</option>
                                    <option value="completed"
                                        {{ request('purchase_status') === 'completed' ? 'selected' : '' }}>Completado
                                    </option>
                                    <option value="refunded"
                                        {{ request('purchase_status') === 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                                    <option value="disputed"
                                        {{ request('purchase_status') === 'disputed' ? 'selected' : '' }}>En disputa</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="verification_status" class="form-label">Verificación</label>
                                <select class="form-select" id="verification_status" name="verification_status">
                                    <option value="">Todos</option>
                                    <option value="pending"
                                        {{ request('verification_status') === 'pending' ? 'selected' : '' }}>Pendiente
                                    </option>
                                    <option value="verified"
                                        {{ request('verification_status') === 'verified' ? 'selected' : '' }}>Verificado
                                    </option>
                                    <option value="invalid"
                                        {{ request('verification_status') === 'invalid' ? 'selected' : '' }}>Inválido
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="delivery_status" class="form-label">Estado Entrega</label>
                                <select class="form-select" id="delivery_status" name="delivery_status">
                                    <option value="">Todos</option>
                                    <option value="pending"
                                        {{ request('delivery_status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="delivered"
                                        {{ request('delivery_status') === 'delivered' ? 'selected' : '' }}>Entregado
                                    </option>
                                    <option value="failed" {{ request('delivery_status') === 'failed' ? 'selected' : '' }}>
                                        Fallido</option>
                                    <option value="retrying"
                                        {{ request('delivery_status') === 'retrying' ? 'selected' : '' }}>Reintentando</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filtrar
                                    </button>
                                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i>Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Creator Reports -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-flag me-2"></i>Reportes de creadores</h5>
                        <small class="text-muted">Ultimos 50 reportes</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Estado</th>
                                        <th>Compra</th>
                                        <th>Creador</th>
                                        <th>Motivo</th>
                                        <th>Mensaje</th>
                                        <th>Contacto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($creatorReports as $report)
                                        <tr data-report-id="{{ $report->id }}">
                                            <td>{{ $report->id }}</td>
                                            <td>
                                                @if ($report->status === 'open')
                                                    <span class="badge text-bg-danger">Abierto</span>
                                                @elseif($report->status === 'reviewing')
                                                    <span class="badge text-bg-warning">Revisando</span>
                                                @else
                                                    <span class="badge text-bg-success">Resuelto</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('purchase.view', $report->purchase->purchase_uuid) }}" target="_blank">
                                                    {{ $report->purchase->purchase_uuid }}
                                                </a>
                                            </td>
                                            <td>
                                                @if ($report->creator)
                                                    <strong>{{ $report->creator->creator_store_name ?? $report->creator->name }}</strong>
                                                    <br><small class="text-muted">ID: {{ $report->creator->id }}</small>
                                                @else
                                                    <span class="text-muted">Eliminado</span>
                                                @endif
                                            </td>
                                            <td>{{ $report->reason }}</td>
                                            <td style="max-width: 280px;">
                                                <small>{{ \Illuminate\Support\Str::limit($report->message, 140) }}</small>
                                            </td>
                                            <td>
                                                @if($report->reporter_telegram)
                                                    <div><span>@</span>{{ ltrim($report->reporter_telegram, '@') }}</div>
                                                @endif
                                                @if($report->reporter_email)
                                                    <small>{{ $report->reporter_email }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-secondary" onclick="updateReportStatus({{ $report->id }}, 'reviewing')">Revisar</button>
                                                    <button class="btn btn-outline-success" onclick="updateReportStatus({{ $report->id }}, 'resolved')">Resolver</button>
                                                    @if($report->creator)
                                                        <button class="btn btn-outline-warning" onclick="banCreator({{ $report->id }})">Banear</button>
                                                        <button class="btn btn-outline-danger" onclick="deleteCreator({{ $report->id }})">Eliminar</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">Sin reportes por ahora.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Compra</th>
                                        <th>Cliente</th>
                                        <th>Producto</th>
                                        <th>Importe</th>
                                        <th>Verificación</th>
                                        <th>Entrega</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($purchases as $purchase)
                                        <tr>
                                            <td>
                                                <div>
                                                    <small class="text-muted">UUID:</small><br>
                                                    <code style="font-size: 10px;">{{ $purchase->purchase_uuid }}</code>
                                                </div>
                                                <div class="mt-1">
                                                    @php $statusLabels = ['completed' => 'Completado', 'refunded' => 'Reembolsado', 'disputed' => 'En disputa', 'pending' => 'Pendiente']; @endphp
                                                    <span class="badge {{ $purchase->purchase_status === 'completed' ? 'text-bg-success' : 'text-bg-warning' }}">
                                                        {{ $statusLabels[$purchase->purchase_status] ?? ucfirst($purchase->purchase_status) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><span>@</span>{{ $purchase->telegram_username }}</strong>
                                                </div>
                                                @if ($purchase->customer_email)
                                                    <small class="text-muted">{{ $purchase->customer_email }}</small>
                                                @endif
                                                @if ($purchase->telegram_user_id)
                                                    <br><small class="text-success">ID:
                                                        {{ $purchase->telegram_user_id }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $purchase->video->title }}</strong>
                                                </div>
                                                <small class="text-muted">ID: {{ $purchase->video->id }}</small>
                                            </td>
                                            <td>
                                                <span class="h6 text-success">{{ $purchase->formatted_amount }}</span>
                                            </td>
                                            <td>
                                                @if ($purchase->verification_status === 'pending')
                                                    <span class="badge text-bg-warning">Pendiente</span>
                                                @elseif($purchase->verification_status === 'verified')
                                                    <span class="badge text-bg-success">Verificado</span>
                                                @else
                                                    <span class="badge text-bg-danger">Inválido</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($purchase->delivery_status === 'pending')
                                                    <span class="badge text-bg-info">Pendiente</span>
                                                @elseif($purchase->delivery_status === 'delivered')
                                                    <span class="badge text-bg-success">Entregado</span>
                                                    @if ($purchase->delivered_at)
                                                        <br><small
                                                            class="text-muted">{{ $purchase->delivered_at->format('d/m H:i') }}</small>
                                                    @endif
                                                @elseif($purchase->delivery_status === 'failed')
                                                    <span class="badge text-bg-danger">Fallido</span>
                                                    @if ($purchase->delivery_attempts > 0)
                                                        <br><small class="text-muted">{{ $purchase->delivery_attempts }}
                                                            intentos</small>
                                                    @endif
                                                @else
                                                    <span class="badge text-bg-warning">Reintentando</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $purchase->created_at->format('d/m/Y') }}<br>{{ $purchase->created_at->format('H:i:s') }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <!-- View Details -->
                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="viewPurchase('{{ $purchase->id }}')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <!-- Manual Actions -->
                                                    @if ($purchase->verification_status === 'pending')
                                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                            title="{{ $purchase->video->isServiceProduct() ? 'Aprovisionar acceso IPTV' : 'Verificar con Telegram ID' }}"
                                                            onclick="verifyPurchase('{{ $purchase->id }}', {{ $purchase->video->isServiceProduct() ? 'true' : 'false' }})">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif

                                                    @if ($purchase->delivery_status === 'failed' && $purchase->canRetryDelivery())
                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                            onclick="retryDelivery('{{ $purchase->id }}')">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    @endif

                                                    @if ($purchase->delivery_status !== 'delivered')
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="markDelivered('{{ $purchase->id }}')">
                                                            <i class="fas fa-truck"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <br>No se encontraron compras
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($purchases->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $purchases->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Details Modal -->
    <div class="modal fade" id="purchaseDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="purchaseDetailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Purchase Modal (only for regular video purchases, not IPTV) -->
    <div class="modal fade" id="verifyPurchaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verificar Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="verifyPurchaseForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="telegram_user_id" class="form-label">ID de usuario Telegram</label>
                            <input type="text" class="form-control" id="telegram_user_id" name="telegram_user_id"
                                required placeholder="Ej: 123456789">
                            <div class="form-text">El cliente debe enviarte su ID de Telegram (p.ej. usando @userinfobot).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Verificar Compra</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mark Delivered Modal -->
    <div class="modal fade" id="markDeliveredModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Marcar como Entregado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="markDeliveredForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="delivery_notes" class="form-label">Notas de entrega (opcional)</label>
                            <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="3"></textarea>
                            <div class="form-text">Añade notas sobre la entrega manual.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Marcar como Entregado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentPurchaseId = null;

        // View purchase details
        function viewPurchase(purchaseId) {
            fetch(`/admin/purchases/${purchaseId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('purchaseDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('purchaseDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Error al cargar detalles: ' + error.message);
                });
        }

        // Verify purchase
        function verifyPurchase(purchaseId, isServiceProduct) {
            currentPurchaseId = purchaseId;

            if (isServiceProduct) {
                // IPTV/service product: provision directly, no Telegram ID needed
                if (!confirm('¿Aprovisionar y verificar este acceso IPTV automáticamente?')) return;
                fetch(`/admin/purchases/${purchaseId}/verify`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({}),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(() => showAlert('error', 'Ha ocurrido un error'));
            } else {
                // Regular video purchase: ask for Telegram ID
                new bootstrap.Modal(document.getElementById('verifyPurchaseModal')).show();
            }
        }

        // Mark as delivered
        function markDelivered(purchaseId) {
            currentPurchaseId = purchaseId;
            new bootstrap.Modal(document.getElementById('markDeliveredModal')).show();
        }

        // Retry delivery
        function retryDelivery(purchaseId) {
            if (confirm('¿Confirma reintentar la entrega de esta compra?')) {
                fetch(`/admin/purchases/${purchaseId}/retry-delivery`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('success', data.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('error', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Ha ocurrido un error');
                    });
            }
        }

        // Handle verify purchase form
        document.getElementById('verifyPurchaseForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(`/admin/purchases/${currentPurchaseId}/verify`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        bootstrap.Modal.getInstance(document.getElementById('verifyPurchaseModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Ha ocurrido un error');
                });
        });

        // Handle mark delivered form
        document.getElementById('markDeliveredForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(`/admin/purchases/${currentPurchaseId}/mark-delivered`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        bootstrap.Modal.getInstance(document.getElementById('markDeliveredModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Ha ocurrido un error');
                });
        });

        // Edit telegram username functionality for admin
        function editAdminTelegramUsername(purchaseId, currentUsername) {
            const newUsername = prompt('Nuevo nombre de usuario de Telegram:', currentUsername);

            if (newUsername === null || newUsername === currentUsername) {
                return; // User cancelled or no change
            }

            if (!newUsername.trim()) {
                showAlert('error', 'El nombre de usuario no puede estar vacío');
                return;
            }

            // Clean username
            const cleanUsername = newUsername.replace('@', '').trim();

            fetch(`/admin/purchases/${purchaseId}/update-username`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    telegram_username: cleanUsername
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update both the display in modal and main table
                    document.getElementById('admin-telegram-username-display').textContent = '@' + data.username;

                    // Update main table if visible
                    const tableCell = document.querySelector(`tr[data-purchase-id="${purchaseId}"] .telegram-username`);
                    if (tableCell) {
                        tableCell.textContent = '@' + data.username;
                    }

                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message || 'Error al actualizar el nombre de usuario');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Ha ocurrido un error al actualizar el nombre de usuario');
            });
        }

        // Alert function
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
            document.body.insertAdjacentHTML('beforeend', alertHtml);

            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }

        // ── Messaging ──────────────────────────────────────────────────
        let msgPollingIntervals = {};

        function sendAdminMessage(purchaseId) {
            const input = document.getElementById('msg-input-' + purchaseId);
            const text  = input.value.trim();
            if (!text) return;

            fetch(`/admin/purchases/${purchaseId}/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: text }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    appendMessage(purchaseId, data.message);
                    input.value = '';
                } else {
                    showAlert('error', data.error || 'Error al enviar');
                }
            })
            .catch(() => showAlert('error', 'Error de conexión'));
        }

        function appendMessage(purchaseId, msg) {
            const thread = document.getElementById('msg-thread-' + purchaseId);
            if (!thread) return;
            const isAdmin = msg.sender_type === 'admin';
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-' + (isAdmin ? 'end' : 'start');
            const bubbleStyle = isAdmin
                ? 'background:#1d6ae5;border-radius:16px 16px 4px 16px;color:#fff;'
                : 'background:#2d2d42;border-radius:16px 16px 16px 4px;color:#e8e8f0;';
            const metaStyle = isAdmin
                ? 'font-size:.68rem;opacity:.75;margin-bottom:2px;'
                : 'font-size:.68rem;color:#9d9db8;margin-bottom:2px;';
            const safeMsg = msg.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            div.innerHTML = `
                <div style="max-width:78%;${bubbleStyle}padding:8px 13px;font-size:.855rem;">
                    <div style="${metaStyle}">${msg.sender_name} · ${msg.time}</div>
                    <div>${safeMsg}</div>
                </div>`;
            thread.appendChild(div);
            thread.scrollTop = thread.scrollHeight;
            if (msg.id) thread.dataset.lastId = msg.id;
        }

        function startMsgPolling(purchaseId) {
            if (msgPollingIntervals[purchaseId]) return;
            msgPollingIntervals[purchaseId] = setInterval(() => {
                const thread = document.getElementById('msg-thread-' + purchaseId);
                if (!thread) { stopMsgPolling(purchaseId); return; }
                const afterId = thread.dataset.lastId || '0';
                fetch(`/admin/purchases/${purchaseId}/messages?after_id=${afterId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.messages && data.messages.length) {
                            data.messages.forEach(m => appendMessage(purchaseId, m));
                        }
                    });
            }, 5000);
        }

        function stopMsgPolling(purchaseId) {
            clearInterval(msgPollingIntervals[purchaseId]);
            delete msgPollingIntervals[purchaseId];
        }

        // Start polling when purchase modal opens
        document.addEventListener('shown.bs.modal', function(e) {
            const thread = e.target.querySelector('[id^="msg-thread-"]');
            if (thread) {
                const purchaseId = thread.dataset.purchase;
                thread.scrollTop = thread.scrollHeight;
                startMsgPolling(purchaseId);
            }
        });
        document.addEventListener('hidden.bs.modal', function(e) {
            const thread = e.target.querySelector('[id^="msg-thread-"]');
            if (thread) stopMsgPolling(thread.dataset.purchase);
        });

        // Allow Enter to send (Shift+Enter for newline)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && e.target.id && e.target.id.startsWith('msg-input-')) {
                e.preventDefault();
                const purchaseId = e.target.id.replace('msg-input-', '');
                sendAdminMessage(purchaseId);
            }
        });
        // ── End Messaging ───────────────────────────────────────────────

        function updateReportStatus(reportId, status) {
            fetch(`/admin/reports/${reportId}/status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ status })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Error');
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 800);
                })
                .catch(error => showAlert('error', error.message));
        }

        function banCreator(reportId) {
            if (!confirm('Banear creador? Esto desactiva su modo creador.')) return;

            fetch(`/admin/reports/${reportId}/ban-creator`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Error');
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 800);
                })
                .catch(error => showAlert('error', error.message));
        }

        function deleteCreator(reportId) {
            if (!confirm('Eliminar cuenta del creador? Esta accion no se puede deshacer.')) return;

            fetch(`/admin/reports/${reportId}/delete-creator`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Error');
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 800);
                })
                .catch(error => showAlert('error', error.message));
        }
    </script>
@endsection
