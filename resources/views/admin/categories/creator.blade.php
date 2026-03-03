@extends('admin.layout')

@section('title', 'Categorias de ' . ($creator->creator_store_name ?? $creator->name))

@section('content')
<div class="container-fluid">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-2xl font-bold mb-0">
                            Categorias de {{ $creator->creator_store_name ?? $creator->name }}
                        </h2>
                        <a href="{{ route('admin.categories.manage') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver a creadores
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus-circle text-success"></i> Crear categoria</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.categories.store', $creator) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="name" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="image" class="form-label">Subir imagen</label>
                                        <input type="file" class="form-control" id="image" name="image">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="image_url" class="form-label">O URL de imagen</label>
                                        <input type="url" class="form-control" id="image_url" name="image_url">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Crear categoria</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-layer-group"></i> Categorias ({{ $categories->count() }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th>Videos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categories as $category)
                                            <tr>
                                                <td>
                                                    @if ($category->hasImage())
                                                        <img src="{{ $category->getImageUrl() }}" alt="Image" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                                    @else
                                                        <span class="text-muted">No image</span>
                                                    @endif
                                                </td>
                                                <td>{{ $category->name }}</td>
                                                <td>
                                                    @if($category->is_hidden)
                                                        <span class="badge text-bg-dark">HIDE</span>
                                                    @else
                                                        <span class="badge text-bg-success">VISIBLE</span>
                                                    @endif
                                                </td>
                                                <td>{{ $category->videos_count }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ $category->getImageUrl() }}')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleHideCategory({{ $category->id }})">
                                                            @if($category->is_hidden)Show @else Hide @endif
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" onclick="deleteCategory({{ $category->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Este creador no tiene categorias.</td>
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

<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" onsubmit="updateCategory(event)">
                <div class="modal-body">
                    <input type="hidden" id="edit-category-id">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="edit-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagen actual</label>
                        <div id="current-image-preview"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-image" class="form-label">Subir nueva imagen</label>
                        <input type="file" class="form-control" id="edit-image">
                    </div>
                    <div class="mb-3">
                        <label for="edit-image-url" class="form-label">O nueva URL</label>
                        <input type="url" class="form-control" id="edit-image-url">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function editCategory(id, name, imageUrl) {
        document.getElementById('edit-category-id').value = id;
        document.getElementById('edit-name').value = name;

        const preview = document.getElementById('current-image-preview');
        if (imageUrl) {
            preview.innerHTML = `<img src="${imageUrl}" style="width: 100px; height: 100px; object-fit: cover;" class="rounded">`;
        } else {
            preview.innerHTML = '<p class="text-muted">No image</p>';
        }

        document.getElementById('edit-image').value = '';
        document.getElementById('edit-image-url').value = '';

        const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }

    function updateCategory(event) {
        event.preventDefault();
        const id = document.getElementById('edit-category-id').value;
        const name = document.getElementById('edit-name').value;
        const image = document.getElementById('edit-image').files[0];
        const imageUrl = document.getElementById('edit-image-url').value;

        const formData = new FormData();
        formData.append('name', name);
        if (image) {
            formData.append('image', image);
        } else if (imageUrl) {
            formData.append('image_url', imageUrl);
        }

        fetch(`{{ route('admin.categories.creator', $creator) }}/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Could not update category.'));
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function deleteCategory(id) {
        if (!confirm('Eliminar esta categoria? Los videos pasaran a General o quedaran sin categoria.')) {
            return;
        }

        fetch(`{{ route('admin.categories.creator', $creator) }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Could not delete category.'));
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function toggleHideCategory(id) {
        fetch(`{{ route('admin.categories.creator', $creator) }}/${id}/toggle-hide`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Could not update visibility.'));
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
@endsection
