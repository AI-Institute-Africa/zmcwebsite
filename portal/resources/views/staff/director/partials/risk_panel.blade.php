<div class="zmc-card bg-white shadow-sm border-0 rounded-4 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-1">Strategic Risk Indicators</h5>
            <div class="text-muted small">Real-time monitoring of operational thresholds</div>
        </div>
        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">
            <i class="ri-error-warning-line me-1"></i> Flagged Items
        </span>
    </div>

    <div class="row g-4">
        {{-- SLA Breakdown --}}
        <div class="col-md-4">
            <div class="p-3 rounded-4 border {{ $riskIndicators['processing_time_sla']['level'] == 'red' ? 'border-danger bg-danger-subtle' : ($riskIndicators['processing_time_sla']['level'] == 'amber' ? 'border-warning bg-warning-subtle' : 'border-success bg-success-subtle') }}">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-box rounded-circle bg-white p-2 me-3 shadow-sm">
                        <i class="ri-timer-flash-line {{ $riskIndicators['processing_time_sla']['level'] == 'red' ? 'text-danger' : ($riskIndicators['processing_time_sla']['level'] == 'amber' ? 'text-warning' : 'text-success') }} h4 mb-0"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-uppercase opacity-75">Processing Time SLA</div>
                        <div class="h4 fw-black mb-0">{{ $riskIndicators['processing_time_sla']['value'] }}</div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px; background: rgba(0,0,0,0.05);">
                    <div class="progress-bar {{ $riskIndicators['processing_time_sla']['level'] == 'red' ? 'bg-danger' : ($riskIndicators['processing_time_sla']['level'] == 'amber' ? 'bg-warning' : 'bg-success') }}" style="width: {{ min(100, (floatval($riskIndicators['processing_time_sla']['value']) / 15) * 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Reprint Frequency --}}
        <div class="col-md-4">
             <div class="p-3 rounded-4 border {{ $riskIndicators['reprint_frequency']['level'] == 'red' ? 'border-danger bg-danger-subtle' : ($riskIndicators['reprint_frequency']['level'] == 'amber' ? 'border-warning bg-warning-subtle' : 'border-success bg-success-subtle') }}">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-box rounded-circle bg-white p-2 me-3 shadow-sm">
                        <i class="ri-printer-error-line {{ $riskIndicators['reprint_frequency']['level'] == 'red' ? 'text-danger' : ($riskIndicators['reprint_frequency']['level'] == 'amber' ? 'text-warning' : 'text-success') }} h4 mb-0"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-uppercase opacity-75">Reprint Frequency</div>
                        <div class="h4 fw-black mb-0">{{ $riskIndicators['reprint_frequency']['value'] }}</div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px; background: rgba(0,0,0,0.05);">
                    <div class="progress-bar {{ $riskIndicators['reprint_frequency']['level'] == 'red' ? 'bg-danger' : ($riskIndicators['reprint_frequency']['level'] == 'amber' ? 'bg-warning' : 'bg-success') }}" style="width: {{ min(100, (floatval($riskIndicators['reprint_frequency']['value']) / 20) * 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Waiver Volume --}}
        <div class="col-md-4">
             <div class="p-3 rounded-4 border {{ $riskIndicators['excessive_waivers']['level'] == 'red' ? 'border-danger bg-danger-subtle' : ($riskIndicators['excessive_waivers']['level'] == 'amber' ? 'border-warning bg-warning-subtle' : 'border-success bg-success-subtle') }}">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-box rounded-circle bg-white p-2 me-3 shadow-sm">
                        <i class="ri-file-reduce-line {{ $riskIndicators['excessive_waivers']['level'] == 'red' ? 'text-danger' : ($riskIndicators['excessive_waivers']['level'] == 'amber' ? 'text-warning' : 'text-success') }} h4 mb-0"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-uppercase opacity-75">Waivers (MTD)</div>
                        <div class="h4 fw-black mb-0">{{ $riskIndicators['excessive_waivers']['value'] }}</div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px; background: rgba(0,0,0,0.05);">
                    <div class="progress-bar {{ $riskIndicators['excessive_waivers']['level'] == 'red' ? 'bg-danger' : ($riskIndicators['excessive_waivers']['level'] == 'amber' ? 'bg-warning' : 'bg-success') }}" style="width: {{ min(100, ($riskIndicators['excessive_waivers']['value'] / 15) * 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
