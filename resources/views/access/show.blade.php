@extends('layout')

@section('title', 'Acceso de servicio')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <strong>Acceso activo</strong>
                    <span class="badge text-bg-success">Vence: {{ $access->expires_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="card-body">
                    <h4>{{ $access->video->title }}</h4>
                    @if($access->video->fan_message)
                        <div class="alert alert-info" style="white-space: pre-wrap;">{{ $access->video->fan_message }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Nombre de linea</label>
                        <input class="form-control" value="{{ $access->line->line_name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL M3U</label>
                        <input class="form-control" value="{{ $access->line->m3u_url }}" readonly>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <input class="form-control" value="{{ $access->line->line_username }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contrasena</label>
                            <input class="form-control" value="{{ $access->line->line_password }}" readonly>
                        </div>
                    </div>

                    @if($access->line->notes)
                        <div class="mt-3">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" rows="3" readonly>{{ $access->line->notes }}</textarea>
                        </div>
                    @endif

                    @if($access->video->access_instructions)
                        <div class="mt-3 alert alert-secondary" style="white-space: pre-wrap;">
                            <strong>Instrucciones:</strong><br>
                            {{ $access->video->access_instructions }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
