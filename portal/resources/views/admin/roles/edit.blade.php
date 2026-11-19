@extends('layouts.portal')

@section('content')
<div style="padding:20px;max-width:980px">
  <h2>Edit role: {{ $role->name }}</h2>

  @if(session('success'))
    <div style="background:#dcfce7;color:#166534;padding:10px;border-radius:8px;margin:10px 0;">
      {{ session('success') }}
    </div>
  @endif

  <form method="POST" action="{{ route('admin.roles.update', $role) }}">
    @csrf

    <div style="display:flex;flex-wrap:wrap;gap:10px;padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
      @foreach($permissions as $p)
        <label style="display:flex;align-items:center;gap:6px;">
          <input type="checkbox" name="permissions[]" value="{{ $p->name }}" @checked(in_array($p->name, $rolePerms, true))>
          {{ $p->name }}
        </label>
      @endforeach
    </div>

    <button style="padding:10px 14px;margin-top:16px;">Save</button>
    <a href="{{ route('admin.roles.index') }}" style="margin-left:10px;">Back</a>
  </form>
</div>
@endsection
