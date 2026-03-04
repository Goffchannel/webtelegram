@extends('layout')

@section('title', 'Compra completada')

@section('styles')
<style>
body { background: #0e1117 !important; }
main.container { max-width: 100% !important; padding: 16px !important; margin-top: 0 !important; }
.card { background: #161b25; border-color: #252d3d; color: #e2e8f0; }
.card-header { background: #1e2535; border-color: #252d3d; color: #e2e8f0; }
.text-muted { color: #64748b !important; }
.alert-success { background: #14291a; border-color: #22c55e; color: #86efac; }
.alert-warning { background: #2a2010; border-color: #f59e0b; color: #fcd34d; }
.alert-info    { background: #0e1e2a; border-color: #4f8ef7; color: #93c5fd; }
.alert-danger  { background: #2a1010; border-color: #ef4444; color: #fca5a5; }
.alert-secondary { background: #1a1f2e; border-color: #252d3d; color: #94a3b8; }
.badge.bg-success { background: #22c55e !important; color: #0a1a0f; }
.btn-outline-secondary { border-color: #475569; color: #94a3b8; }
.btn-outline-secondary:hover { background: #1e2535; color: #e2e8f0; }
.btn-outline-danger { border-color: #ef4444; color: #fca5a5; }
code { background: #252d3d; color: #93c5fd; padding: 1px 5px; border-radius: 4px; }
.modal-content { background: #161b25; border-color: #252d3d; color: #e2e8f0; }
.modal-header  { border-color: #252d3d; }
.modal-footer  { border-color: #252d3d; }
.form-control  { background: #0e1117; border-color: #252d3d; color: #e2e8f0; }
.form-control:focus { background: #0e1117; border-color: #4f8ef7; color: #e2e8f0; box-shadow: 0 0 0 3px rgba(79,142,247,.2); }
.input-group-text { background: #1e2535; border-color: #252d3d; color: #94a3b8; }
.form-text { color: #64748b; }
.form-select { background: #0e1117; border-color: #252d3d; color: #e2e8f0; }
</style>
@endsection

@section('content')
    @php
        $isManualCreatorFlow = $purchase->creator_id && $purchase->creator && !$purchase->creator->is_admin;
        $isServiceProduct = $purchase->video && $purchase->video->isServiceProduct();
    @endphp
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Compra completada
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Purchase Status -->
                        <div class="alert alert-success">
                            <h5 class="alert-heading">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Pago confirmado
                            </h5>
                            <p class="mb-0">Tu pago se proceso correctamente. Debajo tienes los detalles de tu compra.
                            </p>
                        </div>

                        <!-- Video Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas {{ $isServiceProduct ? 'fa-tv' : 'fa-video' }} me-2"></i>
                                            {{ $isServiceProduct ? 'Detalles del servicio' : 'Detalles del video' }}
                                        </h6>
                                        <h5>{{ $purchase->video->title }}</h5>
                                        @if ($purchase->video->description)
                                            <p class="text-muted">{{ $purchase->video->description }}</p>
                                        @endif
                                        <p class="h4 text-success">{{ $purchase->formatted_amount }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-receipt me-2"></i>
                                            Informacion de la compra
                                        </h6>
                                        <p><strong>ID de compra:</strong> {{ $purchase->purchase_uuid }}</p>
                                        <p><strong>Fecha:</strong> {{ $purchase->created_at->format('d/m/Y H:i:s') }}</p>
                                        <p><strong>Estado:</strong>
                                            <span class="badge bg-success">
                                                @php $statusLabels = ['completed' => 'Completado', 'pending' => 'Pendiente', 'failed' => 'Fallido', 'refunded' => 'Reembolsado']; @endphp
                                                {{ $statusLabels[$purchase->purchase_status] ?? ucfirst($purchase->purchase_status) }}
                                            </span>
                                        </p>
                                        @if ($purchase->customer_email)
                                            <p><strong>Email:</strong> {{ $purchase->customer_email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Status -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-truck me-2"></i>
                                    Estado de entrega
                                </h6>

                                @if ($purchase->verification_status === 'pending')
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-clock me-2"></i>
                                            @if($isManualCreatorFlow)
                                                Pendiente de aprobacion del creador
                                            @elseif($isServiceProduct)
                                                Activa tu suscripcion en Telegram
                                            @else
                                                Esperando verificacion en Telegram
                                            @endif
                                        </h6>
                                        @if($isManualCreatorFlow)
                                            <p class="mb-2">Tu solicitud fue enviada al creador. Cuando valide tu pago, el acceso quedara activo para tu usuario de Telegram.</p>
                                        @elseif($isServiceProduct)
                                            <p class="mb-2">Tu compra se proceso correctamente. Sigue estos pasos para activar tu acceso:</p>
                                        @else
                                            <p class="mb-2">Para recibir tu video, sigue estos pasos:</p>
                                        @endif
                                        <ol>
                                            @if($isManualCreatorFlow)
                                                <li>Guarda este enlace de compra para volver luego:
                                                    <code>{{ route('purchase.view', $purchase->purchase_uuid) }}</code>
                                                </li>
                                                <li>Cuando el creador apruebe, vuelve a este mismo enlace y sigue las instrucciones de entrega.</li>
                                            @endif
                                            @if($isServiceProduct)
                                                <li>Pulsa el botón de abajo para activar tu suscripción en Telegram</li>
                                            @elseif(!$purchase->creator_id)
                                                <li>Abre Telegram y busca nuestro bot</li>
                                                <li>Envia el comando <code>/start</code> al bot</li>
                                            @endif
                                            @if ($purchase->telegram_username)
                                                <li>Asegurate de que tu usuario de Telegram es:
                                                    <strong><span>@</span><span id="telegram-username-display">{{ $purchase->telegram_username }}</span></strong>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="editTelegramUsername()">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                </li>
                                            @endif
                                        </ol>
                                        @if($isManualCreatorFlow)
                                            <small class="text-muted">Si el creador rechaza el pago, deberas contactar directamente con ese creador para cualquier reembolso. Si tienes problemas, usa el boton "Reportar creador".</small>
                                        @else
                                            <small class="text-muted">
                                                Cuando inicies el bot con el mismo usuario usado en la compra,
                                                tu video se entregara automaticamente. Esta pagina se actualiza sola
                                                cuando el video se entregue.
                                            </small>

                                            <!-- Bot Conversation Button -->
                                            <div class="mt-3 text-center">
                                                @if($bot['is_configured'])
                                                    <a href="{{ $bot['url'] }}?start=getvideo_{{ $purchase->video_id }}" target="_blank" class="btn btn-success btn-lg">
                                                        <i class="fab fa-telegram me-2"></i>
                                                        {{ $isServiceProduct ? 'Activar suscripción IPTV' : 'Recibir video ahora' }}
                                                    </a>
                                                @else
                                                    <a href="{{ route('login') }}" class="btn btn-warning btn-lg">
                                                        <i class="fas fa-cog me-2"></i>Falta configurar bot
                                                    </a>
                                                @endif
                                                <p class="text-muted mt-2 mb-0">
                                                    @if($isServiceProduct)
                                                        <small><i class="fas fa-info-circle me-1"></i>El bot te enviará tu enlace de acceso IPTV personal al instante.</small>
                                                    @else
                                                        <small><i class="fas fa-info-circle me-1"></i>Pulsa este boton para recibir tu video con el comando <code>/getvideo {{ $purchase->video_id }}</code>.</small>
                                                    @endif
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($purchase->verification_status === 'verified')
                                    @if ($purchase->delivery_status === 'delivered')
                                        @if($isServiceProduct && $purchase->serviceAccess)
                                            {{-- ================================================ --}}
                                            {{-- IPTV / Service access: call-to-action prominente --}}
                                            {{-- ================================================ --}}
                                            <div class="py-2">
                                                <div class="text-center mb-3">
                                                    <div style="font-size: 3rem; line-height:1;" class="mb-1">📺</div>
                                                    <h5 class="fw-bold mb-0">¡Tu suscripción IPTV está activa!</h5>
                                                    <div class="d-flex justify-content-center gap-3 mt-2 text-muted" style="font-size:.82rem;">
                                                        <span><i class="fas fa-calendar-alt me-1"></i>Expira: <strong>{{ $purchase->serviceAccess->expires_at->format('d/m/Y') }}</strong></span>
                                                        <span><i class="fas fa-clock me-1"></i>Activado: {{ $purchase->delivered_at->format('H:i') }}</span>
                                                    </div>
                                                </div>

                                                {{-- Paso a paso Plooplayer --}}
                                                <div style="background:#0e1117;border:1px solid #252d3d;border-radius:12px;padding:16px 20px;" class="mb-3">
                                                    <p class="fw-semibold mb-2" style="color:#e2e8f0;font-size:.9rem;">
                                                        <i class="fas fa-list-ol me-2" style="color:#4f8ef7;"></i>Cómo ver tu IPTV en Plooplayer:
                                                    </p>
                                                    <ol style="color:#94a3b8;font-size:.85rem;padding-left:1.2rem;margin:0;line-height:1.9;">
                                                        <li>Descarga <strong style="color:#e2e8f0;">Plooplayer</strong> en tu dispositivo (Android / iOS / Smart TV)</li>
                                                        <li>Pulsa el botón verde de abajo para obtener <strong style="color:#e2e8f0;">tu enlace personal</strong></li>
                                                        <li>En la página siguiente, copia el enlace que aparece</li>
                                                        <li>Abre Plooplayer → <em>Añadir lista</em> → pega el enlace → guarda</li>
                                                        <li>¡Listo! Ya puedes ver todos los canales</li>
                                                    </ol>
                                                </div>

                                                <div class="text-center">
                                                    <a class="btn btn-success btn-lg px-5 fw-bold shadow"
                                                       target="_blank"
                                                       href="{{ route('service.access.show', $purchase->serviceAccess->access_token) }}"
                                                       style="font-size:1.05rem;">
                                                        <i class="fas fa-tv me-2"></i>Obtener mi enlace IPTV
                                                    </a>
                                                    <p class="text-muted mt-2 mb-0" style="font-size:.8rem;">
                                                        <i class="fas fa-shield-alt me-1"></i>Enlace personal e intransferible — no lo compartas
                                                    </p>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-success">
                                                <h6 class="alert-heading">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    Video entregado
                                                </h6>
                                                <p class="mb-1">Tu video se entrego correctamente en tu cuenta de Telegram.</p>
                                                @if($purchase->creator_id)
                                                    <p class="mb-1">Si necesitas volver a recibirlo, abre el bot y usa <code>/getvideo {{ $purchase->video_id }}</code>.</p>
                                                @endif
                                                <small class="text-muted">Entregado el: {{ $purchase->delivered_at->format('M d, Y H:i:s') }}</small>
                                                <div class="mt-3">
                                                    @if($bot['is_configured'])
                                                        <a href="{{ $bot['url'] }}" target="_blank" class="btn btn-success">
                                                            <i class="fab fa-telegram me-2"></i>Abrir chat del bot
                                                        </a>
                                                    @else
                                                        <a href="{{ route('login') }}" class="btn btn-warning">
                                                            <i class="fas fa-cog me-2"></i>Falta configuracion
                                                        </a>
                                                    @endif
                                                    <p class="text-muted mt-2 mb-0">
                                                        <small><i class="fas fa-video me-1"></i>Usa <code>/getvideo {{ $purchase->video_id }}</code> cuando quieras para recibirlo otra vez.</small>
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($purchase->delivery_status === 'pending')
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">
                                                <i class="fas fa-spinner fa-spin me-2"></i>
                                                Preparando entrega
                                            </h6>
                                            @if($isManualCreatorFlow && !$isServiceProduct)
                                                <p class="mb-2">Pago aprobado por el creador. Sigue estos pasos para recibir tu video:</p>
                                                <ol class="mb-2">
                                                    <li>Abre Telegram y entra al bot.</li>
                                                    <li>Envia <code>/start</code> si es tu primera vez.</li>
                                                    <li>Envia <code>/getvideo {{ $purchase->video_id }}</code> con el mismo usuario usado en la compra.</li>
                                                </ol>
                                                <small class="text-muted">Si no llega en 1-2 minutos, actualiza esta pagina y vuelve a ejecutar <code>/getvideo {{ $purchase->video_id }}</code>.</small>
                                            @elseif(!$isServiceProduct)
                                                <p class="mb-0">Tu video se esta preparando para entrega. Lo recibiras en breve en Telegram.</p>
                                            @else
                                                <p class="mb-0">Estamos activando tu acceso de servicio. Recarga en unos segundos.</p>
                                            @endif
                                        </div>
                                    @elseif($purchase->delivery_status === 'failed')
                                        <div class="alert alert-danger">
                                            <h6 class="alert-heading">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Problema de entrega
                                            </h6>
                                            <p class="mb-1">Hubo un problema al entregar tu video. El equipo ya fue notificado.</p>
                                            @if ($purchase->delivery_notes)
                                                <small class="text-muted">{{ $purchase->delivery_notes }}</small>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if($isManualCreatorFlow)
                            <div class="card mb-3 border-danger">
                                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                    <div>
                                        <h6 class="mb-1 text-danger"><i class="fas fa-flag me-2"></i>Problema con este creador</h6>
                                        <small class="text-muted">Si hubo fraude, no entrega o mal trato, envianos un reporte para revisarlo desde admin.</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger" onclick="openCreatorReportModal()">
                                        Reportar creador
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="text-center">
                            <a href="{{ route('videos.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Ver mas videos
                            </a>
                        </div>

                        @if($isServiceProduct && $purchase->video && $purchase->video->fan_message)
                            <div class="mt-3 alert alert-secondary" style="white-space: pre-wrap;">{{ $purchase->video->fan_message }}</div>
                        @endif

                        <!-- Support Information -->
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Necesitas ayuda? Contacta soporte con tu ID de compra:
                                <strong>{{ $purchase->purchase_uuid }}</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Telegram Username Modal -->
    <div class="modal fade" id="editUsernameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar usuario de Telegram</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUsernameForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_telegram_username" class="form-label">Usuario de Telegram</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" class="form-control" id="new_telegram_username"
                                       name="telegram_username" value="{{ $purchase->telegram_username }}" required>
                            </div>
                            <div class="form-text">Escribe tu usuario correcto de Telegram (sin @)</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong> Debe coincidir exactamente con tu usuario de Telegram.
                            Tienes que escribir al bot con ese mismo usuario para recibir el video.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($isManualCreatorFlow)
        <div class="modal fade" id="reportCreatorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reportar creador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="reportCreatorForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Motivo</label>
                                <select class="form-select" name="reason" required>
                                    <option value="">Selecciona un motivo</option>
                                    <option value="No entrega el video">No entrega el video</option>
                                    <option value="Pago aprobado pero sin acceso">Pago aprobado pero sin acceso</option>
                                    <option value="Contenido incorrecto">Contenido incorrecto</option>
                                    <option value="Fraude o estafa">Fraude o estafa</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripcion del problema</label>
                                <textarea class="form-control" name="message" rows="4" required placeholder="Explica lo ocurrido..."></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Tu nombre (opcional)</label>
                                    <input class="form-control" name="reporter_name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telegram (opcional)</label>
                                    <input class="form-control" name="reporter_telegram" placeholder="@usuario">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email (opcional)</label>
                                    <input type="email" class="form-control" name="reporter_email">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Enviar reporte</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        // Auto-refresh every 30 seconds if verification is pending or delivery is pending
        @if (
            $purchase->verification_status === 'pending' ||
                ($purchase->verification_status === 'verified' && $purchase->delivery_status === 'pending'))
            function shouldPausePurchaseAutoRefresh() {
                // Do not reload while the user is typing or interacting with any modal.
                if (document.querySelector('.modal.show')) {
                    return true;
                }

                const active = document.activeElement;
                if (!active) {
                    return false;
                }

                const tag = active.tagName;
                return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
            }

            setInterval(function() {
                if (shouldPausePurchaseAutoRefresh()) {
                    return;
                }
                window.location.reload();
            }, 30000);
        @endif

        // Edit telegram username functionality
        function editTelegramUsername() {
            new bootstrap.Modal(document.getElementById('editUsernameModal')).show();
        }

        // Handle username update form submission
        document.getElementById('editUsernameForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
            submitButton.disabled = true;

            fetch(`/purchase/{{ $purchase->purchase_uuid }}/update-username`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the display
                    document.getElementById('telegram-username-display').textContent = data.username;

                    // Show success message
                    showAlert('success', 'Usuario de Telegram actualizado correctamente.');

                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('editUsernameModal')).hide();
                } else {
                    showAlert('error', data.message || 'No se pudo actualizar el usuario');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Ocurrio un error al actualizar el usuario');
            })
            .finally(() => {
                // Reset button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });

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

        @if($isManualCreatorFlow)
        function openCreatorReportModal() {
            new bootstrap.Modal(document.getElementById('reportCreatorModal')).show();
        }

        document.getElementById('reportCreatorForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';

            fetch(`/purchase/{{ $purchase->purchase_uuid }}/report`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Reporte enviado');
                    bootstrap.Modal.getInstance(document.getElementById('reportCreatorModal')).hide();
                    document.getElementById('reportCreatorForm').reset();
                } else {
                    showAlert('error', data.message || 'No se pudo enviar el reporte');
                }
            })
            .catch(() => {
                showAlert('error', 'Error enviando el reporte');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
        @endif
    </script>
@endsection
