<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-receipt me-2"></i>Purchase Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>UUID:</strong></td>
                <td><code style="font-size: 11px;">{{ $purchase->purchase_uuid }}</code></td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td>{{ $purchase->created_at->format('M d, Y H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Amount:</strong></td>
                <td><span class="h6 text-success">{{ $purchase->formatted_amount }}</span></td>
            </tr>
            <tr>
                <td><strong>Currency:</strong></td>
                <td>{{ strtoupper($purchase->currency) }}</td>
            </tr>
            <tr>
                <td><strong>Purchase Status:</strong></td>
                <td>
                    <span class="badge {{ $purchase->purchase_status === 'completed' ? 'text-bg-success' : 'text-bg-warning' }}">
                        {{ ucfirst($purchase->purchase_status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Verification:</strong></td>
                <td>
                    @if ($purchase->verification_status === 'pending')
                        <span class="badge text-bg-warning">Pending</span>
                    @elseif($purchase->verification_status === 'verified')
                        <span class="badge text-bg-success">Verified</span>
                    @else
                        <span class="badge text-bg-danger">Invalid</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Telegram Username:</strong></td>
                <td>
                    <span id="admin-telegram-username-display"><span>@</span>{{ $purchase->telegram_username }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="editAdminTelegramUsername('{{ $purchase->id }}', '{{ $purchase->telegram_username }}')">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
            @if ($purchase->telegram_user_id)
                <tr>
                    <td><strong>Telegram User ID:</strong></td>
                    <td>{{ $purchase->telegram_user_id }}</td>
                </tr>
            @endif
            @if ($purchase->customer_email)
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $purchase->customer_email }}</td>
                </tr>
            @endif
            @if ($purchase->user)
                <tr>
                    <td><strong>Account Name:</strong></td>
                    <td>{{ $purchase->user->name }}</td>
                </tr>
            @endif
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h6><i class="fas fa-video me-2"></i>Video Information</h6>
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">{{ $purchase->video->title }}</h6>
                @if ($purchase->video->description)
                    <p class="card-text">{{ Str::limit($purchase->video->description, 100) }}</p>
                @endif
                <p class="card-text">
                    <small class="text-muted">Video ID: {{ $purchase->video->id }}</small><br>
                    <small class="text-muted">Price: ${{ number_format($purchase->video->price, 2) }}</small>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <h6><i class="fas fa-truck me-2"></i>Delivery Status</h6>
        <div class="card">
            <div class="card-body">
                <div class="mb-2">
                    @if ($purchase->delivery_status === 'pending')
                        <span class="badge text-bg-info">Pending</span>
                    @elseif($purchase->delivery_status === 'delivered')
                        <span class="badge text-bg-success">Delivered</span>
                    @elseif($purchase->delivery_status === 'failed')
                        <span class="badge text-bg-danger">Failed</span>
                    @else
                        <span class="badge text-bg-warning">Retrying</span>
                    @endif
                </div>

                @if ($purchase->delivered_at)
                    <p class="small text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Delivered: {{ $purchase->delivered_at->format('M d, Y H:i:s') }}
                    </p>
                @endif

                @if ($purchase->delivery_attempts > 0)
                    <p class="small text-muted">
                        Delivery attempts: {{ $purchase->delivery_attempts }}
                    </p>
                @endif

                @if ($purchase->delivery_notes)
                    <p class="small">
                        <strong>Notes:</strong> {{ $purchase->delivery_notes }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($purchase->stripe_metadata)
    <div class="row mt-3">
        <div class="col-12">
            <h6><i class="fab fa-stripe me-2"></i>Stripe Information</h6>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Session ID:</strong><br>
                            <code style="font-size: 10px;">{{ $purchase->stripe_session_id }}</code>
                        </div>
                        @if ($purchase->stripe_payment_intent_id)
                            <div class="col-md-4">
                                <strong>Payment Intent:</strong><br>
                                <code style="font-size: 10px;">{{ $purchase->stripe_payment_intent_id }}</code>
                            </div>
                        @endif
                        @if ($purchase->stripe_customer_id)
                            <div class="col-md-4">
                                <strong>Customer ID:</strong><br>
                                <code style="font-size: 10px;">{{ $purchase->stripe_customer_id }}</code>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Debug Information -->
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-bug me-2"></i>Debug Information</h6>
        <div class="card border-info">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted"><strong>Created:</strong></small><br>
                        <small>{{ $purchase->created_at->format('M d, Y H:i:s') }} ({{ $purchase->created_at->diffForHumans() }})</small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted"><strong>Last Updated:</strong></small><br>
                        <small>{{ $purchase->updated_at->format('M d, Y H:i:s') }} ({{ $purchase->updated_at->diffForHumans() }})</small>
                    </div>
                </div>

                @if($purchase->delivery_notes)
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted"><strong>Delivery Notes:</strong></small><br>
                            <small class="font-monospace">{{ $purchase->delivery_notes }}</small>
                        </div>
                    </div>
                @endif

                @if($purchase->delivery_metadata)
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted"><strong>Delivery Metadata:</strong></small><br>
                            <pre class="small p-2 rounded">{{ json_encode($purchase->delivery_metadata, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif

                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Troubleshooting:</strong> If verified but not delivered, the user needs to type <code>/start</code> in the bot again.
                            If they're the admin/sync user, create a different Telegram account for testing.
                        </small>

                        @php
                            $syncUserTelegramId = \App\Models\Setting::get('sync_user_telegram_id');
                        @endphp

                        @if($syncUserTelegramId && $purchase->telegram_user_id == $syncUserTelegramId)
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>⚠️ SYNC USER CONFLICT DETECTED!</strong><br>
                                <small>This purchase is from the configured sync user (ID: {{ $syncUserTelegramId }}).
                                The bot may not deliver videos properly to the sync user.
                                Use a different Telegram account for testing.</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Service access panel (IPTV subscriptions)                    --}}
{{-- ============================================================ --}}
@php $serviceAccess = $purchase->serviceAccess ?? null; @endphp
@if($serviceAccess)
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-tv me-2"></i>Acceso IPTV</h6>
        <div class="card border-{{ $serviceAccess->status === 'active' ? 'success' : ($serviceAccess->status === 'revoked' ? 'danger' : 'warning') }}">
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-auto">
                        <strong>Estado:</strong>
                        @if($serviceAccess->status === 'active')
                            <span class="badge bg-success">Activo</span>
                        @elseif($serviceAccess->status === 'revoked')
                            <span class="badge bg-danger">Revocado</span>
                        @else
                            <span class="badge bg-warning text-dark">Expirado</span>
                        @endif
                    </div>
                    <div class="col-auto">
                        <strong>Vence:</strong>
                        <span class="{{ $serviceAccess->isExpired() ? 'text-danger' : 'text-success' }}">
                            {{ $serviceAccess->expires_at?->format('d/m/Y H:i') ?? '—' }}
                        </span>
                    </div>
                    @if($serviceAccess->last_viewed_at)
                    <div class="col-auto">
                        <strong>Último acceso:</strong>
                        <span class="text-muted">{{ $serviceAccess->last_viewed_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">Link del suscriptor</label>
                    <input class="form-control form-control-sm font-monospace"
                        value="{{ route('iptv.playlist', $serviceAccess->access_token) }}" readonly>
                </div>

                {{-- IP Binding info --}}
                @php $boundIps = $serviceAccess->bound_ips ?? []; @endphp
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">
                        <i class="fas fa-shield-alt me-1 text-info"></i>IPs vinculadas
                        <span class="badge text-bg-secondary ms-1">{{ count($boundIps) }} / {{ $serviceAccess->max_ips ?? 1 }}</span>
                    </label>
                    @if(count($boundIps) > 0)
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            @foreach($boundIps as $ip)
                                <code class="small bg-light px-2 py-1 rounded border">{{ $ip }}</code>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small">Ninguna IP registrada aún (se vinculará en el primer acceso).</div>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    {{-- Renew --}}
                    <form method="POST"
                        action="{{ route('admin.purchases.service-access.renew', $purchase) }}"
                        class="d-flex gap-1 align-items-center">
                        @csrf
                        <input type="number" name="days" value="30" min="1" max="366"
                            class="form-control form-control-sm" style="width:70px;" title="Días a renovar">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-sync me-1"></i>Renovar
                        </button>
                    </form>

                    {{-- Reset IPs --}}
                    @if(count($boundIps) > 0)
                    <form method="POST"
                        action="{{ route('admin.purchases.service-access.reset-ips', $purchase) }}"
                        onsubmit="return confirm('¿Resetear IPs vinculadas? El suscriptor podrá acceder desde un nuevo dispositivo.')">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fas fa-map-marker-alt me-1"></i>Reset IPs
                        </button>
                    </form>
                    @endif

                    {{-- Revoke --}}
                    @if($serviceAccess->status !== 'revoked')
                    <form method="POST"
                        action="{{ route('admin.purchases.service-access.revoke', $purchase) }}"
                        onsubmit="return confirm('¿Revocar acceso IPTV inmediatamente?')">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-ban me-1"></i>Revocar
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- Messaging panel                                            --}}
{{-- ============================================================ --}}
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-comments me-2"></i>Mensajes con el cliente</h6>
        <div class="card border-secondary">
            <div class="card-body p-2">

                {{-- Chat thread --}}
                <div id="msg-thread-{{ $purchase->id }}"
                     style="min-height:80px; max-height:260px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; padding:8px; background:#1a1a2e; border-radius:8px;"
                     data-purchase="{{ $purchase->id }}"
                     data-last-ts="{{ $purchase->messages->last()?->created_at?->toIso8601String() ?? '' }}">
                    @forelse($purchase->messages as $msg)
                        @if($msg->sender_type === 'admin')
                            <div class="d-flex justify-content-end">
                                <div style="max-width:78%; background:#1d6ae5; border-radius:16px 16px 4px 16px; padding:8px 13px; font-size:.855rem; color:#fff;">
                                    <div style="font-size:.68rem; opacity:.75; margin-bottom:2px;">{{ $msg->sender_name }} · {{ $msg->created_at->format('H:i') }}</div>
                                    <div>{{ $msg->message }}</div>
                                </div>
                            </div>
                        @else
                            <div class="d-flex justify-content-start">
                                <div style="max-width:78%; background:#2d2d42; border-radius:16px 16px 16px 4px; padding:8px 13px; font-size:.855rem; color:#e8e8f0;">
                                    <div style="font-size:.68rem; color:#9d9db8; margin-bottom:2px;">{{ $msg->sender_name }} · {{ $msg->created_at->format('H:i') }}</div>
                                    <div>{{ $msg->message }}</div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <p style="color:#6b6b8a; text-align:center; font-size:.82rem; margin:auto 0;">Sin mensajes aún.</p>
                    @endforelse
                </div>

                <hr class="my-2">

                @if($purchase->telegram_user_id)
                    <div class="d-flex gap-2 mt-2">
                        <textarea id="msg-input-{{ $purchase->id }}"
                                  class="form-control form-control-sm"
                                  rows="2"
                                  style="resize:none; background:#1a1a2e; color:#e8e8f0; border-color:#3d3d5c;"
                                  placeholder="Escribe un mensaje... (Enter envía)"></textarea>
                        <button class="btn btn-primary btn-sm px-3"
                                onclick="sendAdminMessage({{ $purchase->id }})">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                @else
                    <div class="alert alert-warning py-2 mb-0 small mt-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        El comprador no ha vinculado su Telegram. Debe escribir <code>/start</code> al bot primero.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex gap-2">
            @if ($purchase->verification_status === 'pending')
                <button type="button" class="btn btn-success btn-sm" onclick="verifyPurchase('{{ $purchase->id }}')">
                    <i class="fas fa-check me-1"></i>Verify Purchase
                </button>
            @endif

            @if ($purchase->delivery_status === 'failed' && $purchase->canRetryDelivery())
                <button type="button" class="btn btn-warning btn-sm" onclick="retryDelivery('{{ $purchase->id }}')">
                    <i class="fas fa-redo me-1"></i>Retry Delivery
                </button>
            @endif

            @if ($purchase->delivery_status !== 'delivered')
                <button type="button" class="btn btn-primary btn-sm" onclick="markDelivered('{{ $purchase->id }}')">
                    <i class="fas fa-truck me-1"></i>Mark as Delivered
                </button>
            @endif

            <a href="{{ route('purchase.view', $purchase->purchase_uuid) }}" target="_blank"
                class="btn btn-outline-info btn-sm">
                <i class="fas fa-external-link-alt me-1"></i>Customer View
            </a>
        </div>
    </div>
</div>
