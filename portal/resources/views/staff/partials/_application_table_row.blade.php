@php
  $rowNo = method_exists($applications, 'firstItem') && $applications->firstItem()
    ? ($applications->firstItem() + $i)
    : ($i + 1);
  $ref = $app->reference ?? ('APP-' . str_pad((int) $app->id, 5, '0', STR_PAD_LEFT));
  $applicantName = $app->applicant?->name ?? '—';
  $collectionRegion = ucfirst($app->collection_region ?? '—');
  $date = !empty($app->created_at) ? \Carbon\Carbon::parse($app->created_at)->format('d M Y') : '—';
  $time = !empty($app->created_at) ? \Carbon\Carbon::parse($app->created_at)->format('H:i') : '—';
  $requestType = ucfirst($app->request_type ?? 'New');
  $scopeRaw = strtolower((string) ($app->journalist_scope ?? $app->residency_type ?? 'local'));
  $scope = ($scopeRaw === 'foreign' || $scopeRaw === 'non-resident') ? 'Foreign' : 'Local';
  $scopeBadge = $scope === 'Foreign' ? 'warning' : 'success';
  $statusLabel = \App\Models\Application::statusLabel($app->status ?? '');
  $rawPaymentStatus = strtolower(trim((string) ($app->payment_status ?? '')));
  $hasPaidRecord = $app->relationLoaded('payments')
    ? $app->payments->contains(fn ($payment) => in_array(strtolower((string) $payment->status), ['paid', 'verified', 'confirmed', 'completed'], true))
    : \App\Models\Payment::where('application_id', $app->id)
        ->whereIn('status', ['paid', 'verified', 'confirmed', 'completed'])
        ->exists();
  // Payment records are authoritative when an older callback left the
  // denormalised application field as "none" or blank.
  $payStatus = ($hasPaidRecord && in_array($rawPaymentStatus, ['', 'none', 'pending', 'submitted'], true))
    ? 'Paid'
    : ucfirst($app->payment_status ?? 'pending');
  // If in production/beyond, it must be paid for display purposes
  if (in_array($app->status, ['production_queue', 'produced_ready', 'card_generated', 'certificate_generated', 'printed', 'issued']) && strtolower($payStatus) === 'pending') {
    $payStatus = 'Paid';
  }
  $payBadge = match ($payStatus) {
    'Paid' => 'success',
    'Pending' => 'warning',
    'Failed' => 'danger',
    default => 'secondary'
  };
  $category = ($app->application_type === 'registration') 
    ? ($app->media_house_category_code ?? '—')
    : ($app->accreditation_category_code ?? '—');
  $officer = $app->assignedOfficer?->name ?? '—';
@endphp
<td class="text-muted small">{{ $rowNo }}</td>
<td class="fw-bold small">{{ $ref }}</td>
<td class="small">{{ $applicantName }}</td>
<td class="text-capitalize small">{{ $collectionRegion }}</td>
<td class="small">{{ $date }}@if($time !== '—') <span class="text-muted ms-1">{{ $time }}</span>@endif</td>
<td class="small">{{ $requestType }}</td>
<td class="small">{{ $scope }}</td>
<td class="small text-muted">{{ $statusLabel }}</td>
<td class="small">{{ $payStatus }}</td>
<td class="small">{{ $category }}</td>
<td class="small text-muted">{{ $officer }}</td>
