@extends('layouts.portal')
@section('title', 'Appeal Settings')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold m-0" style="color:#0f172a;">Settings</h3>
      <div class="text-muted small">Manage appeal categories and configurations</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success"><i class="ri-check-line me-2"></i>{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    {{-- Categories Management --}}
    <div class="col-lg-8">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
          <h6 class="fw-bold m-0">Appeal Categories</h6>
          <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="ri-add-line me-1"></i>Add Category
          </button>
        </div>
        
        <div class="table-responsive">
          <table class="table table-hover align-middle zmc-table mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
                <tr>
                  <td class="fw-bold">{{ $category->name }}</td>
                  <td>{{ \Illuminate\Support\Str::limit($category->description, 50) }}</td>
                  <td>
                    @if($category->is_active)
                      <span class="badge bg-success">Active</span>
                    @else
                      <span class="badge bg-danger">Inactive</span>
                    @endif
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}">Edit</button>
                    <form method="POST" action="{{ route('staff.appeals.settings.category.destroy', $category) }}" class="d-inline">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                  </td>
                </tr>

                {{-- Edit Modal --}}
                <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1">
                  <div class="modal-dialog">
                    <form class="modal-content" method="POST" action="{{ route('staff.appeals.settings.category.update', $category) }}">
                      @csrf @method('PUT')
                      <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label zmc-lbl">Category Name</label>
                          <input type="text" name="name" class="form-control zmc-input" value="{{ $category->name }}" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label zmc-lbl">Description</label>
                          <textarea name="description" class="form-control zmc-input" rows="3">{{ $category->description }}</textarea>
                        </div>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }}>
                          <label class="form-check-label">Active</label>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>

              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">No categories configured.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- System Config placeholders --}}
    <div class="col-lg-4">
      <div class="zmc-card mb-4">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Email Configuration</h6>
        <p class="small text-muted mb-3">Manage automated email templates sent to applicants.</p>
        @if(Route::has('staff.it.templates-mgmt'))
          <a href="{{ route('staff.it.templates-mgmt') }}" class="btn btn-sm btn-outline-dark w-100">
            <i class="ri-mail-settings-line me-1"></i>Manage Templates
          </a>
        @else
          <span class="small text-muted">Email templates are managed centrally by the ICT Department.</span>
        @endif
      </div>
      
      <div class="zmc-card">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Roles & Permissions</h6>
        <p class="small text-muted mb-0">User accounts, roles and permissions are managed by the ICT Department. This dashboard is limited to appeal categories and communications settings.</p>
      </div>
    </div>
  </div>

</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('staff.appeals.settings.category.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label zmc-lbl">Category Name</label>
          <input type="text" name="name" class="form-control zmc-input" required>
        </div>
        <div class="mb-3">
          <label class="form-label zmc-lbl">Description</label>
          <textarea name="description" class="form-control zmc-input" rows="3"></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
          <label class="form-check-label">Active</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-dark">Add Category</button>
      </div>
    </form>
  </div>
</div>

@endsection
