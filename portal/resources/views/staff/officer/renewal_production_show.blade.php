@extends('layouts.portal')

@section('title', 'Renewal Production')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Renewal Production</h4>
            <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
                <i class="ri-information-line me-1"></i>{{ $renewal->getRenewalTypeLabel() }}
                &middot; {{ $renewal->original_number ?: 'No original number' }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('staff.officer.renewals.production') }}" class="btn btn-white border shadow-sm btn-sm px-3">
                <i class="ri-arrow-left-line me-1"></i>Production Queue
            </a>
            <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
                <i class="ri-refresh-line me-1"></i>Refresh
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Applicant information</h6>
                    <div class="row g-3 small">
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Name</div>
                            <div class="fw-semibold">{{ $renewal->applicant->name ?? '—' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Email</div>
                            <div class="fw-semibold">{{ $renewal->applicant->email ?? '—' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Original number</div>
                            <div class="fw-semibold">{{ $renewal->original_number ?: '—' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Renewal type</div>
                            <div class="fw-semibold">{{ $renewal->getRenewalTypeLabel() }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Payment amount</div>
                            <div class="fw-semibold">
                                {{ $renewal->payment_amount !== null ? number_format((float) $renewal->payment_amount, 2) : '—' }}
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Payment reference</div>
                            <div class="fw-semibold">{{ $renewal->payment_reference ?: '—' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Payment verified</div>
                            <div class="fw-semibold">{{ optional($renewal->payment_verified_at)->format('Y-m-d H:i') ?: '—' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Current status</div>
                            <div class="fw-semibold">{{ $renewal->getStatusLabel() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($renewal->originalApplication)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Original application</h6>
                        <div class="row g-3 small">
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Reference</div>
                                <div class="fw-semibold">{{ $renewal->originalApplication->reference ?: '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Type</div>
                                <div class="fw-semibold">{{ ucfirst($renewal->originalApplication->application_type ?: '—') }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Collection region</div>
                                <div class="fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $renewal->originalApplication->collection_region ?: '—')) }}
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Status</div>
                                <div class="fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $renewal->originalApplication->status ?: '—')) }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('staff.officer.applications.show', $renewal->originalApplication) }}"
                                class="btn btn-white border btn-sm px-3">
                                <i class="ri-external-link-line me-1"></i>Open original application
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if($renewal->has_changes && $renewal->changeRequests->count() > 0)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Changes to apply</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Old value</th>
                                        <th>New value</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($renewal->changeRequests as $change)
                                        <tr>
                                            <td class="fw-semibold">{{ $change->getFieldLabel() }}</td>
                                            <td class="text-muted">{{ $change->old_value ?: '—' }}</td>
                                            <td class="fw-semibold text-success">{{ $change->new_value ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ ucwords(str_replace('_', ' ', $change->status ?: 'pending')) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Production actions</h6>

                    @if($renewal->status === \App\Models\RenewalApplication::RENEWAL_PAYMENT_VERIFIED)
                        <form method="POST" action="{{ route('staff.officer.renewals.production.generate', $renewal) }}"
                            onsubmit="return confirm('Start production for this renewal?');">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-play-line me-1"></i>Start production
                            </button>
                        </form>
                    @endif

                    @if($renewal->status === \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION)
                        <form method="POST" action="{{ route('staff.officer.renewals.production.mark-produced', $renewal) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-1">Collection location</label>
                                <input type="text" name="collection_location" maxlength="255"
                                    class="form-control form-control-sm" value="{{ old('collection_location') }}"
                                    placeholder="e.g. Harare Office" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ri-check-double-line me-1"></i>Mark as produced
                            </button>
                        </form>

                        <button type="button" onclick="printRenewalDocument()" class="btn btn-dark w-100 mt-3">
                            <i class="ri-printer-line me-1"></i>Print document
                        </button>
                    @endif

                    @if($renewal->status === \App\Models\RenewalApplication::RENEWAL_PRODUCED_READY_FOR_COLLECTION)
                        <div class="alert alert-success py-2 mb-3">
                            <div class="fw-bold">Document produced</div>
                            <div class="small">Ready for collection{{ $renewal->collection_location ? ' at ' . $renewal->collection_location : '' }}.</div>
                        </div>

                        <button type="button" onclick="printRenewalDocument()" class="btn btn-dark w-100">
                            <i class="ri-printer-line me-1"></i>Print document
                        </button>
                    @endif

                    @if(!in_array($renewal->status, [
                        \App\Models\RenewalApplication::RENEWAL_PAYMENT_VERIFIED,
                        \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION,
                        \App\Models\RenewalApplication::RENEWAL_PRODUCED_READY_FOR_COLLECTION,
                    ], true))
                        <div class="alert alert-warning py-2 mb-0">
                            This renewal is not yet ready for production. Current status:
                            <strong>{{ $renewal->getStatusLabel() }}</strong>.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Status</h6>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status</span>
                            <span class="fw-semibold">{{ $renewal->getStatusLabel() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Current stage</span>
                            <span class="fw-semibold">{{ ucwords(str_replace('_', ' ', $renewal->current_stage ?: '—')) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Produced</span>
                            <span class="fw-semibold">{{ optional($renewal->produced_at)->format('Y-m-d H:i') ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Print count</span>
                            <span class="fw-semibold">{{ (int) ($renewal->print_count ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Last action</span>
                            <span class="fw-semibold">{{ optional($renewal->last_action_at)->format('Y-m-d H:i') ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function printRenewalDocument() {
        if (!confirm('Log this print action?')) {
            return;
        }

        try {
            const response = await fetch(@json(route('staff.officer.renewals.production.print', $renewal)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.ok) {
                alert('Print logged successfully. Total prints: ' + data.print_count);
                window.print();
            } else {
                alert(data.message || 'Error logging print');
            }
        } catch (error) {
            alert('Error logging print');
        }
    }
</script>
@endpush
@endsection
