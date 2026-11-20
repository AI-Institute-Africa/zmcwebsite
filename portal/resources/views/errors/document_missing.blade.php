@extends('layouts.portal')
@section('title', 'Document Unavailable')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto',sans-serif; color:#334155;">
    <div class="zmc-card text-center py-5" style="max-width:680px; margin:40px auto;">
        <i class="ri-file-damage-line" style="font-size:64px; color:#f59e0b;"></i>
        <h1 class="fw-bold mt-3 mb-2" style="color:#1e293b;">Document File Not Available</h1>
        <p class="text-muted">
            We could not retrieve <strong>{{ $document->original_name ?: ('Document #'.$document->id) }}</strong> from storage.
            The record exists in the database, but the underlying file is missing on the server.
        </p>
        <div class="alert alert-light border text-start small mt-4 mb-4" style="max-width:560px; margin:0 auto;">
            <div><strong>Document ID:</strong> #{{ $document->id }}</div>
            <div><strong>Application:</strong> #{{ $document->application_id }}</div>
            <div><strong>Type:</strong> {{ str_replace('_',' ', (string)($document->doc_type ?? '—')) }}</div>
            <div><strong>Stored path:</strong> <code>{{ $document->file_path ?: '—' }}</code></div>
        </div>
        <p class="small text-muted">
            Please ask the applicant to re-upload this document, or contact the IT administrator if you believe the file should be present.
        </p>
        <div class="mt-4">
            <a href="javascript:history.back()" class="btn btn-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
        </div>
    </div>
</div>
@endsection
