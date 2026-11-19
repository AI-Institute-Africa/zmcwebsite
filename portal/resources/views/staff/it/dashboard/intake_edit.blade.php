@extends('layouts.portal')
@section('title', 'Edit Intake Application - IT Admin')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155; padding: 20px;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                Edit Application: {{ $application->reference }}
            </h4>
            <div class="text-muted mt-1" style="font-size:13px;">
                <i class="ri-information-line me-1"></i>
                Update all form fields for this physical intake application.
                @if($formData['intake_officer'] ?? null)
                    <span class="ms-2 badge bg-light text-dark border">Intake by: {{ $formData['intake_officer'] }}</span>
                @endif
                @if($formData['last_edited_by'] ?? null)
                    <span class="ms-2 badge bg-warning-subtle text-warning border">Last edited by: {{ $formData['last_edited_by'] }} on {{ \Carbon\Carbon::parse($formData['last_edited_at'] ?? '')->format('d M Y H:i') }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.intake') }}" class="btn btn-white border shadow-sm btn-sm px-3">
                <i class="ri-arrow-left-line me-1"></i>Back to Intake List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Reference</div>
                <div class="fw-bold" style="font-size:16px; color:#1e293b;">{{ $application->reference }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Type</div>
                <div class="fw-bold" style="font-size:16px; color:#1e293b;">{{ ucfirst($application->application_type) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Request</div>
                <div class="fw-bold" style="font-size:16px; color:#1e293b;">{{ ucfirst($application->request_type) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Status</div>
                <div class="fw-bold" style="font-size:16px; color:#1e293b;">{{ ucwords(str_replace('_', ' ', $application->status)) }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('staff.it.intake.update', $application) }}" enctype="multipart/form-data">
        @csrf

        @if($application->application_type === 'accreditation')
        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-user-line me-2"></i>Applicant Personal Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ $formData['first_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Surname</label>
                        <input type="text" name="surname" class="form-control" value="{{ $formData['surname'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">National ID Number</label>
                        <input type="text" name="national_id" class="form-control zmc-nid-input" value="{{ $formData['national_id'] ?? $formData['id_number'] ?? '' }}" placeholder="XX-XXXXXXXXXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ $formData['date_of_birth'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="male" {{ ($formData['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ ($formData['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nationality</label>
                        <input type="text" name="nationality" class="form-control" value="{{ $formData['nationality'] ?? 'Zimbabwean' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="">-- Select --</option>
                            @php $cat = $formData['category'] ?? $application->accreditation_category_code ?? ''; @endphp
                            <option value="JE" {{ $cat === 'JE' ? 'selected' : '' }}>JE — Local media practitioner employed full-time</option>
                            <option value="JF" {{ $cat === 'JF' ? 'selected' : '' }}>JF — Local journalist free-lancing locally</option>
                            <option value="JO" {{ $cat === 'JO' ? 'selected' : '' }}>JO — Local journalist running office for foreign media</option>
                            <option value="JS" {{ $cat === 'JS' ? 'selected' : '' }}>JS — Local journalist stringing for foreign media</option>
                            <option value="JM" {{ $cat === 'JM' ? 'selected' : '' }}>JM — Local journalist reporting locally and abroad</option>
                            <option value="JP" {{ $cat === 'JP' ? 'selected' : '' }}>JP — Content creation, photography, PR, digital media</option>
                            <option value="JD" {{ $cat === 'JD' ? 'selected' : '' }}>JD — Digital social media practitioner</option>
                            <option value="JT" {{ $cat === 'JT' ? 'selected' : '' }}>JT — Foreign journalist on temporary permit</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Applicant Photo</label>
                        <div class="d-flex align-items-center gap-3">
                            @if($formData['photo_path'] ?? null)
                                <img src="{{ asset('storage/' . $formData['photo_path']) }}" alt="Current Photo" class="rounded" style="width:80px; height:80px; object-fit:cover; border:2px solid #dee2e6;">
                            @endif
                            <div class="flex-grow-1">
                                <input type="file" name="applicant_photo" class="form-control" accept="image/*">
                                <div class="form-text">Upload new photo to replace existing (JPG, PNG - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-white border-bottom rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-building-line me-2"></i>Employment Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Organisation/Media House</label>
                        <input type="text" name="employer_name" class="form-control" value="{{ $formData['employer_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Designation/Position</label>
                        <input type="text" name="designation" class="form-control" value="{{ $formData['designation'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Employment Start Date</label>
                        <input type="date" name="employment_start_date" class="form-control" value="{{ $formData['employment_start_date'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Press Card Number (if any)</label>
                        <input type="text" name="press_card_number" class="form-control" value="{{ $formData['press_card_number'] ?? '' }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Qualifications / Training</label>
                        <textarea name="qualifications" class="form-control" rows="2">{{ $formData['qualifications'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @elseif($application->application_type === 'registration')
        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-primary text-white rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-building-line me-2"></i>Media House Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Entity Name</label>
                        <input type="text" name="entity_name" class="form-control" value="{{ $formData['entity_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Trading Name</label>
                        <input type="text" name="trading_name" class="form-control" value="{{ $formData['trading_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Business Registration Number</label>
                        <input type="text" name="business_registration" class="form-control" value="{{ $formData['business_registration'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tax Number</label>
                        <input type="text" name="tax_number" class="form-control" value="{{ $formData['tax_number'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Business Type</label>
                        <select name="business_type" class="form-select">
                            <option value="">-- Select --</option>
                            @php $bt = $formData['business_type'] ?? ''; @endphp
                            <option value="private_limited" {{ $bt === 'private_limited' ? 'selected' : '' }}>Private Limited</option>
                            <option value="public_limited" {{ $bt === 'public_limited' ? 'selected' : '' }}>Public Limited</option>
                            <option value="partnership" {{ $bt === 'partnership' ? 'selected' : '' }}>Partnership</option>
                            <option value="sole_proprietor" {{ $bt === 'sole_proprietor' ? 'selected' : '' }}>Sole Proprietor</option>
                            <option value="ngo" {{ $bt === 'ngo' ? 'selected' : '' }}>NGO</option>
                            <option value="government" {{ $bt === 'government' ? 'selected' : '' }}>Government</option>
                            <option value="other" {{ $bt === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Ownership Type</label>
                        <select name="ownership_type" class="form-select">
                            <option value="">-- Select --</option>
                            @php $ot = $formData['ownership_type'] ?? ''; @endphp
                            <option value="local" {{ $ot === 'local' ? 'selected' : '' }}>Local</option>
                            <option value="foreign" {{ $ot === 'foreign' ? 'selected' : '' }}>Foreign</option>
                            <option value="mixed" {{ $ot === 'mixed' ? 'selected' : '' }}>Mixed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Local Shareholding (%)</label>
                        <input type="number" name="local_ownership" class="form-control" value="{{ $formData['local_ownership'] ?? '' }}" min="0" max="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Website</label>
                        <input type="url" name="website" class="form-control" value="{{ $formData['website'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Media Category</label>
                        <select name="media_category" class="form-select">
                            <option value="">-- Select --</option>
                            @php $mc = $formData['media_category'] ?? $application->media_house_category_code ?? ''; @endphp
                            <option value="MC" {{ $mc === 'MC' ? 'selected' : '' }}>MC — Community Media</option>
                            <option value="MA" {{ $mc === 'MA' ? 'selected' : '' }}>MA — Advertising agency</option>
                            <option value="MF" {{ $mc === 'MF' ? 'selected' : '' }}>MF — Foreign media office</option>
                            <option value="MN" {{ $mc === 'MN' ? 'selected' : '' }}>MN — Newspaper publisher</option>
                            <option value="DG" {{ $mc === 'DG' ? 'selected' : '' }}>DG — Internet media service</option>
                            <option value="MP" {{ $mc === 'MP' ? 'selected' : '' }}>MP — Production house</option>
                            <option value="MS" {{ $mc === 'MS' ? 'selected' : '' }}>MS — Magazine publisher</option>
                            <option value="MT" {{ $mc === 'MT' ? 'selected' : '' }}>MT — Broadcasting free to air</option>
                            <option value="MB" {{ $mc === 'MB' ? 'selected' : '' }}>MB — Satellite broadcast</option>
                            <option value="MV" {{ $mc === 'MV' ? 'selected' : '' }}>MV — Video on demand</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Publication/Service Name</label>
                        <input type="text" name="publication_name" class="form-control" value="{{ $formData['publication_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Frequency</label>
                        <select name="publication_frequency" class="form-select">
                            <option value="">-- Select --</option>
                            @php $pf = $formData['publication_frequency'] ?? ''; @endphp
                            <option value="daily" {{ $pf === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $pf === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $pf === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ $pf === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="irregular" {{ $pf === 'irregular' ? 'selected' : '' }}>Irregular</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Editor/Manager Name</label>
                        <input type="text" name="editor_name" class="form-control" value="{{ $formData['editor_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Editor Contact</label>
                        <input type="text" name="editor_contact" class="form-control" value="{{ $formData['editor_contact'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Number of Employees</label>
                        <input type="number" name="num_employees" class="form-control" value="{{ $formData['num_employees'] ?? '' }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Year Established</label>
                        <input type="number" name="year_established" class="form-control" value="{{ $formData['year_established'] ?? '' }}" min="1900" max="{{ date('Y') }}">
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-white border-bottom rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-map-pin-line me-2"></i>Contact & Address</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ $formData['email'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="{{ $formData['phone'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Physical Address</label>
                        <input type="text" name="physical_address" class="form-control" value="{{ $formData['physical_address'] ?? '' }}">
                    </div>
                    @if($application->application_type === 'registration')
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Postal Address</label>
                        <input type="text" name="postal_address" class="form-control" value="{{ $formData['postal_address'] ?? '' }}">
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Province</label>
                        <select name="province" class="form-select">
                            <option value="">-- Select --</option>
                            @php $prov = $formData['province'] ?? $application->collection_region ?? ''; @endphp
                            <option value="harare" {{ $prov === 'harare' ? 'selected' : '' }}>Harare</option>
                            <option value="bulawayo" {{ $prov === 'bulawayo' ? 'selected' : '' }}>Bulawayo</option>
                            <option value="manicaland" {{ $prov === 'manicaland' ? 'selected' : '' }}>Manicaland</option>
                            <option value="mashonaland_central" {{ $prov === 'mashonaland_central' ? 'selected' : '' }}>Mashonaland Central</option>
                            <option value="mashonaland_east" {{ $prov === 'mashonaland_east' ? 'selected' : '' }}>Mashonaland East</option>
                            <option value="mashonaland_west" {{ $prov === 'mashonaland_west' ? 'selected' : '' }}>Mashonaland West</option>
                            <option value="masvingo" {{ $prov === 'masvingo' ? 'selected' : '' }}>Masvingo</option>
                            <option value="matabeleland_north" {{ $prov === 'matabeleland_north' ? 'selected' : '' }}>Matabeleland North</option>
                            <option value="matabeleland_south" {{ $prov === 'matabeleland_south' ? 'selected' : '' }}>Matabeleland South</option>
                            <option value="midlands" {{ $prov === 'midlands' ? 'selected' : '' }}>Midlands</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">City/Town</label>
                        <input type="text" name="city" class="form-control" value="{{ $formData['city'] ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-white border-bottom rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-money-dollar-circle-line me-2"></i>Payment & Receipt</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Receipt Number</label>
                        <input type="text" name="receipt_number" class="form-control" value="{{ $application->receipt_number ?? $formData['receipt_number'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">-- Select --</option>
                            @php $pm = $formData['payment_method'] ?? ''; @endphp
                            <option value="cash" {{ $pm === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ $pm === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="ecocash" {{ $pm === 'ecocash' ? 'selected' : '' }}>EcoCash</option>
                            <option value="card" {{ $pm === 'card' ? 'selected' : '' }}>Card</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0 rounded-4">
            <div class="card-header bg-white border-bottom rounded-top-4">
                <h6 class="fw-bold m-0"><i class="ri-sticky-note-line me-2"></i>Additional Notes</h6>
            </div>
            <div class="card-body">
                <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any additional notes or observations about this application...">{{ $formData['admin_notes'] ?? '' }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <a href="{{ route('staff.it.intake') }}" class="btn btn-secondary px-4">
                <i class="ri-arrow-left-line me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="ri-save-line me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
