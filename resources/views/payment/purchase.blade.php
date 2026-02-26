@extends('layout')

@section('title', 'Purchase Successful')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Purchase Successful!
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Purchase Status -->
                        <div class="alert alert-success">
                            <h5 class="alert-heading">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Payment Confirmed
                            </h5>
                            <p class="mb-0">Your payment has been successfully processed. Your purchase details are below.
                            </p>
                        </div>

                        <!-- Video Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-video me-2"></i>
                                            Video Details
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
                                            Purchase Information
                                        </h6>
                                        <p><strong>Purchase ID:</strong> {{ $purchase->purchase_uuid }}</p>
                                        <p><strong>Date:</strong> {{ $purchase->created_at->format('M d, Y H:i:s') }}</p>
                                        <p><strong>Status:</strong>
                                            <span class="badge bg-success">{{ ucfirst($purchase->purchase_status) }}</span>
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
                                    Delivery Status
                                </h6>

                                @if ($purchase->verification_status === 'pending')
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-clock me-2"></i>
                                            @if($purchase->creator_id)
                                                Pendiente de aprobacion del creador
                                            @else
                                                Waiting for Telegram Verification
                                            @endif
                                        </h6>
                                        @if($purchase->creator_id)
                                            <p class="mb-2">Tu solicitud fue enviada al creador. Cuando valide tu pago, el acceso quedara activo para tu usuario de Telegram.</p>
                                        @else
                                            <p class="mb-2">To receive your video, please follow these steps:</p>
                                        @endif
                                        <ol>
                                            @if($purchase->creator_id)
                                                <li>Guarda este enlace de compra para volver luego:
                                                    <code>{{ route('purchase.view', $purchase->purchase_uuid) }}</code>
                                                </li>
                                                <li>Cuando el creador apruebe, vuelve a este mismo enlace y sigue las instrucciones de entrega.</li>
                                            @endif
                                            @if(!$purchase->creator_id)
                                                <li>Open Telegram and search for our bot</li>
                                                <li>Send the command <code>/start</code> to the bot</li>
                                            @endif
                                            @if ($purchase->telegram_username)
                                                <li>Make sure your Telegram username is:
                                                    <strong><span>@</span><span id="telegram-username-display">{{ $purchase->telegram_username }}</span></strong>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="editTelegramUsername()">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </li>
                                            @endif
                                        </ol>
                                        @if($purchase->creator_id)
                                            <small class="text-muted">Si el creador rechaza el pago, deberas contactar directamente con ese creador para cualquier reembolso. Si tienes problemas, usa el boton "Reportar creador".</small>
                                        @else
                                            <small class="text-muted">
                                                Once you start the bot with the same username you used during purchase,
                                                your video will be automatically delivered to you. This page will automatically
                                                refresh when your video is delivered.
                                            </small>

                                            <!-- Bot Conversation Button -->
                                            <div class="mt-3 text-center">
                                                @if($bot['is_configured'])
                                                    <a href="{{ $bot['url'] }}?start=getvideo_{{ $purchase->video_id }}" target="_blank" class="btn btn-success btn-lg">
                                                    <i class="fab fa-telegram me-2"></i>Get Your Video Now
                                                </a>
                                                @else
                                                    <a href="{{ route('login') }}" class="btn btn-warning btn-lg">
                                                        <i class="fas fa-cog me-2"></i>Bot Setup Required
                                                    </a>
                                                @endif
                                                <p class="text-muted mt-2 mb-0">
                                                    <small><i class="fas fa-info-circle me-1"></i>Click this button to get your video via `/getvideo {{ $purchase->video_id }}` command!</small>
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($purchase->verification_status === 'verified')
                                    @if ($purchase->delivery_status === 'delivered')
                                        <div class="alert alert-success">
                                            <h6 class="alert-heading">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Video Delivered!
                                            </h6>
                                            <p class="mb-1">Your video has been successfully delivered to your Telegram
                                                account.</p>
                                            @if($purchase->creator_id)
                                                <p class="mb-1">Si necesitas volver a recibirlo, abre el bot y usa <code>/getvideo {{ $purchase->video_id }}</code>.</p>
                                            @endif
                                            <small class="text-muted">Delivered on:
                                                {{ $purchase->delivered_at->format('M d, Y H:i:s') }}</small>

                                            <!-- Bot Access Button -->
                                            <div class="mt-3">
                                                @if($bot['is_configured'])
                                                    <a href="{{ $bot['url'] }}" target="_blank" class="btn btn-success">
                                                    <i class="fab fa-telegram me-2"></i>Open Bot Chat
                                                </a>
                                                @else
                                                    <a href="{{ route('login') }}" class="btn btn-warning">
                                                        <i class="fas fa-cog me-2"></i>Setup Required
                                                    </a>
                                                @endif
                                                <p class="text-muted mt-2 mb-0">
                                                    <small><i class="fas fa-video me-1"></i>Use <code>/getvideo {{ $purchase->video_id }}</code> anytime to get your video again!</small>
                                                </p>
                                            </div>
                                        </div>
                                    @elseif($purchase->delivery_status === 'pending')
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">
                                                <i class="fas fa-spinner fa-spin me-2"></i>
                                                Preparing Delivery
                                            </h6>
                                            @if($purchase->creator_id)
                                                <p class="mb-2">Pago aprobado por el creador. Sigue estos pasos para recibir tu video:</p>
                                                <ol class="mb-2">
                                                    <li>Abre Telegram y entra al bot.</li>
                                                    <li>Envia <code>/start</code> si es tu primera vez.</li>
                                                    <li>Envia <code>/getvideo {{ $purchase->video_id }}</code> con el mismo usuario usado en la compra.</li>
                                                </ol>
                                                <small class="text-muted">Si no llega en 1-2 minutos, actualiza esta pagina y vuelve a ejecutar <code>/getvideo {{ $purchase->video_id }}</code>.</small>
                                            @else
                                                <p class="mb-0">Your video is being prepared for delivery. You'll receive it
                                                    shortly on Telegram.</p>
                                            @endif
                                        </div>
                                    @elseif($purchase->delivery_status === 'failed')
                                        <div class="alert alert-danger">
                                            <h6 class="alert-heading">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Delivery Issue
                                            </h6>
                                            <p class="mb-1">There was an issue delivering your video. Our team has been
                                                notified.</p>
                                            @if ($purchase->delivery_notes)
                                                <small class="text-muted">{{ $purchase->delivery_notes }}</small>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if($purchase->creator_id)
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
                                Browse More Videos
                            </a>
                        </div>

                        <!-- Support Information -->
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Need help? Contact our support team with your Purchase ID:
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
                    <h5 class="modal-title">Edit Telegram Username</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUsernameForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_telegram_username" class="form-label">Telegram Username</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" class="form-control" id="new_telegram_username"
                                       name="telegram_username" value="{{ $purchase->telegram_username }}" required>
                            </div>
                            <div class="form-text">Enter your correct Telegram username (without the @ symbol)</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Make sure this matches exactly with your Telegram username.
                            You'll need to contact our bot with this username to receive your video.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Username</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($purchase->creator_id)
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
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
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
                    showAlert('success', 'Telegram username updated successfully!');

                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('editUsernameModal')).hide();
                } else {
                    showAlert('error', data.message || 'Failed to update username');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating the username');
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

        @if($purchase->creator_id)
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
