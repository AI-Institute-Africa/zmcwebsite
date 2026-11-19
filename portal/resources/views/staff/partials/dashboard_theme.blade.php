<style>
  /* Shared staff dashboard visual language, aligned with the practitioner portal. */
  .staff-dashboard-theme .zmc-dashboard-wrapper { max-width: 1480px; margin: 0 auto; }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child {
    position: relative; overflow: hidden; padding: 24px 28px; margin-bottom: 24px !important;
    border-radius: 24px; color: #fff;
    background: linear-gradient(120deg, #0e1b2b 0%, #173b3d 55%, #1f6f57 100%);
    box-shadow: 0 18px 40px rgba(15, 35, 48, .18);
  }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child::after {
    content: ''; position: absolute; width: 220px; height: 220px; right: -55px; top: -95px;
    border-radius: 50%; background: rgba(242, 201, 76, .18); pointer-events: none;
  }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child > * { position: relative; z-index: 1; }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child h4 { color: #fff !important; letter-spacing: -.02em; font-size: clamp(1.75rem, 2.8vw, 2.35rem) !important; line-height: 1.12; }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child .text-muted { color: rgba(255,255,255,.76) !important; }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child .zmc-pill-dark {
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); color: #fff;
  }
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child .btn-white,
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child .btn-outline-primary,
  .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child .btn-outline-secondary {
    background: rgba(255,255,255,.94); border-color: transparent; color: #173b3d;
  }
  .staff-dashboard-theme .zmc-card {
    border: 1px solid #e8edf1; border-radius: 22px !important; overflow: hidden; background: #fff; padding: 20px;
    box-shadow: 0 10px 24px rgba(15, 35, 48, .07); transition: transform .18s ease, box-shadow .18s ease;
  }
  .staff-dashboard-theme .card { border-radius: 22px !important; overflow: hidden; border-color: #e8edf1; }
  .staff-dashboard-theme .zmc-card:hover { transform: translateY(-2px); box-shadow: 0 16px 32px rgba(15, 35, 48, .11); }
  .staff-dashboard-theme .zmc-card .icon-box,
  .staff-dashboard-theme .zmc-card .acct-kpi-icon {
    width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 13px; background: #eef7f2; color: #247b5a; font-size: 20px;
  }
  .staff-dashboard-theme .zmc-card.border-warning .icon-box,
  .staff-dashboard-theme .zmc-card.border-warning .acct-kpi-icon { background: #fff8df; color: #b27b00; }
  .staff-dashboard-theme .zmc-card.border-danger .icon-box,
  .staff-dashboard-theme .zmc-card.border-danger .acct-kpi-icon { background: #fff0ef; color: #c2413b; }
  .staff-dashboard-theme .zmc-card.border-info .icon-box,
  .staff-dashboard-theme .zmc-card.border-info .acct-kpi-icon { background: #edf6ff; color: #2876b8; }
  .staff-dashboard-theme .zmc-card .h3 { font-size: 28px; line-height: 1.1; color: #172b2b; }
  .staff-dashboard-theme .zmc-card .text-muted.small.fw-bold,
  .staff-dashboard-theme .acct-kpi-label { letter-spacing: .04em; text-transform: uppercase; color: #5e7470 !important; }
  .staff-dashboard-theme .h3, .staff-dashboard-theme .h4 { letter-spacing: -.02em; }
  .staff-dashboard-theme .table { --bs-table-bg: transparent; }
  .staff-dashboard-theme .table thead th { background: #f5f8f7; color: #48605d; border-bottom: 1px solid #e4ebe8; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
  .staff-dashboard-theme .table tbody tr { border-color: #edf1f0; }
  .staff-dashboard-theme .table tbody tr:hover { background: #fbfdfc; }
  .staff-dashboard-theme .btn-primary, .staff-dashboard-theme .btn-success { border-radius: 10px; }
  @media (max-width: 768px) {
    .staff-dashboard-theme .zmc-dashboard-wrapper > .d-flex:first-child { padding: 20px; border-radius: 18px; }
  }
</style>
<style>
  /* Baseline applied to every Registrar template, including legacy list views. */
  .zmc-dashboard-wrapper{max-width:1480px;margin:0 auto}
  .zmc-dashboard-wrapper .zmc-card,.zmc-dashboard-wrapper .card{border:1px solid #e5ede9;border-radius:18px!important;box-shadow:0 8px 24px rgba(20,58,46,.06);overflow:hidden}
  .zmc-dashboard-wrapper .table thead th{background:#f4f8f6;color:#50645f;border-bottom:1px solid #e3ebe7;font-size:11px;text-transform:uppercase;letter-spacing:.06em}
  .zmc-dashboard-wrapper .table tbody tr:hover{background:#fbfdfc}
  .zmc-dashboard-wrapper .form-control,.zmc-dashboard-wrapper .form-select{border-radius:10px;border-color:#dce7e1}
  .zmc-dashboard-wrapper .btn{border-radius:10px;font-weight:700}
</style>
<style>
  /* Keep Registrar pages on the same navy / green / gold palette as the other staff dashboards. */
  .registrar-theme .registrar-hero,
  .staff-dashboard-theme .registrar-hero,
  .registrar-theme .payment-hero,
  .registrar-theme .docs-hero{background:linear-gradient(120deg,#0e1b2b 0%,#173b3d 55%,#1f6f57 100%)!important}
  .registrar-theme .btn-success,
  .registrar-theme .docs-tabs .btn-dark{background:var(--zmc-accent)!important;border-color:var(--zmc-accent)!important;color:#18202b!important}
  .registrar-theme .docs-tabs .btn-outline-dark{border-color:var(--zmc-accent-dark);color:#52615f}
  .registrar-theme .payment-kpi .icon{border:1px solid rgba(250,204,21,.25)}
  .registrar-theme .payment-breakdown b{color:var(--zmc-accent-dark)}
  .registrar-theme .docs-summary span{border-color:rgba(250,204,21,.35)}
  .registrar-theme .docs-accordion .doc-count{background:rgba(250,204,21,.16);color:#806400}
  .registrar-theme .docs-accordion .btn-outline-success{color:#176a4f;border-color:#2f8b69}
</style>
<style>
  /* Registrar secondary pages share the same calm, rounded workbench language. */
  .registrar-theme .zmc-dashboard-wrapper{max-width:1480px;margin:0 auto;color:#17352c}
  .registrar-theme .zmc-dashboard-wrapper> .d-flex:first-child{position:relative;overflow:hidden;padding:22px 26px;margin-bottom:22px!important;border-radius:20px;color:#fff;background:linear-gradient(120deg,#123f35,#1f6f57);box-shadow:0 14px 30px rgba(20,83,65,.14)}
  .registrar-theme .zmc-dashboard-wrapper> .d-flex:first-child:after{content:"";position:absolute;width:180px;height:180px;right:-50px;top:-80px;border-radius:50%;background:rgba(242,201,76,.16)}
  .registrar-theme .zmc-dashboard-wrapper> .d-flex:first-child>*{position:relative;z-index:1}
  .registrar-theme .zmc-dashboard-wrapper> .d-flex:first-child h4{color:#fff!important;font-size:clamp(1.45rem,2.3vw,2rem)!important;letter-spacing:-.03em}
  .registrar-theme .zmc-dashboard-wrapper> .d-flex:first-child .text-muted{color:rgba(255,255,255,.76)!important}
  .registrar-theme .zmc-card,.registrar-theme .card{border:1px solid #e5ede9;border-radius:18px!important;box-shadow:0 8px 24px rgba(20,58,46,.06);overflow:hidden}
  .registrar-theme .zmc-card{padding:20px}
  .registrar-theme .zmc-card .h3{font-size:26px;color:#17352c}
  .registrar-theme .table thead th{background:#f4f8f6;color:#50645f;border-bottom:1px solid #e3ebe7;font-size:11px;text-transform:uppercase;letter-spacing:.06em}
  .registrar-theme .table tbody tr:hover{background:#fbfdfc}
  .registrar-theme .form-control,.registrar-theme .form-select{border-radius:10px;border-color:#dce7e1}
  .registrar-theme .btn{border-radius:10px;font-weight:700}
  .registrar-theme .nav-tabs{border-bottom-color:#dfe9e4}
  .registrar-theme .nav-tabs .nav-link{border-radius:10px 10px 0 0;font-weight:700;color:#58706a}
  .registrar-theme .nav-tabs .nav-link.active{color:#176a4f;border-color:#dfe9e4 #dfe9e4 #fff;background:#fff}
</style>
