<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4">
                <h6 class="fw-bold m-0">Environment & System Variables</h6>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="row g-3">
                    @foreach($env as $key => $val)
                    <div class="col-md-6">
                        <div class="p-3 bg-slate-50 rounded-4 border border-slate-100 d-flex justify-content-between align-items-center">
                            <span class="small uppercase fw-bold text-slate-700">{{ str_replace('_',' ', $key) }}</span>
                            <span class="badge {{ $val === true ? 'bg-success' : ($val === false ? 'bg-danger' : 'bg-primary') }} rounded-pill px-3">
                                {{ is_bool($val) ? ($val ? 'ON' : 'OFF') : $val }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 rounded-4 bg-dark text-white shadow-sm position-relative overflow-hidden">
                    <div class="position-absolute" style="top: -10px; right: -10px; opacity: 0.05;">
                        <i class="ri-command-line" style="font-size: 80px;"></i>
                    </div>
                    <h6 class="fw-bold mb-3"><i class="ri-terminal-box-line me-2 text-info"></i> Cache & Optimization</h6>
                    <div class="d-flex gap-2">
                        <form action="{{ route('staff.it.system.clear_cache') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-white-10 text-white btn-sm px-3 rounded-pill fw-bold border-0">Clear App Cache</button>
                        </form>
                        <button class="btn btn-white-10 text-white btn-sm px-3 rounded-pill fw-bold border-0">Re-route Cache</button>
                        <button class="btn btn-white-10 text-white btn-sm px-3 rounded-pill fw-bold border-0">Optimize DB</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-top border-5 border-info">
             <h6 class="fw-bold mb-3">Cron Job Health</h6>
             <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-info-subtle rounded-4">
                 <div class="spinner-grow text-info spinner-grow-sm"></div>
                 <div class="small fw-bold">Scheduler is RUNNING</div>
             </div>
             <div class="list-group list-group-flush small">
                 <div class="list-group-item d-flex justify-content-between border-0 py-2">
                     <span class="text-slate-700 fw-bold">Expiry Monitor</span>
                     <span class="fw-bold">OK (1m ago)</span>
                 </div>
                 <div class="list-group-item d-flex justify-content-between border-0 py-2">
                     <span class="text-slate-700 fw-bold">Notification Queue</span>
                     <span class="fw-bold">Active</span>
                 </div>
             </div>
        </div>
    </div>
</div>
