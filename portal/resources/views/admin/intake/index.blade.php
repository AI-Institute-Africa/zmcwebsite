<!-- Admin Intake Review Dashboard -->

<x-app-layout>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Intake Review Dashboard</h1>
                <p class="text-muted">Applications awaiting intake approval review</p>
            </div>
        </div>

        @if($applications->isEmpty())
            <div class="alert alert-info">
                No applications to review at this time.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Confidence</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $application)
                            <tr>
                                <td><strong>#{{ $application->id }}</strong></td>
                                <td>{{ $application->first_name }} {{ $application->last_name }}</td>
                                <td><span class="badge bg-primary">{{ ucfirst($application->applicant_type) }}</span></td>
                                <td><span class="badge bg-secondary">{{ $application->status }}</span></td>
                                <td>
                                    @php $decision = $application->intakeDecision; @endphp
                                    @if($decision)
                                        {{ $decision->overall_score }}%
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if($decision)
                                        <small>{{ $decision->category_recommendation ?? 'Unassigned' }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.intake.show', $application->id) }}" class="btn btn-sm btn-primary">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
