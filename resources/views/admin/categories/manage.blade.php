@extends('admin.layout')

@section('title', 'Categorias por creador')

@section('content')
<div class="container-fluid">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-0"><i class="fas fa-folder me-2 text-primary"></i>Categorías</h2>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Creadores ({{ $creators->count() }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Creador</th>
                                            <th>Slug</th>
                                            <th>Categorias</th>
                                            <th>Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($creators as $creator)
                                            <tr>
                                                <td>
                                                    <strong>{{ $creator->creator_store_name ?? $creator->name }}</strong>
                                                    @if($creator->is_admin)
                                                        <span class="badge text-bg-warning ms-2">Admin</span>
                                                    @endif
                                                </td>
                                                <td><code>{{ $creator->creator_slug }}</code></td>
                                                <td>{{ $creator->categories_count }}</td>
                                                <td>
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.creator', $creator) }}">
                                                        Ver categorias
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">No hay creadores registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
