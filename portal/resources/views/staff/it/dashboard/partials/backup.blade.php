<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-5 h-100 bg-white">
            <div class="text-center mb-4">
                <div class="icon-box bg-primary-subtle text-primary p-4 rounded-circle d-inline-block mb-3">
                    <i class="ri-database-2-line fs-1"></i>
                </div>
                <h5 class="fw-bold m-0">Database Backup</h5>
                <p class="text-slate-600 small fw-bold">Snapshots of the entire system state</p>
            </div>
            
            <div class="p-4 rounded-4 bg-slate-50 border border-slate-100 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-slate-700 fw-bold">Last full backup</span>
                    <span class="small fw-bold text-slate-900">2h 14m ago</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-slate-700 fw-bold">Schedule</span>
                    <span class="badge bg-primary rounded-pill">Daily @ 02:00</span>
                </div>
            </div>

            <div class="d-grid gap-2">
                <form action="{{ route('staff.it.system.backup') }}" method="POST" class="d-grid gap-2">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill py-3 fw-bold shadow-sm">
                        <i class="ri-flashlight-line me-2"></i> Trigger Manual Backup
                    </button>
                </form>
                <button class="btn btn-slate-100 border text-slate-600 rounded-pill py-2 fw-bold small mt-2">
                    <i class="ri-download-cloud-2-line me-2"></i> Export SQL Dump
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-5 h-100 bg-white border-top border-5 border-danger">
            <div class="text-center mb-4">
                <div class="icon-box bg-danger-subtle text-danger p-4 rounded-circle d-inline-block mb-3">
                    <i class="ri-history-line fs-1"></i>
                </div>
                <h5 class="fw-bold m-0">Disaster Recovery</h5>
                <p class="text-slate-600 small fw-bold">Restore system to a known good state</p>
            </div>

            <div class="alert alert-warning border-0 rounded-4 px-4 py-3 mb-4 d-flex align-items-start gap-3">
                <i class="ri-alert-line fs-3 mt-1"></i>
                <div class="small">
                    <strong>CAUTION:</strong> Restoration will overwrite all current data. 
                    This action is irreversible and should only be performed during emergency maintenance.
                </div>
            </div>

            <div class="d-grid gap-2 mt-auto">
                <button class="btn btn-outline-danger rounded-pill py-3 fw-bold border-2">
                    <i class="ri-restart-line me-2"></i> Restore from Last Snapshot
                </button>
                <button class="btn btn-slate-100 border text-slate-600 rounded-pill py-2 fw-bold small mt-2">
                    <i class="ri-folder-zip-line me-2"></i> Export Document Archive
                </button>
            </div>
        </div>
    </div>
</div>
