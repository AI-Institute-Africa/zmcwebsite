@extends('layouts.portal')

@section('title', 'Permission Matrix')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Permission Matrix</h4>
      <div class="text-muted" style="font-size:13px;">Roles vs permissions overview.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.roles.index') }}">Roles</a>
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.permissions.index') }}">Permissions</a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Permission</th>
              @foreach($roles as $r)
                <th class="text-center" style="white-space:nowrap;">{{ $r->name }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($permissions as $p)
              <tr>
                <td><code>{{ $p->name }}</code></td>
                @foreach($roles as $r)
                  <td class="text-center">
                    @if(!empty($matrix[$r->name][$p->name]))
                      <i class="ri-check-line text-success"></i>
                    @else
                      <i class="ri-close-line text-muted"></i>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
