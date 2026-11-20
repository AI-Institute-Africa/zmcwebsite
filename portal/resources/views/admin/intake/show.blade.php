<!-- Admin Intake Review Detail View -->

<x-app-layout>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Application #{{ $application->id }} - Intake Review</h1>
                <p class="text-muted"><a href="{{ route('admin.intake.index') }}">← Back</a></p>
            </div>
        </div>

        <!-- Applicant Info -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h2>Applicant Information</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $application->first_name }} {{ $application->last_name }}</p>
                        <p><strong>Email:</strong> {{ $application->email }}</p>
                        <p><strong>Type:</strong> {{ ucfirst($application->applicant_type) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> {{ $application->phone }}</p>
                        <p><strong>Employment:</strong> {{ ucfirst($application->employment_status) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Intake Decision -->
        @if($decision = $application->intakeDecision)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h3>Intake Decision</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Overall Score:</strong></p>
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ $decision->overall_score }}%">{{ $decision->overall_score }}%</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Decision:</strong> <span class="badge bg-info">{{ str_replace('_', ' ', $decision->decision) }}</span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Category:</strong> {{ $decision->category_recommendation ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h4>Documents</h4>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Classification</th>
                            <th>Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($application->documents as $doc)
                            @php $class = $doc->classifications()->latest()->first(); @endphp
                            <tr>
                                <td><strong>{{ $doc->doc_type }}</strong></td>
                                <td><span class="badge bg-{{ $class?->recommendation === 'pass' ? 'success' : 'warning' }}">{{ $class?->recommendation ?? 'pending' }}</span></td>
                                <td>{{ $class?->confidence_score }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit Log -->
        <div class="card">
            <div class="card-header bg-light">
                <h5>Audit Trail</h5>
            </div>
            <div class="card-body">
                @forelse($application->intakeAuditLogs()->latest()->get() as $log)
                    <div class="mb-2 pb-2 border-bottom">
                        <small class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}</small>
                        <p><strong>{{ str_replace('_', ' ', $log->action) }}</strong></p>
                    </div>
                @empty
                    <p class="text-muted">No audit logs</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
