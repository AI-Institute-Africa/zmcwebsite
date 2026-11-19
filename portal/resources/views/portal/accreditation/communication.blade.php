@extends('layouts.portal')

@section('title', 'Communication')
@section('page_title', 'COMMUNICATION')

@section('content')
<div id="communication-page">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0" style="font-size:18px;">Communication (Email)</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeEmailModal">
      <i class="ri-mail-add-line me-2"></i>Compose
    </button>
  </div>

  <div class="form-container">
    <div class="form-header">
      <h5 class="m-0"><i class="ri-inbox-line me-2"></i>Inbox (Demo)</h5>
      <p class="mt-2">In production: messages are stored in DB and linked to applications.</p>
    </div>

    <div class="form-steps-container">
      <div class="list-group">
        <button class="list-group-item list-group-item-action" onclick="alert('Demo: Open message thread')">
          <div class="d-flex justify-content-between">
            <div class="fw-bold">ZMC Notifications</div>
            <small class="text-muted">Today</small>
          </div>
          <div class="text-muted">Your application message will appear here when communication is enabled.</div>
        </button>

        <button class="list-group-item list-group-item-action" onclick="alert('Demo: Open message thread')">
          <div class="d-flex justify-content-between">
            <div class="fw-bold">Accreditation Officer</div>
            <small class="text-muted">Yesterday</small>
          </div>
          <div class="text-muted">Please upload a clearer scan of your ID document.</div>
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Compose Modal --}}
<div class="modal fade" id="composeEmailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-mail-add-line me-2"></i>Compose Email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">To</label>
          <input class="form-control" placeholder="name@example.com">
        </div>
        <div class="mb-3">
          <label class="form-label">Subject</label>
          <input class="form-control" placeholder="Subject">
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea class="form-control" rows="6" placeholder="Write your message..."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Attachment (optional)</label>
          <input type="file" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="alert('Demo: Email sent')"><i class="ri-send-plane-line me-1"></i>Send</button>
      </div>
    </div>
  </div>
</div>
@endsection
