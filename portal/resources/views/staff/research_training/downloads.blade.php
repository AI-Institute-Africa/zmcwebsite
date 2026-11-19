@extends('layouts.staff')
@section('title','Complaint Downloads')
@section('content')
<div class="container-fluid py-4" style="font-family:Inter,sans-serif;background:#f8fafc;min-height:100vh;">
  <div class="mb-4"><h2 class="fw-bold mb-1">Downloads</h2><p class="text-muted mb-0">Central repository for documents submitted with complaints and staff responses.</p></div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="px-4">Document</th><th>Complaint</th><th>Source</th><th>Uploaded</th><th class="text-end px-4">Download</th></tr></thead><tbody>
    @forelse($complaints as $complaint)<tr><td class="px-4"><i class="ri-file-text-line me-2 text-primary"></i>{{ $complaint->attachment_original_name ?: 'Complaint attachment' }}</td><td>{{ $complaint->reference_number ?: 'COMP-'.$complaint->id }}<br><small class="text-muted">{{ $complaint->full_name }}</small></td><td>Complainant</td><td>{{ $complaint->created_at?->format('d M Y') }}</td><td class="text-end px-4"><a href="{{ route('staff.research.download', $complaint) }}" class="btn btn-sm btn-outline-dark"><i class="ri-download-line"></i></a></td></tr>@empty @endforelse
    @foreach($communications as $communication) @foreach(($communication->attachments ?? []) as $index=>$attachment)<tr><td class="px-4"><i class="ri-file-upload-line me-2 text-success"></i>{{ $attachment['name'] ?? 'Supporting document' }}</td><td>{{ $communication->complaint->reference_number ?: 'COMP-'.$communication->complaint->id }}<br><small class="text-muted">{{ $communication->complaint->full_name }}</small></td><td>Complaints Desk response</td><td>{{ $communication->created_at?->format('d M Y') }}</td><td class="text-end px-4"><a href="{{ route('staff.research.download.communication', [$communication->complaint, $communication->id, $index]) }}" class="btn btn-sm btn-outline-dark"><i class="ri-download-line"></i></a></td></tr>@endforeach @endforeach
    @if($complaints->isEmpty() && $communications->isEmpty())<tr><td colspan="5" class="text-center text-muted py-5">No complaint documents have been uploaded.</td></tr>@endif
  </tbody></table></div></div>
</div>
@endsection
