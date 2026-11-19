@extends('layouts.portal')

@section('title', 'Apply for Media Practitioner Accreditation')
@section('page_title', 'APPLY FOR MEDIA PRACTITIONER ACCREDITATION')

@section('content')
@unless($formBuilderPreview ?? false)
  @include('portal.partials.existing_documents')
@endunless

<div id="new-application-page">
  <style>
    /* ============================================================
       ZMC Application Form — Modern redesign
       Palette: ink black + commission gold + soft neutral canvas
       ============================================================ */
    #new-application-page{
      --af-ink:#0B0F17;
      --af-ink-2:#161E2B;
      --af-line:#E9EDF3;
      --af-canvas:#F5F7FA;
      --af-muted:#6B7A90;
      --af-gold:#D4A017;
      --af-gold-lt:#F2C94C;
      --af-gold-bg:#FDF7E7;
      --af-gold-line:#F3E3B8;
      --af-radius:18px;
      --af-radius-lg:22px;
      color:#334155;
      font-family:'Roboto',system-ui,-apple-system,'Segoe UI',sans-serif;
    }
    #new-application-page *{box-sizing:border-box}

    /* ---------- Page head ---------- */
    .af-page-head{
      display:flex; justify-content:space-between; align-items:center;
      gap:14px; flex-wrap:wrap; margin-bottom:20px;
    }
    .af-page-head h4{
      margin:0; font-size:20px; font-weight:900; letter-spacing:-.02em;
      color:var(--af-ink);
    }
    .af-page-head .af-crumb{
      font-size:12px; color:var(--af-muted); margin-top:3px;
      display:flex; align-items:center; gap:6px;
    }
    .af-crumb i{color:var(--af-gold)}

    .af-btn{
      display:inline-flex; align-items:center; gap:8px;
      padding:11px 17px; border-radius:13px;
      font-size:13px; font-weight:700; line-height:1;
      text-decoration:none; border:1px solid transparent;
      cursor:pointer; white-space:nowrap;
      transition:transform .18s ease, background .18s ease, box-shadow .18s ease, color .18s ease;
    }
    .af-btn:active{transform:translateY(1px)}
    .af-btn i{font-size:15px}
    .af-btn-soft{background:#fff; border-color:var(--af-line); color:#334155}
    .af-btn-soft:hover{border-color:#CBD5E1; color:var(--af-ink)}
    .af-btn-ink{background:var(--af-ink); color:#fff}
    .af-btn-ink:hover{background:#000; color:#fff}
    .af-btn-gold{
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      color:#171203;
      box-shadow:0 14px 30px -16px rgba(212,160,23,.95);
    }
    .af-btn-gold:hover{filter:brightness(1.05); color:#000}
    .af-btn-outline{background:transparent; border-color:var(--af-line); color:#334155}
    .af-btn-outline:hover{border-color:var(--af-ink); color:var(--af-ink)}
    .af-btn-sm{padding:9px 14px; font-size:12.5px; border-radius:11px}

    /* ---------- Return reasons alert ---------- */
    .af-return{
      position:relative; overflow:hidden;
      background:linear-gradient(180deg,#FFFCF3,#fff);
      border:1px solid var(--af-gold-line);
      border-radius:var(--af-radius);
      padding:20px 24px 20px 28px;
      margin-bottom:22px;
      box-shadow:0 18px 44px -36px rgba(180,120,10,.6);
    }
    .af-return::before{
      content:""; position:absolute; left:0; top:0; bottom:0; width:5px;
      background:linear-gradient(180deg,var(--af-gold-lt),#C9901A);
    }
    .af-return h6{
      margin:0 0 12px; font-size:14px; font-weight:900; color:var(--af-ink);
      display:flex; align-items:center; gap:8px;
    }
    .af-return h6 i{color:#B45309; font-size:17px}
    .af-return .af-reason{
      padding:10px 14px; margin-bottom:8px;
      background:#fff; border:1px solid #F3E3B8; border-radius:12px;
      border-left:3px solid var(--af-gold);
    }
    .af-return .af-reason small{
      font-size:10.5px; font-weight:800; letter-spacing:.06em;
      text-transform:uppercase; color:var(--af-muted);
    }
    .af-return .af-reason p{margin:3px 0 0; font-size:13px; color:#3F4C60; line-height:1.55}
    .af-return .af-note{
      margin:10px 0 0; font-size:12.5px; color:var(--af-muted);
      display:flex; align-items:center; gap:6px;
    }

    /* ---------- Form shell ---------- */
    .af-shell{
      background:#fff;
      border:1px solid var(--af-line);
      border-radius:var(--af-radius-lg);
      box-shadow:0 1px 2px rgba(16,24,40,.04), 0 30px 70px -50px rgba(16,24,40,.55);
      overflow:hidden;
    }

    /* Let Step 2 grow with its fields so the document remains the only form scroller. */
    #new-application-page .af-shell,
    #new-application-page .af-body,
    #new-application-page #ap3Form,
    #new-application-page #ap3-step-2{
      height:auto !important;
      max-height:none !important;
    }
    #new-application-page .af-body,
    #new-application-page #ap3Form,
    #new-application-page #ap3-step-2{
      overflow:visible !important;
    }

    /* ---------- Hero inside form ---------- */
    .af-hero{
      position:relative; overflow:hidden;
      padding:30px 32px 26px;
      color:#fff;
      background:
        radial-gradient(120% 180% at 100% 0%, #26313F 0%, #0B0F17 60%),
        #0B0F17;
    }
    .af-hero::before{
      content:""; position:absolute; inset:0;
      background:
        radial-gradient(50% 90% at 88% 8%, rgba(212,160,23,.42), transparent 62%),
        radial-gradient(40% 80% at 4% 108%, rgba(56,189,248,.13), transparent 68%);
      pointer-events:none;
    }
    .af-hero-inner{position:relative; z-index:2}
    .af-eyebrow{
      display:inline-flex; align-items:center; gap:7px;
      font-size:10.5px; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
      padding:5px 11px; border-radius:999px;
      color:#F6DC93;
      background:rgba(212,160,23,.14);
      border:1px solid rgba(212,160,23,.32);
    }
    .af-hero h1{
      margin:14px 0 6px; font-size:clamp(20px,2.2vw,26px);
      font-weight:900; letter-spacing:-.02em; line-height:1.15;
    }
    .af-hero p{
      margin:0; font-size:13px; line-height:1.6;
      color:rgba(255,255,255,.68); max-width:60ch;
    }

    /* ---------- Stepper ---------- */
    .af-stepper{
      padding:24px 32px 4px;
      background:linear-gradient(180deg,#FAFBFD,#fff);
      border-bottom:1px solid var(--af-line);
    }
    .af-steps{
      display:flex; align-items:flex-start;
      gap:6px; position:relative;
      padding-bottom:20px;
    }
    .af-steps::before{
      content:""; position:absolute;
      left:22px; right:22px; top:18px; height:2px;
      background:#E6EAF1; border-radius:2px;
    }
    .af-step{
      flex:1 1 0; position:relative; z-index:1;
      display:flex; flex-direction:column; align-items:center;
      gap:8px; cursor:pointer; text-align:center;
      transition:.2s;
    }
    .af-step .af-step-dot{
      width:38px; height:38px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      background:#fff; border:2px solid #E6EAF1;
      font-size:13px; font-weight:900; color:#94A3B8;
      transition:.25s;
      box-shadow:0 2px 6px rgba(16,24,40,.06);
    }
    .af-step .af-step-label{
      font-size:11.5px; font-weight:800; letter-spacing:.03em;
      color:#94A3B8; transition:.2s;
      white-space:nowrap;
    }
    .af-step:hover .af-step-dot{border-color:#CBD5E1; color:#64748B}
    .af-step.active .af-step-dot{
      background:var(--af-ink); border-color:var(--af-ink); color:#fff;
      box-shadow:0 0 0 5px rgba(212,160,23,.20), 0 8px 20px -10px rgba(11,15,23,.75);
    }
    .af-step.active .af-step-label{color:var(--af-ink)}
    .af-step.completed .af-step-dot{
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      border-color:var(--af-gold); color:#171203;
    }
    .af-step.completed .af-step-label{color:#9A7208}

    /* ---------- Step content ---------- */
    .af-body{padding:30px 32px 12px}
    .af-step-content{display:none; animation:afFade .35s ease}
    .af-step-content.active{display:block}
    @keyframes afFade{
      from{opacity:0; transform:translateY(6px)}
      to{opacity:1; transform:translateY(0)}
    }

    .af-step-title{
      font-size:14px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;
      color:var(--af-ink); margin:0 0 6px;
      display:flex; align-items:center; gap:10px;
    }
    .af-step-title::before{
      content:""; width:4px; height:18px; border-radius:3px;
      background:linear-gradient(180deg,var(--af-gold-lt),var(--af-gold));
    }
    .af-info{
      display:flex; align-items:flex-start; gap:9px;
      padding:12px 15px; margin-bottom:24px;
      background:#F4F8FE; border:1px solid #DDE9FB; border-radius:13px;
      font-size:12.5px; color:#42506A; line-height:1.55;
    }
    .af-info i{color:#2563EB; font-size:16px; margin-top:1px; flex:0 0 16px}

    /* ---------- Form grid ---------- */
    .af-row{
      display:grid; grid-template-columns:1fr 1fr;
      gap:16px; margin-bottom:16px;
    }
    .af-row.single{grid-template-columns:1fr}
    .af-row.three{grid-template-columns:1fr 1fr 1fr}
    .af-field{display:flex; flex-direction:column; min-width:0}

    .af-lbl{
      font-size:11px; font-weight:800; letter-spacing:.06em;
      text-transform:uppercase; color:#64748B;
      margin-bottom:7px;
      display:flex; align-items:center; gap:4px;
    }
    .af-lbl.required::after{
      content:"*"; color:var(--af-gold); font-weight:900; font-size:13px;
      line-height:1;
    }

    #new-application-page .form-control,
    #new-application-page select.form-control,
    #new-application-page textarea.form-control{
      width:100%;
      padding:11px 14px;
      font-size:13.5px; color:var(--af-ink);
      background:#FAFBFD;
      border:1px solid var(--af-line);
      border-radius:12px;
      transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
      font-family:inherit;
      line-height:1.4;
    }
    #new-application-page .form-control:hover{border-color:#D4DBE5}
    #new-application-page .form-control:focus{
      outline:0; background:#fff;
      border-color:var(--af-gold);
      box-shadow:0 0 0 4px rgba(212,160,23,.15);
    }
    #new-application-page textarea.form-control{resize:vertical; min-height:88px}
    #new-application-page select.form-control{
      appearance:none;
      background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236B7A90' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
      background-repeat:no-repeat;
      background-position:right 14px center;
      padding-right:38px;
    }
    #new-application-page .form-hint{
      margin-top:6px; font-size:11.5px; color:var(--af-muted);
      display:flex; align-items:flex-start; gap:6px; line-height:1.5;
    }
    #new-application-page .form-hint i{color:var(--af-gold); font-size:14px; margin-top:1px; flex:0 0 14px}

    /* ---------- Checkbox / radio ---------- */
    .af-checks{display:flex; flex-wrap:wrap; gap:10px}
    .af-check{
      display:inline-flex; align-items:center; gap:9px;
      padding:10px 14px;
      background:#FAFBFD; border:1px solid var(--af-line);
      border-radius:12px;
      font-size:13px; font-weight:600; color:#3F4C60;
      cursor:pointer; transition:.18s;
      user-select:none;
    }
    .af-check:hover{border-color:#CBD5E1; background:#fff}
    .af-check input{
      appearance:none; width:17px; height:17px; margin:0;
      border:2px solid #CBD5E1; border-radius:50%;
      position:relative; cursor:pointer; flex:0 0 17px;
      transition:.18s;
    }
    .af-check input[type="checkbox"]{border-radius:5px}
    .af-check input:checked{
      border-color:var(--af-gold);
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
    }
    .af-check input:checked::after{
      content:""; position:absolute; inset:0;
      background:
        url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23171203' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>")
        center / 11px no-repeat;
    }
    .af-check input[type="radio"]:checked::after{
      background:
        url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><circle cx='12' cy='12' r='5' fill='%23171203'/></svg>")
        center / 9px no-repeat;
    }
    .af-check:has(input:checked){
      border-color:var(--af-gold); background:var(--af-gold-bg); color:var(--af-ink);
    }

    /* ---------- Applicant type cards ---------- */
    .af-type-grid{
      display:grid; grid-template-columns:1fr 1fr; gap:16px;
      margin-bottom:8px;
    }
    .af-type-card{
      position:relative; overflow:hidden;
      padding:26px 24px 22px;
      background:#fff; border:2px solid var(--af-line);
      border-radius:var(--af-radius-lg);
      cursor:pointer;
      transition:transform .25s ease, border-color .25s ease, box-shadow .25s ease, background .25s ease;
    }
    .af-type-card:hover{
      transform:translateY(-3px);
      border-color:#CBD5E1;
      box-shadow:0 22px 50px -34px rgba(16,24,40,.55);
    }
    .af-type-card::after{
      content:""; position:absolute; right:-40px; top:-40px;
      width:130px; height:130px; border-radius:50%;
      background:radial-gradient(circle, rgba(212,160,23,.08), transparent 70%);
      pointer-events:none;
    }
    .af-type-icon{
      width:54px; height:54px; border-radius:16px;
      display:flex; align-items:center; justify-content:center;
      background:#F4F6FA; color:#475569; font-size:26px;
      margin-bottom:16px;
      transition:.25s;
    }
    .af-type-card h4{
      margin:0 0 6px; font-size:16px; font-weight:900;
      color:var(--af-ink); letter-spacing:-.01em;
    }
    .af-type-card p{
      margin:0 0 16px; font-size:12.5px; color:var(--af-muted); line-height:1.55;
    }
    .af-type-tag{
      display:inline-flex; align-items:center; gap:6px;
      font-size:11px; font-weight:800; letter-spacing:.05em;
      padding:5px 11px; border-radius:999px;
      background:#F4F6FA; border:1px solid var(--af-line); color:#42506A;
    }
    .af-type-card.selected{
      border-color:var(--af-ink);
      background:linear-gradient(180deg,#fff 0%, #FAFBFD 100%);
      box-shadow:0 0 0 5px rgba(212,160,23,.16), 0 26px 54px -34px rgba(11,15,23,.65);
    }
    .af-type-card.selected .af-type-icon{
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      color:#171203;
      box-shadow:0 12px 26px -14px rgba(212,160,23,.95);
    }
    .af-type-card.selected h4{color:var(--af-ink)}
    .af-type-card.selected .af-type-tag{
      background:var(--af-gold-bg); border-color:var(--af-gold-line); color:#8A6205;
    }
    .af-type-card .af-checkmark{
      position:absolute; top:16px; right:16px;
      width:28px; height:28px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      color:#171203; font-size:14px;
      opacity:0; transform:scale(.6);
      transition:.22s;
    }
    .af-type-card.selected .af-checkmark{opacity:1; transform:scale(1)}

    /* ---------- Upload areas ---------- */
    .af-upload{
      border:2px dashed #D4DBE5;
      border-radius:16px;
      padding:26px 20px;
      text-align:center;
      background:#FAFBFD;
      transition:.22s;
      cursor:pointer;
    }
    .af-upload:hover{
      border-color:var(--af-gold);
      background:var(--af-gold-bg);
    }
    .af-upload:hover .af-upload-icon{
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      color:#171203;
      transform:translateY(-2px);
    }
    .af-upload-icon{
      width:52px; height:52px; margin:0 auto 12px;
      border-radius:16px;
      display:flex; align-items:center; justify-content:center;
      background:#fff; color:var(--af-ink);
      font-size:24px;
      border:1px solid var(--af-line);
      transition:.25s;
    }
    .af-upload h5{
      margin:0 0 4px; font-size:14px; font-weight:800; color:var(--af-ink);
    }
    .af-upload p{
      margin:0 0 14px; font-size:11.5px; color:var(--af-muted);
    }
    .af-upload .af-btn{pointer-events:auto}

    .uploaded-files,
    .uploaded-files-list{margin-top:12px}
    .uploaded-file{
      display:flex; align-items:center; justify-content:space-between;
      gap:10px; padding:11px 14px;
      border:1px solid var(--af-line); border-radius:12px;
      background:#fff; margin-bottom:8px;
      box-shadow:0 8px 22px -22px rgba(16,24,40,.5);
    }
    .uploaded-file .file-icon{color:var(--af-gold); font-size:18px}

    .existing-doc-item{
      display:flex; align-items:center; justify-content:space-between;
      gap:10px; padding:11px 14px;
      border:1px solid var(--af-line); border-radius:12px;
      background:#fff; margin-top:8px;
    }
    .existing-doc-item .doc-info{display:flex; align-items:center; gap:10px; min-width:0}
    .existing-doc-item .doc-info i{color:var(--af-gold); font-size:20px}
    .existing-doc-item .doc-name{font-size:13px; font-weight:700; color:var(--af-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap}
    .existing-doc-item .doc-meta{font-size:11px; color:var(--af-muted)}
    .existing-doc-item .btn-remove-doc{
      background:transparent; border:1px solid #F8D7DA; color:#B42318;
      padding:6px 11px; border-radius:9px; font-size:11.5px; font-weight:800;
      display:inline-flex; align-items:center; gap:5px; cursor:pointer;
      transition:.18s;
    }
    .existing-doc-item .btn-remove-doc:hover{background:#FDEDEE}

    /* ---------- Referee / link rows ---------- */
    .af-dyn-row{
      display:flex; gap:10px; margin-bottom:10px;
      padding:12px 14px;
      background:#FAFBFD; border:1px solid var(--af-line);
      border-radius:13px;
    }
    .af-dyn-row .form-control{background:#fff}
    .af-row-remove{
      flex:0 0 auto;
      width:38px; height:38px; padding:0;
      display:inline-flex; align-items:center; justify-content:center;
      background:#fff; border:1px solid #F8D7DA; color:#B42318;
      border-radius:11px; font-size:15px; cursor:pointer;
      transition:.18s;
    }
    .af-row-remove:hover{background:#FDEDEE}

    /* ---------- Section separator headings ---------- */
    .af-subhead{
      margin:28px 0 14px; padding-bottom:10px;
      border-bottom:1px solid var(--af-line);
      font-size:12px; font-weight:900; letter-spacing:.09em; text-transform:uppercase;
      color:var(--af-ink);
      display:flex; align-items:center; gap:9px;
    }
    .af-subhead i{color:var(--af-gold); font-size:15px}

    /* ---------- Declaration ---------- */
    .af-declaration{
      padding:18px 20px; border-radius:14px;
      background:linear-gradient(180deg,#FAFBFD,#fff);
      border:1px solid var(--af-line);
      font-size:13px; line-height:1.65; color:#3F4C60;
    }
    .af-declaration .af-time{
      margin-top:10px; font-size:11.5px; color:var(--af-muted);
      display:flex; align-items:center; gap:6px;
    }

    /* ---------- Footer buttons ---------- */
    .af-form-foot{
      display:flex; justify-content:space-between; align-items:center;
      gap:12px; flex-wrap:wrap;
      padding:20px 32px 26px;
      border-top:1px solid var(--af-line);
      background:linear-gradient(180deg,#fff,#FAFBFD);
      margin-top:22px;
    }
    .af-form-foot .af-foot-left{display:flex; gap:10px; flex-wrap:wrap}

    /* ---------- Modals ---------- */
    .af-modal .modal-content{
      border:0; border-radius:22px; overflow:hidden;
      box-shadow:0 40px 100px -45px rgba(11,15,23,.75);
    }
    .af-modal .modal-header{
      background:
        radial-gradient(90% 200% at 100% 0%, rgba(212,160,23,.35), transparent 60%),
        #0B0F17;
      color:#fff; border:0;
      padding:20px 24px;
    }
    .af-modal .modal-title{
      font-size:15px; font-weight:900; color:#fff; letter-spacing:-.01em;
      display:flex; align-items:center; gap:9px;
    }
    .af-modal .modal-title i{color:var(--af-gold-lt) !important}
    .af-modal .btn-close{filter:invert(1) grayscale(1) brightness(2); opacity:.7}
    .af-modal .btn-close:hover{opacity:1}
    .af-modal .modal-body{padding:26px 26px 20px}
    .af-modal .modal-footer{
      border-top:1px solid var(--af-line); padding:14px 22px; background:#FAFBFD;
    }
    .af-modal .modal-footer .btn{
      padding:11px 20px; border-radius:12px; font-weight:800; font-size:13px;
    }
    .af-modal .modal-footer .btn-primary{
      background:var(--af-ink); border-color:var(--af-ink); color:#fff;
    }
    .af-modal .modal-footer .btn-primary:hover{background:#000; color:#fff}
    .af-modal .modal-footer .btn-success{
      background:linear-gradient(135deg,var(--af-gold-lt),var(--af-gold));
      border-color:var(--af-gold); color:#171203;
      box-shadow:0 14px 30px -16px rgba(212,160,23,.95);
    }
    .af-modal .modal-footer .btn-success:hover{color:#000; filter:brightness(1.05)}
    .af-modal .modal-footer .btn-secondary{
      background:#fff; border-color:var(--af-line); color:#334155;
    }
    .af-modal .modal-footer .btn-secondary:hover{border-color:#CBD5E1; color:var(--af-ink)}

    /* Review modal contents */
    #ap3ReviewContent .review-section h6{
      font-size:11.5px; letter-spacing:.09em; text-transform:uppercase;
      color:var(--af-ink);
    }
    #ap3ReviewContent .review-section h6 i{color:var(--af-gold)}
    #ap3ReviewContent p{margin-bottom:7px; font-size:13px; line-height:1.55}
    #ap3ReviewContent p strong{color:#42506A; font-weight:800}
    #ap3ReviewContent ul{padding-left:18px; font-size:13px}
    #ap3ReviewContent .alert{border-radius:13px}
    #ap3ReviewContent .alert-warning{
      background:#FFFCF3; border-color:var(--af-gold-line); color:#4B5563;
    }
    #ap3ReviewContent .alert-warning i{color:#B45309}
    #ap3ReviewContent .list-group-item{
      border-color:var(--af-line); border-radius:12px !important;
      margin-bottom:8px; padding:12px 14px;
    }

    /* Payment modal */
    #ap3PaymentModal .nav-pills{
      gap:6px; padding:5px;
      background:#F4F6FA; border:1px solid var(--af-line);
      border-radius:14px; display:inline-flex;
    }
    #ap3PaymentModal .nav-pills .nav-link{
      border:0; border-radius:10px;
      padding:9px 16px; font-size:12.5px; font-weight:800;
      color:var(--af-muted); background:transparent;
      transition:.18s;
    }
    #ap3PaymentModal .nav-pills .nav-link.active{
      background:#fff; color:var(--af-ink);
      box-shadow:0 1px 2px rgba(16,24,40,.06), 0 8px 20px -14px rgba(16,24,40,.5);
    }
    #ap3PaymentModal .alert-success{
      background:#E7F7EF; border-color:#CFEFDF; color:#065F46;
      border-radius:14px;
    }
    #ap3PaymentModal .alert-info{
      background:#EAF2FE; border-color:#D6E5FD; color:#1E40AF;
      border-radius:14px;
    }
    #ap3PaymentModal .currency-option{
      display:block; padding:16px;
      background:#fff; border:2px solid var(--af-line);
      border-radius:16px; cursor:pointer;
      transition:.18s; position:relative;
    }
    #ap3PaymentModal .currency-option:hover{border-color:#CBD5E1}
    #ap3PaymentModal .currency-option.selected{
      border-color:var(--af-gold);
      background:var(--af-gold-bg);
      box-shadow:0 0 0 4px rgba(212,160,23,.15);
    }
    #ap3PaymentModal .currency-option input{position:absolute; opacity:0; pointer-events:none}
    #ap3PaymentModal .currency-option i{color:var(--af-gold); font-size:22px; display:block; margin-bottom:6px}

    /* Camera modal */
    #ap3CameraModal .modal-content{border-radius:22px; overflow:hidden}
    #ap3CameraModal .modal-header{
      background:#0B0F17; color:#fff; border:0; padding:16px 20px;
    }
    #ap3CameraModal .modal-title{font-size:14px; font-weight:900; color:#fff}
    #ap3CameraModal .btn-close{filter:invert(1) grayscale(1) brightness(2)}
    #ap3CameraModal video,
    #ap3CameraModal #ap3PreviewImg{
      border-radius:14px; border:1px solid var(--af-line);
    }

    /* Toast */
    #ap3-toast-container .alert{
      border-radius:14px; border:0;
      box-shadow:0 20px 46px -20px rgba(11,15,23,.55);
      font-size:13px; font-weight:700;
      padding:13px 18px;
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 767.98px){
      .af-hero{padding:24px 22px}
      .af-stepper{padding:20px 18px 4px}
      .af-body{padding:22px 20px 8px}
      .af-form-foot{padding:16px 20px 20px}
      .af-row,
      .af-row.three{grid-template-columns:1fr}
      .af-type-grid{grid-template-columns:1fr}
      .af-step .af-step-label{font-size:10px}
      .af-step .af-step-dot{width:32px; height:32px; font-size:12px}
      .af-steps::before{top:15px}
    }
  </style>

  {{-- ============================ PAGE HEAD ============================ --}}
  <div class="af-page-head">
    <div>
      <h4>Apply for Media Practitioner Accreditation</h4>
      <div class="af-crumb">
        <i class="ri-map-pin-2-line"></i> Portal <span class="text-muted">/</span> Accreditation <span class="text-muted">/</span> New Application
      </div>
    </div>
    <a class="af-btn af-btn-soft" href="{{ route('accreditation.home') }}">
      <i class="ri-arrow-left-line"></i> Back to Tracker
    </a>
  </div>

  {{-- ====================== RETURN REASONS ====================== --}}
      @if((isset($returnReasons) && $returnReasons->isNotEmpty()) || filled($draft?->correction_notes) || filled($draft?->proof_review_notes))
    <div class="af-return">
      <h6><i class="ri-error-warning-line"></i> Your application was returned for the following reason(s)</h6>
        @foreach($returnReasons ?? collect() as $reason)
        <div class="af-reason">
          <small>{{ $reason->created_at->format('d M Y H:i') }} &mdash; {{ ucfirst(str_replace('_', ' ', $reason->comment_type)) }}</small>
          <p>{{ $reason->body }}</p>
        </div>
      @endforeach
        @foreach(['Correction requested' => $draft?->correction_notes, 'Payment proof feedback' => $draft?->proof_review_notes] as $reasonTitle => $reasonBody)
          @if(filled($reasonBody) && collect($returnReasons ?? [])->doesntContain(fn ($reason) => trim((string) $reason->body) === trim((string) $reasonBody)))
            <div class="alert alert-warning py-2 mb-2"><strong>{{ $reasonTitle }}:</strong> {{ $reasonBody }}</div>
          @endif
        @endforeach
      <p class="af-note"><i class="ri-information-line"></i> Please address the issues above before resubmitting your application.</p>
    </div>
  @endif

  {{-- ============================ FORM SHELL ============================ --}}
  <div class="af-shell">

    {{-- Hero --}}
    <div class="af-hero">
      <div class="af-hero-inner">
        <span class="af-eyebrow"><i class="ri-shield-star-line"></i> Accreditation Application</span>
        <h1>Media Practitioner Accreditation</h1>
        <p>{{ config('legislation.registration_regulations') }}</p>
      </div>
    </div>

    {{-- Stepper --}}
    <div class="af-stepper">
      <div class="af-steps">
        <div class="af-step active" data-step="1">
          <div class="af-step-dot">1</div>
          <div class="af-step-label">Applicant Type</div>
        </div>
        <div class="af-step" data-step="2">
          <div class="af-step-dot">2</div>
          <div class="af-step-label">Personal Details</div>
        </div>
        <div class="af-step" data-step="3">
          <div class="af-step-dot">3</div>
          <div class="af-step-label">Employment</div>
        </div>
        <div class="af-step" data-step="4">
          <div class="af-step-dot">4</div>
          <div class="af-step-label">Past Work</div>
        </div>
        <div class="af-step" data-step="5">
          <div class="af-step-dot">5</div>
          <div class="af-step-label">Uploads &amp; Declaration</div>
        </div>
      </div>
    </div>

    <form id="ap3Form" onsubmit="return false;">
      {{-- Hidden scope --}}
      <input type="hidden" name="journalist_scope" id="ap3_scope" required>

      <div class="af-body">

        {{-- ===================== STEP 1: TYPE ===================== --}}
        <div class="step-content af-step-content active" id="ap3-step-1">
          <h3 class="af-step-title">Select Applicant Type</h3>
          <div class="af-info">
            <i class="ri-information-line"></i>
            <div>Select whether you are a Local or Foreign media practitioner. Different requirements apply.</div>
          </div>

          <div class="af-type-grid">
            <div class="app-type-card af-type-card" data-type="local">
              <div class="af-checkmark"><i class="ri-check-line"></i></div>
              <div class="af-type-icon"><i class="ri-user-3-line"></i></div>
              <h4>Local Media Practitioner</h4>
              <p>Zimbabwean citizens or residents applying for accreditation.</p>
              <span class="af-type-tag"><i class="ri-id-card-line"></i> National ID Required</span>
            </div>

            <div class="app-type-card af-type-card" data-type="foreign">
              <div class="af-checkmark"><i class="ri-check-line"></i></div>
              <div class="af-type-icon"><i class="ri-global-line"></i></div>
              <h4>Foreign Media Practitioner</h4>
              <p>International media practitioners seeking temporary accreditation.</p>
              <span class="af-type-tag"><i class="ri-passport-line"></i> Passport Required</span>
            </div>
          </div>
        </div>

        {{-- ===================== STEP 2: PERSONAL DETAILS ===================== --}}
        <div class="step-content af-step-content" id="ap3-step-2">
          <h3 class="af-step-title">Personal Details</h3>
          <div class="af-info">
            <i class="ri-information-line"></i>
            <div>Please provide your personal information as it appears on your official documents.</div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Title</label>
              <select class="form-control" name="title" required>
                <option value="">Select</option>
                <option value="Prof">Prof</option>
                <option value="Dr">Dr</option>
                <option value="Mr">Mr</option>
                <option value="Mrs">Mrs</option>
                <option value="Ms">Ms</option>
                <option value="Miss">Miss</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="af-field">
              <label class="af-lbl required">Surname</label>
              <input type="text" class="form-control" name="surname" value="{{ old('surname', ($draft?->form_data['surname'] ?? Auth::user()->profile_data['surname'] ?? Auth::user()->profile_data['last_name'] ?? '')) }}" required>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Name</label>
              <input type="text" class="form-control" name="first_name" value="{{ old('first_name', ($draft?->form_data['first_name'] ?? Auth::user()->profile_data['first_name'] ?? '')) }}" required>
            </div>
            <div class="af-field">
              <label class="af-lbl">Other Names</label>
              <input type="text" class="form-control" name="other_names">
            </div>
          </div>

          <div class="af-row single">
            <div class="af-field">
              <label class="af-lbl required">Date of Birth</label>
              <input type="date" class="form-control" name="dob" required>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Sex</label>
              <div class="af-checks">
                <label class="af-check">
                  <input type="radio" name="gender" value="male" required>
                  <span>Male</span>
                </label>
                <label class="af-check">
                  <input type="radio" name="gender" value="female" required>
                  <span>Female</span>
                </label>
              </div>
            </div>
            <div class="af-field">
              <label class="af-lbl required">Marital Status</label>
              <select class="form-control" name="marital_status" required>
                <option value="">Select</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Divorced">Divorced</option>
                <option value="Widowed">Widowed</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field scope-local">
              <label class="af-lbl required">National Reg. No</label>
              <input type="text" class="form-control" name="national_reg_no" placeholder="63-1234567X50" data-req="1">
            </div>

            <div class="af-field scope-foreign">
              <label class="af-lbl required">Passport No</label>
              <input type="text" class="form-control" name="passport_no" placeholder="Passport number" data-req="1">
            </div>

            <div class="af-field">
              @include('portal.partials.country_selector', [
                'selectorId' => 'ap3_nationality',
                'fieldName' => 'nationality',
                'fieldLabel' => 'Nationality',
                'isRequired' => true,
                'currentValue' => ($draft?->form_data['nationality'] ?? Auth::user()->profile_data['nationality'] ?? '')
              ])
            </div>
          </div>

          <div class="af-row scope-foreign">
            <div class="af-field">
              <label class="af-lbl required">Date of Expiry</label>
              <input type="date" class="form-control" name="passport_expiry" data-req="1">
            </div>
            <div class="af-field">
              <label class="af-lbl required">Issued at</label>
              <input type="text" class="form-control" name="passport_issued_at" placeholder="City / Country" data-req="1">
            </div>
          </div>

          <div class="af-row single">
            <div class="af-field">
              <label class="af-lbl required">Residential Address</label>
              <textarea class="form-control" rows="3" name="address" required></textarea>
            </div>
          </div>

          <div class="af-row scope-local">
            <div class="af-field">
              <label class="af-lbl required" for="applicant-province">Province</label>
              <select class="form-control" name="province" id="applicant-province" data-req="1">
                <option value="">Select your province</option>
                @foreach(['Harare','Bulawayo','Manicaland','Mashonaland Central','Mashonaland East','Mashonaland West','Masvingo','Matabeleland North','Matabeleland South','Midlands'] as $province)
                  <option value="{{ \Illuminate\Support\Str::snake($province) }}" @selected(old('province', $draft?->form_data['province'] ?? Auth::user()->region ?? '') === \Illuminate\Support\Str::snake($province))>{{ $province }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="scope-foreign">
            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl required">Is this your first time in Zimbabwe?</label>
                <div class="af-checks">
                  <label class="af-check">
                    <input type="radio" name="first_time_in_zim" value="yes" data-req="1">
                    <span>Yes</span>
                  </label>
                  <label class="af-check">
                    <input type="radio" name="first_time_in_zim" value="no" data-req="1">
                    <span>No</span>
                  </label>
                </div>
              </div>
              <div class="af-field">
                <label class="af-lbl">If No, indicate when you were last here</label>
                <input type="text" class="form-control" name="last_in_zim_when" placeholder="e.g. June {{ now()->year }}">
              </div>
            </div>

            <div class="af-row single">
              <div class="af-field">
                <label class="af-lbl required">Address in Zimbabwe</label>
                <input type="text" class="form-control" name="address_in_zimbabwe" data-req="1">
              </div>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Phone No.</label>
              <div class="d-flex gap-2">
                <select class="form-control zmc-phone-code-select" name="phone_country_code" data-default-value="{{ old('phone_country_code', ($draft?->form_data['phone_country_code'] ?? Auth::user()->phone_country_code ?? '')) }}" style="max-width:220px;" required>
                  <option value="">Dial Code</option>
                </select>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', ($draft?->form_data['phone'] ?? Auth::user()->phone_number ?? '')) }}" placeholder="e.g. 771234567" required>
              </div>
            </div>
            <div class="af-field">
              <label class="af-lbl required">Email</label>
              <input type="email" class="form-control" name="email" value="{{ old('email', ($draft?->form_data['email'] ?? Auth::user()->email ?? '')) }}" readonly style="background:#F1F5F9; cursor:not-allowed;" required>
            </div>
          </div>
        </div>

        {{-- ===================== STEP 3: EMPLOYMENT + REFERENCES ===================== --}}
        <div class="step-content af-step-content" id="ap3-step-3">
          <h3 class="af-step-title">Employment &amp; References</h3>
          <div class="af-info">
            <i class="ri-information-line"></i>
            <div>Please provide your employment details. Three references are required.</div>
          </div>

          <div class="af-subhead"><i class="ri-briefcase-line"></i> Employment</div>

          <div class="af-row single">
            <div class="af-field">
              <label class="af-lbl required">Applicant Type</label>
              <div class="af-checks">
                <label class="af-check">
                  <input type="radio" name="employment_type" value="employed" required>
                  <span>Employed</span>
                </label>
                <label class="af-check">
                  <input type="radio" name="employment_type" value="freelancer" required>
                  <span>Freelancer</span>
                </label>
              </div>
              <div class="form-hint"><i class="ri-eye-line"></i> If Freelancer, employment fields and employment letter will be hidden.</div>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Type of Medium</label>
              <select class="form-control" name="medium_type" required>
                <option value="">Select</option>
                <option value="News Agency">News Agency</option>
                <option value="Newspaper">Newspaper</option>
                <option value="Television">Television</option>
                <option value="Radio">Radio</option>
                <option value="Magazine">Magazine</option>
                <option value="Reporter">Reporter</option>
                <option value="Engineer/Technician">Engineer/Technician</option>
                <option value="Others">Others (Specify)</option>
              </select>
              <div class="af-field" id="ap3OtherMediumWrap" style="display:none; margin-top:10px;">
                <label class="af-lbl required">If Others, specify (Medium)</label>
                <input type="text" class="form-control" name="medium_type_other">
              </div>
            </div>
            <div class="af-field">
              <label class="af-lbl required">Designation</label>
              <select class="form-control" name="designation" required>
                <option value="">Select</option>
                <option value="Producer/Editor">Producer/Editor</option>
                <option value="Correspondent">Correspondent</option>
                <option value="Photographer">Photographer</option>
                <option value="Freelance">Freelance</option>
                <option value="Camera person">Camera person</option>
                <option value="News Photo">News Photo</option>
                <option value="Others">Others (Specify)</option>
              </select>
              <div class="af-field" id="ap3OtherDesignationWrap" style="display:none; margin-top:10px;">
                <label class="af-lbl required">If Others, specify (Designation)</label>
                <input type="text" class="form-control" name="designation_other">
              </div>
            </div>
          </div>

          <div class="employment-only">
            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl">Media Organisation Represented</label>
                <input type="text" class="form-control" name="media_org">
              </div>
              <div class="af-field">
                <label class="af-lbl">Physical Address</label>
                <input type="text" class="form-control" name="media_org_address">
              </div>
            </div>

            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl">Phone</label>
                <input type="text" class="form-control" name="media_org_phone">
              </div>
              <div class="af-field">
                <label class="af-lbl">Email</label>
                <input type="email" class="form-control" name="media_org_email">
              </div>
            </div>

            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl">Name of Editor/Publisher</label>
                <input type="text" class="form-control" name="editor_publisher_name">
              </div>
              <div class="af-field">
                <label class="af-lbl">Immediate Supervisor you report to</label>
                <input type="text" class="form-control" name="immediate_supervisor">
              </div>
            </div>

            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl">Which other Organisations do you string for?</label>
                <input type="text" class="form-control" name="string_for_orgs">
              </div>
              <div class="af-field">
                <label class="af-lbl">Give details</label>
                <textarea class="form-control" rows="2" name="string_for_details"></textarea>
              </div>
            </div>
          </div>

          <div class="scope-foreign">
            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl">Country in which media practitioner is based</label>
                <input type="text" class="form-control" name="journalist_based_country">
              </div>
              <div class="af-field">
                <label class="af-lbl required">Arrived on</label>
                <input type="date" class="form-control" name="arrived_on" data-req="1">
              </div>
            </div>

            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl required">By Air/Road</label>
                <select class="form-control" name="arrival_mode" data-req="1">
                  <option value="">Select</option>
                  <option value="Air">Air</option>
                  <option value="Road">Road</option>
                </select>
              </div>
              <div class="af-field">
                <label class="af-lbl required">Port of Entry</label>
                <input type="text" class="form-control" name="port_of_entry" data-req="1">
              </div>
            </div>

            <div class="af-row">
              <div class="af-field">
                <label class="af-lbl required">Departing on</label>
                <input type="date" class="form-control" name="departing_on" data-req="1">
              </div>
              <div class="af-field">
                <label class="af-lbl required">Special assignment to be covered in Zimbabwe</label>
                <textarea class="form-control" rows="2" name="special_assignment" data-req="1"></textarea>
              </div>
            </div>
          </div>

          <div class="af-subhead"><i class="ri-group-line"></i> References <span style="margin-left:auto; font-weight:700; letter-spacing:0; text-transform:none; color:var(--af-muted); font-size:11.5px;">3 required</span></div>

          <div class="af-row single">
            <div class="af-field">
              <div id="ap3RefereeFixedRows"></div>
              <div class="form-hint"><i class="ri-information-line"></i> Fill all 3 references (Name, Address, Phone).</div>
            </div>
          </div>
        </div>

        {{-- ===================== STEP 4: PAST WORK SAMPLES ===================== --}}
        <div class="step-content af-step-content" id="ap3-step-4">
          <h3 class="af-step-title">Past Work Samples</h3>
          <div class="af-info">
            <i class="ri-information-line"></i>
            <div>Upload five samples of your past work as a single file (articles, photos, etc.). This is required if you do not have formal media qualifications.</div>
          </div>

          <div class="af-row single">
            <div class="af-field">
              <div class="form-hint"><i class="ri-information-line"></i> Upload your supporting qualification documents below. You can select more than one file.</div>
            </div>
          </div>

          <div class="af-row single">
            <div class="af-field">
              <label class="af-lbl">Academic / Educational Certificate</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-award-line"></i></div>
                <h5>Upload Academic Certificate</h5>
                <p>PDF / JPG / PNG &mdash; Max 10MB</p>
                <input type="file" name="educational_certificate[]" id="edu_cert" accept=".pdf,.jpg,.jpeg,.png" multiple style="display:none;">
                <label for="edu_cert" class="af-btn af-btn-ink af-btn-sm">Choose Files</label>
              </div>
              <div class="uploaded-files" data-doc-type="educational_certificate"></div>
              <div class="form-hint" style="margin-top:8px;">
                <i class="ri-information-line"></i>
                <span><strong>Only academic/educational certificate files are accepted.</strong> Other document types will be rejected. Accepted formats: PDF, JPG, PNG</span>
              </div>
            </div>
          </div>

          <div class="af-row single">
            <div class="af-field">
              <label class="af-lbl">Upload five samples of your past work as a single file</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-folder-upload-line"></i></div>
                <h5>Upload Past Work</h5>
                <p>PDF / JPG / PNG / DOCX &mdash; Max 10MB</p>
                <input type="file" name="past_work_samples[]" id="past_work" accept=".pdf,.jpg,.jpeg,.png,.docx" style="display:none;">
                <label for="past_work" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div id="pastWorkFilesList" class="uploaded-files-list mt-2"></div>
            </div>
          </div>

          <div class="af-subhead"><i class="ri-links-line"></i> Online Work Links</div>
          <div class="af-row single">
            <div class="af-field">
              <div id="ap3PastWorkLinksRows"></div>
              <button type="button" class="af-btn af-btn-soft af-btn-sm mt-2" id="ap3AddPastWorkLinkRow">
                <i class="ri-add-line"></i> Add Web Link
              </button>
            </div>
          </div>
        </div>

        {{-- ===================== CUSTOM FIELDS (Injected by IT Admin) ===================== --}}
        @include('portal.partials.custom_form_fields', ['customFormType' => 'accreditation', 'customFieldStep' => null])

        {{-- ===================== STEP 5: UPLOADS + DECLARATION ===================== --}}
        <div class="step-content af-step-content" id="ap3-step-5">
          <h3 class="af-step-title">Annexures / Uploads &amp; Declaration</h3>
          <div class="af-info">
            <i class="ri-information-line"></i>
            <div>Upload required documents. Photo should be passport size with a white background.</div>
          </div>

          <div class="scope-local">
            <div class="af-row single">
              <div class="af-field">
                <label class="af-lbl required">Collection Office</label>
                <select class="form-control" name="collection_region" required>
                  <option value="">Select collection office</option>
                  <option value="harare">Harare Head Office - ZMC House, {{ config('organization.registered_address') }}</option>
                  <option value="bulawayo">Bulawayo Regional Office - 17 Rylanders Court, Windsor Park Complex, 16th Avenue, Famona, Bulawayo</option>
                </select>
              </div>
            </div>
          </div>

          <div class="af-row">
            <div class="af-field">
              <label class="af-lbl required">Photo (Passport size, white background)</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-camera-line"></i></div>
                <h5>Upload Photo</h5>
                <p>JPG / PNG &mdash; clear face, white background</p>
                <input type="file" name="passport_photo" id="pass_photo" accept=".jpg,.jpeg,.png" style="display:none;" required>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                  <label for="pass_photo" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
                  <button type="button" class="af-btn af-btn-soft af-btn-sm" id="ap3TakePhotoBtn">
                    <i class="ri-camera-lens-line"></i> Take Photo
                  </button>
                </div>
              </div>
              <div class="uploaded-files" data-doc-type="passport_photo"></div>
            </div>

            <div class="af-field scope-local">
              <label class="af-lbl required">National ID Scan</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-id-card-line"></i></div>
                <h5>Upload National ID</h5>
                <p>PDF / JPG / PNG</p>
                <input type="file" name="id_scan" id="id_scan" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" required>
                <label for="id_scan" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div class="uploaded-files" data-doc-type="id_scan"></div>
            </div>

            <div class="af-field scope-foreign">
              <label class="af-lbl required">Passport Bio Data Page</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-passport-line"></i></div>
                <h5>Upload Passport Bio Data Page</h5>
                <p>PDF / JPG / PNG</p>
                <input type="file" name="passport_biodata_page" id="pass_bio" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" data-req="1">
                <label for="pass_bio" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div class="uploaded-files" data-doc-type="passport_biodata_page"></div>
            </div>
          </div>

          <div class="af-row scope-foreign">
            <div class="af-field">
              <label class="af-lbl required">Clearance Letter</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-file-text-line"></i></div>
                <h5>Upload Clearance Letter</h5>
                <p>PDF / JPG / PNG</p>
                <input type="file" name="clearance_letter" id="clear_letter" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" data-req="1">
                <label for="clear_letter" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div class="uploaded-files" data-doc-type="clearance_letter"></div>
            </div>
            <div class="af-field"></div>
          </div>

          <div class="af-row scope-local">
            <div class="af-field employment-letter-only">
              <label class="af-lbl required">Employment Letter (Employed)</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-file-text-line"></i></div>
                <h5>Upload Employment Letter</h5>
                <p>Required for Employed applicants</p>
                <input type="file" name="employment_letter" id="emp_letter" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                <label for="emp_letter" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div class="uploaded-files" data-doc-type="employment_letter"></div>
            </div>

            <div class="af-field freelancer-only" style="display:none;">
              <label class="af-lbl required">Reference Letter / Affidavit (Freelancer)</label>
              <div class="upload-area af-upload">
                <div class="af-upload-icon"><i class="ri-file-paper-2-line"></i></div>
                <h5>Upload Reference / Testimonial / Affidavit</h5>
                <p>Required for Freelancers</p>
                <input type="file" name="reference_letter" id="ref_letter" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                <label for="ref_letter" class="af-btn af-btn-ink af-btn-sm">Choose File</label>
              </div>
              <div class="uploaded-files" data-doc-type="reference_letter"></div>
            </div>
          </div>

          <div class="af-subhead"><i class="ri-shield-check-line"></i> Declaration</div>

          <div class="af-declaration">
            I declare that all the information given above, to the best of my knowledge is true and complete.
            <div class="af-time"><i class="ri-history-line"></i> Date &amp; Time: {{ now()->format('d M Y, H:i') }} (Automated)</div>
          </div>

          <div class="af-row single mt-3">
            <div class="af-field">
              <label class="af-check" style="align-self:flex-start;">
                <input type="checkbox" id="ap3DeclarationConfirm" name="declaration_confirmed" value="1" required>
                <span>I confirm that I have read and agree to this declaration.</span>
              </label>
              <input type="hidden" name="declaration_date" value="{{ date('Y-m-d') }}">
            </div>
          </div>
        </div>

      </div>

      {{-- Footer buttons --}}
      <div class="af-form-foot">
        <div class="af-foot-left">
          <button type="button" class="af-btn af-btn-outline" id="ap3PrevBtn">
            <i class="ri-arrow-left-line"></i> Previous
          </button>
          <button type="button" class="af-btn af-btn-soft" id="ap3SaveDraftBtn">
            <i class="ri-save-line"></i> Save Draft
          </button>
        </div>
        <button type="button" class="af-btn af-btn-gold" id="ap3NextBtn">
          Next <i class="ri-arrow-right-line"></i>
        </button>
      </div>
    </form>
  </div>

  {{-- ============================ REVIEW MODAL ============================ --}}
  <div class="modal fade af-modal" id="ap3ReviewModal" tabindex="-1" aria-labelledby="ap3ReviewModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ap3ReviewModalLabel">
            <i class="ri-file-search-line"></i> Review Your Application
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="ap3ReviewContent"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit Application</button>
          @php
            $isResubmission = isset($draft) && !$draft->is_draft && in_array($draft->status, [\App\Models\Application::CORRECTION_REQUESTED, \App\Models\Application::RETURNED_TO_APPLICANT], true);
          @endphp
          @if($isResubmission)
            <button type="button" class="btn btn-success" id="ap3SubmitCorrectionsBtn" onclick="submitApplication()">
              <i class="ri-send-plane-line me-2"></i>Submit Corrections
            </button>
          @else
            <button type="button" class="btn btn-primary" id="ap3ProceedToPayBtn">
              <i class="ri-bank-card-line me-2"></i>Proceed to Payment
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ============================ PAYMENT MODAL ============================ --}}
  <div class="modal fade af-modal" id="ap3PaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <div class="modal-title">
              <i class="ri-bank-card-line"></i>
              Payment Required
            </div>
            <div class="zmc-modal-sub" id="ap3_payment_meta" style="font-size:12px; color:rgba(255,255,255,.62); margin-top:3px;">Application submitted successfully</div>
          </div>
        </div>

        <div class="modal-body">
          <div class="alert alert-success border mb-3">
            <div class="fw-bold mb-1"><i class="ri-check-line me-1"></i> Application Submitted</div>
            <div class="text-muted small" id="ap3_payment_ref_info">Your application has been submitted. Please proceed with payment.</div>
          </div>

          <ul class="nav nav-pills mb-3" id="ap3PayTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-ap3-paynow" data-bs-toggle="pill" data-bs-target="#pane-ap3-paynow" type="button" role="tab">
                <i class="ri-flashlight-line me-1"></i> PayNow
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-ap3-proof" data-bs-toggle="pill" data-bs-target="#pane-ap3-proof" type="button" role="tab">
                <i class="ri-file-upload-line me-1"></i> Upload Proof
              </button>
            </li>
          </ul>

          <div class="tab-content">
            {{-- PayNow Tab --}}
            <div class="tab-pane fade show active" id="pane-ap3-paynow" role="tabpanel">
              <div id="ap3_paynow_step1">
                <div class="mb-4">
                  <label class="form-label fw-bold" style="font-size:11.5px; letter-spacing:.07em; text-transform:uppercase; color:#64748B;">
                    <i class="ri-money-dollar-circle-line me-1" style="color:var(--af-gold)"></i> Select Currency
                  </label>
                  <div class="row g-3">
                    <div class="col-6">
                      <div class="currency-option selected" data-currency="USD" onclick="ap3SelectCurrency('USD')">
                        <input type="radio" name="ap3_currency" id="ap3_currency_usd" value="USD" checked>
                        <label for="ap3_currency_usd" style="cursor:pointer; margin:0;">
                          <i class="ri-money-dollar-circle-line"></i>
                          <div class="fw-bold">USD</div>
                          <div class="small text-muted">US Dollar</div>
                          <div class="currency-amount fw-bold text-success mt-1" id="ap3_fee_usd_display">${{ number_format((float) config('fees.accreditation.new'), 2) }}</div>
                        </label>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="currency-option" data-currency="ZWG" onclick="ap3SelectCurrency('ZWG')">
                        <input type="radio" name="ap3_currency" id="ap3_currency_zwg" value="ZWG">
                        <label for="ap3_currency_zwg" style="cursor:pointer; margin:0;">
                          <i class="ri-money-dollar-circle-line"></i>
                          <div class="fw-bold">ZWG</div>
                          <div class="small text-muted">Zimbabwe Gold</div>
                          <div class="currency-amount fw-bold text-success mt-1">Check RBZ Rate</div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div id="ap3_rbz_section" class="d-none mb-4">
                  <div class="alert alert-info border">
                    <div class="d-flex align-items-start">
                      <i class="ri-information-line me-2 mt-1"></i>
                      <div class="flex-grow-1">
                        <div class="fw-bold mb-2">Current RBZ Exchange Rate</div>
                        <div class="small mb-2">Accounts maintains this rate from the official RBZ listing. The portal applies it to your ZWG payment:</div>
                        <a href="https://www.rbz.co.zw/index.php" target="_blank" class="af-btn af-btn-soft af-btn-sm">
                          <i class="ri-external-link-line"></i> View RBZ Exchange Rates
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="card border shadow-sm" style="border-radius:14px; border-color:var(--af-line) !important;">
                    <div class="card-body">
                      <h6 class="card-title fw-bold mb-3">
                        <i class="ri-calculator-line me-1" style="color:var(--af-gold)"></i> Exchange Rate Calculator
                      </h6>
                      <div class="row g-3">
                        <div class="col-12">
                          <label class="form-label small fw-bold">RBZ Exchange Rate (1 USD = ? ZWG)</label>
                          <input type="number" step="0.0001" min="0" class="form-control" id="ap3_rbz_rate_input" placeholder="Loading rate…" readonly>
                          <div class="text-muted small mt-1" id="ap3_rbz_rate_effective_date">Rate maintained by Accounts</div>
                        </div>
                        <div class="col-12">
                          <div class="bg-light p-3 rounded" style="border-radius:12px;">
                            <div class="row">
                              <div class="col-6">
                                <div class="text-muted small">USD Amount</div>
                                <div class="fw-bold" id="ap3_fee_usd_calculator">${{ number_format((float) config('fees.accreditation.new'), 2) }}</div>
                              </div>
                              <div class="col-6 text-end">
                                <div class="text-muted small">ZWG Amount</div>
                                <div class="fw-bold text-success" id="ap3_calc_zwg_amount">ZWG 0.00</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center py-2">
                  <button type="button" class="af-btn af-btn-ink w-100 justify-content-center py-3" id="ap3BtnOpenPayNow" style="font-size:14px;">
                    <i class="ri-bank-card-2-line me-2"></i> <span id="ap3_paynow_btn_text">Pay with PayNow (USD)</span>
                  </button>
                  <div class="text-muted small mt-3">You will be automatically redirected to PayNow to complete payment. Do not close this tab.</div>
                </div>

                <div id="ap3_paynow_redirecting" class="d-none">
                  <div class="alert alert-info border text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <strong>Redirecting you to PayNow...</strong>
                    <div class="small mt-1">Please wait. You will return here automatically after payment.</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Upload Proof Tab --}}
            <div class="tab-pane fade" id="pane-ap3-proof" role="tabpanel">
              <form id="ap3ProofForm">
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label small">First Name</label>
                    <input type="text" class="form-control" name="payer_first_name" value="{{ explode(' ', Auth::user()?->name ?? '')[0] ?? '' }}" readonly required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Last Name</label>
                    <input type="text" class="form-control" name="payer_last_name" value="{{ count(explode(' ', Auth::user()?->name ?? '')) > 1 ? implode(' ', array_slice(explode(' ', Auth::user()?->name ?? ''), 1)) : '' }}" readonly required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Payment Date</label>
                    <input type="date" class="form-control" name="payment_date" required max="{{ date('Y-m-d') }}">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Amount Paid</label>
                    <input type="number" step="0.01" class="form-control" name="amount" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Bank / Payment Method</label>
                    <input type="text" class="form-control" name="method" placeholder="e.g. CBZ / Steward / EcoCash" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Reference / Transaction Number (optional)</label>
                    <input type="text" class="form-control" name="reference" placeholder="e.g. Bank reference">
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Upload Proof (PDF/JPG/PNG)</label>
                    <input type="file" class="form-control" name="proof_file" accept=".pdf,.jpg,.jpeg,.png" required>
                  </div>
                </div>
                <button type="submit" class="af-btn af-btn-ink w-100 justify-content-center mt-3" id="ap3BtnSubmitProof">
                  <i class="ri-upload-cloud-2-line"></i> Submit Payment Proof
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="ap3PaymentClose">
            <i class="ri-arrow-left-line me-1"></i> Pay Later from Dashboard
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ============================ CAMERA MODAL ============================ --}}
  <div class="modal fade af-modal" id="ap3CameraModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ri-camera-lens-line"></i> Take Photo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" id="ap3CameraCloseBtn"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info" style="border-radius:13px; background:#EAF2FE; border-color:#D6E5FD; color:#1E40AF;">
            <i class="ri-information-line me-1"></i>
            Stand in front of a <strong>white background</strong>. Make sure your face is well-lit.
          </div>

          <video id="ap3CamVideo" autoplay playsinline style="width:100%; border-radius:14px; background:#000;"></video>
          <canvas id="ap3CamCanvas" style="display:none;"></canvas>

          <div class="d-flex gap-2 mt-3">
            <button type="button" class="af-btn af-btn-ink flex-grow-1 justify-content-center" id="ap3CaptureBtn">
              <i class="ri-camera-line"></i> Capture
            </button>
            <button type="button" class="af-btn af-btn-soft" id="ap3RetakeBtn" style="display:none;">
              <i class="ri-refresh-line"></i> Retake
            </button>
          </div>

          <div id="ap3CapturePreview" class="mt-3" style="display:none;">
            <div class="fw-semibold mb-2" style="font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:#64748B;">Preview</div>
            <img id="ap3PreviewImg" alt="Captured photo" style="width:100%; border-radius:14px; border:1px solid var(--af-line);" />
            <button type="button" class="af-btn af-btn-gold w-100 justify-content-center mt-3" id="ap3UsePhotoBtn">
              <i class="ri-check-line"></i> Use This Photo
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@include('portal.partials.nid_validator')

@push('scripts')
<script>
  // Ensure DOM is ready before running any scripts
  document.addEventListener('DOMContentLoaded', function() {
    const ap3AccreditationFees = {
      local: @json((float) config('fees.accreditation.new')),
      foreign: @json((float) config('fees.accreditation.foreign_new')),
    };
    let ap3AccreditationFeeUSD = ap3AccreditationFees.local;
    const updateAp3Fee = (scope) => {
      ap3AccreditationFeeUSD = ap3AccreditationFees[scope] ?? ap3AccreditationFees.local;
      const formattedFee = `$${ap3AccreditationFeeUSD.toFixed(2)}`;
      const feeDisplay = document.getElementById('ap3_fee_usd_display');
      const calculatorDisplay = document.getElementById('ap3_fee_usd_calculator');
      if (feeDisplay) feeDisplay.textContent = formattedFee;
      if (calculatorDisplay) calculatorDisplay.textContent = formattedFee;
    };
    // Check if variables already exist to avoid duplicate declaration error
    if (typeof ap3CurrentStep === 'undefined') {
      var ap3CurrentStep = 1;
      var ap3MaxStepReached = 1;
    }
    const ap3CsrfToken = '{{ csrf_token() }}';

    const ap3Steps = document.querySelectorAll('#new-application-page .af-step');
    const ap3StepContents = [
      document.getElementById('ap3-step-1'),
      document.getElementById('ap3-step-2'),
      document.getElementById('ap3-step-3'),
      document.getElementById('ap3-step-4'),
      document.getElementById('ap3-step-5'),
    ];

    const ap3PrevBtn = document.getElementById('ap3PrevBtn');
    const ap3NextBtn = document.getElementById('ap3NextBtn');
    const ap3SaveDraftBtn = document.getElementById('ap3SaveDraftBtn');

    function getFormData() {
      const form = document.getElementById('ap3Form');
      const formData = {};
      const inputs = form.querySelectorAll('input, select, textarea');

      inputs.forEach(input => {
        if (!input.name) return;
        if (input.type === 'file') return;

        if (input.type === 'radio') {
          if (input.checked) formData[input.name] = input.value;
        } else if (input.type === 'checkbox') {
          formData[input.name] = input.checked;
        } else {
          formData[input.name] = input.value;
        }
      });

      return formData;
    }

    function ap3ShowStep(step){
      ap3StepContents.forEach((el, idx) => el.classList.toggle('active', idx === (step-1)));

      ap3Steps.forEach(s => {
        const n = parseInt(s.dataset.step, 10);
        s.classList.remove('active','completed');
        if(n === step) s.classList.add('active');
        
        if (n < step || n < ap3MaxStepReached) {
          if (n !== step) s.classList.add('completed');
          s.querySelector('.af-step-dot').innerHTML = '<i class="ri-check-line"></i>';
        } else {
          s.querySelector('.af-step-dot').textContent = n;
        }
      });

      ap3PrevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
      ap3NextBtn.innerHTML = step === 5 ? 'Review & Submit <i class="ri-file-search-line"></i>' : 'Next <i class="ri-arrow-right-line"></i>';
    }

    function setRequiredWithin(container, isRequired) {
      if (!container) return;
      container.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type === 'hidden') return;
        if (el.type === 'file') return;

        if (isRequired) {
          if (el.dataset.req === "1") el.setAttribute('required', 'required');
        } else {
          if (el.dataset.req === "1") el.removeAttribute('required');
        }
      });

      container.querySelectorAll('input[type="file"][data-req="1"]').forEach(f => {
        if (isRequired && !hasExistingFile(f.name)) f.setAttribute('required','required');
        else f.removeAttribute('required');
      });
    }

    function applyScopeVisibility(scope) {
      updateAp3Fee(scope);
      const locals = document.querySelectorAll('.scope-local');
      const foreigners = document.querySelectorAll('.scope-foreign');

      locals.forEach(el => el.style.display = (scope === 'local') ? '' : 'none');
      foreigners.forEach(el => el.style.display = (scope === 'foreign') ? '' : 'none');

      locals.forEach(el => setRequiredWithin(el, scope === 'local'));
      foreigners.forEach(el => setRequiredWithin(el, scope === 'foreign'));
    }

    function visible(el){
      if (!el) return false;
      if (el.closest('.scope-local') && document.getElementById('ap3_scope').value !== 'local') return false;
      if (el.closest('.scope-foreign') && document.getElementById('ap3_scope').value !== 'foreign') return false;
      if ((el.closest('.employment-only') || el.closest('.employment-letter-only')) && currentEmploymentType() !== 'employed') return false;
      if (el.closest('.freelancer-only') && currentEmploymentType() !== 'freelancer') return false;

      if (el.offsetParent === null) {
        if (el.type === 'file') {
          const parent = el.closest('.af-field') || el.parentElement;
          return parent ? parent.offsetParent !== null : false;
        }
        return false;
      }
      return true;
    }

    function currentEmploymentType(){
      return document.querySelector('input[name="employment_type"]:checked')?.value || '';
    }

    function hasExistingFile(docType) {
      const container = document.querySelector(`.uploaded-files[data-doc-type="${docType}"]`);
      return container && container.children.length > 0;
    }

    function validateFilesForStep5(){
      const scope = document.getElementById('ap3_scope').value;

      if (!hasExistingFile('passport_photo')) {
          const passportPhoto = document.querySelector('input[name="passport_photo"]');
          if (!passportPhoto?.files?.[0]) return 'Please upload or take your passport photo.';
      }

      if (scope === 'local') {
        if (!hasExistingFile('id_scan')) {
          const idScan = document.querySelector('input[name="id_scan"]');
          if (!idScan?.files?.[0]) return 'Please upload your National ID Scan.';
        }

        const empType = currentEmploymentType();
        if (empType === 'employed') {
          if (!hasExistingFile('employment_letter')) {
              const emp = document.querySelector('input[name="employment_letter"]');
              if (!emp?.files?.[0]) return 'Please upload Employment Letter (Employed applicants).';
          }
        }
        if (empType === 'freelancer') {
          if (!hasExistingFile('reference_letter')) {
              const ref = document.querySelector('input[name="reference_letter"]');
              if (!ref?.files?.[0]) return 'Please upload Reference/Testimonial/Affidavit (Freelancers).';
          }
        }
      } else {
        if (!hasExistingFile('passport_biodata_page')) {
            const pb = document.querySelector('input[name="passport_biodata_page"]');
            if (!pb?.files?.[0]) return 'Please upload your Passport Bio Data Page.';
        }
        if (!hasExistingFile('clearance_letter')) {
            const cl = document.querySelector('input[name="clearance_letter"]');
            if (!cl?.files?.[0]) return 'Please upload your Clearance Letter.';
        }
      }

      if (scope === 'foreign') {
        const bio = document.querySelector('input[name="passport_biodata_page"]');
        const clr = document.querySelector('input[name="clearance_letter"]');
        if (!bio?.files?.[0]) return 'Please upload Passport Bio Data Page.';
        if (!clr?.files?.[0]) return 'Please upload Clearance Letter.';
      }

      return '';
    }

    function ap3ValidateStep(step){
      const scope = document.getElementById('ap3_scope').value;
      if(step === 1){
        if(!scope){ alert('Please select an applicant type (Local or Foreign Media Practitioner)'); return false; }
      }

      const currentContent = ap3StepContents[step-1];

      const required = currentContent.querySelectorAll('[required]');
      for(const field of required){
        if (!visible(field)) continue;

        if(field.type === 'radio'){
          const group = currentContent.querySelectorAll(`input[name="${field.name}"]`);
          const anyChecked = Array.from(group).some(r => visible(r) && r.checked);
          if(!anyChecked){ alert('Please select an option.'); return false; }
        } else if (field.type !== 'file') {
          if(!String(field.value || '').trim()){
            alert('Please complete all required fields.');
            field.focus?.();
            return false;
          }
          if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
              alert('Please enter a valid email address.');
              field.focus?.();
              return false;
            }
          }
          if (field.name.includes('phone') && !field.name.includes('country_code')) {
            const phoneStr = field.value.replace(/\D/g, '');
            if (phoneStr.length < 6) {
               alert('Please enter a valid phone number with at least 6 digits.');
               field.focus?.();
               return false;
            }
          }
        }
      }

      if (step === 3) {
        const refNames = new Set();
        const refPhones = new Set();
        for (let i=1;i<=3;i++){
          const n = document.querySelector(`[name="referee_name_${i}"]`);
          const a = document.querySelector(`[name="referee_address_${i}"]`);
          const p = document.querySelector(`[name="referee_phone_${i}"]`);
          if (!n?.value?.trim() || !a?.value?.trim() || !p?.value?.trim()){
            alert('Please complete all 3 references (Name, Address, Phone).');
            return false;
          }
          const nm = n.value.trim().toLowerCase();
          const ph = p.value.trim().replace(/\D/g, '');
          
          if (ph.length < 6) {
            alert('Please enter a valid phone number for reference ' + i + '.');
            p.focus();
            return false;
          }
          if (refNames.has(nm)) {
            alert('Reference names must be distinct. You cannot provide the same person twice.');
            n.focus();
            return false;
          }
          if (refPhones.has(ph)) {
            alert('Reference phone numbers must be distinct. You cannot provide the same contact details twice.');
            p.focus();
            return false;
          }
          refNames.add(nm);
          refPhones.add(ph);
        }
      }

      if (step === 4) {
        const urlInputs = currentContent.querySelectorAll('input[name^="past_work_link_url_"]');
        for (const urlInput of urlInputs) {
          const url = (urlInput.value || '').trim();
          if (url) {
            const urlPattern = /^https?:\/\/.+/i;
            if (!urlPattern.test(url)) {
              alert('Invalid URL format. All web links must start with http:// or https://\n\nExample: https://example.com/article');
              urlInput.focus();
              return false;
            }

            try {
              const urlObj = new URL(url);
              if (!urlObj.hostname || urlObj.hostname.length < 3) {
                alert('Invalid URL. Please provide a complete URL with a valid domain.\n\nExample: https://example.com/article');
                urlInput.focus();
                return false;
              }
            } catch (e) {
              alert('Invalid URL format. Please provide a valid web link.\n\nExample: https://example.com/article');
              urlInput.focus();
              return false;
            }
          }
        }
      }

      if (step === 5) {
        const msg = validateFilesForStep5();
        if (msg) { alert(msg); return false; }

        // Duplicate file check across all visible file inputs
        const fileHashes = new Set();
        const allFileInputs = document.querySelectorAll('#ap3Form input[type="file"]');
        for (const input of allFileInputs) {
          if (!visible(input)) continue;
          if (input.files.length > 0) {
            for (const file of input.files) {
              const hash = file.name + '|' + file.size + '|' + file.type;
              if (fileHashes.has(hash)) {
                alert('Duplicate document detected: "' + file.name + '". Please do not upload the same document in multiple fields.');
                return false;
              }
              fileHashes.add(hash);
            }
          }
        }
      }

      if (step === 3) {
        const mt = document.querySelector('select[name="medium_type"]')?.value;
        const des = document.querySelector('select[name="designation"]')?.value;
        if (mt === 'Others' && !document.querySelector('input[name="medium_type_other"]')?.value?.trim()){
          alert('Please specify the medium type (Others).'); return false;
        }
        if (des === 'Others' && !document.querySelector('input[name="designation_other"]')?.value?.trim()){
          alert('Please specify the designation (Others).'); return false;
        }
      }

      return true;
    }

    function rowHtmlPastWorkLink(idx){
      return `
        <div class="d-flex gap-2 mb-2 ap3-row af-dyn-row" data-row="${idx}" style="margin-bottom:10px;">
          <input class="form-control past-work-url" name="past_work_link_url_${idx}" placeholder="URL (e.g. https://example.com/article)" style="flex: 2;">
          <input class="form-control" name="past_work_link_desc_${idx}" placeholder="Description" style="flex: 2;">
          <button type="button" class="af-row-remove ap3RemoveRow" title="Remove"><i class="ri-close-line"></i></button>
        </div>
      `;
    }

    function initRepeater(containerId, addBtnId, rowBuilder){
      const container = document.getElementById(containerId);
      const btn = document.getElementById(addBtnId);
      if(!container || !btn) return null;

      let idx = 1;

      function wireRemoveButtons() {
        container.querySelectorAll('.ap3RemoveRow').forEach(rm => {
          rm.onclick = (e) => e.target.closest('.ap3-row').remove();
        });
      }

      function wireUrlValidation() {
        container.querySelectorAll('.past-work-url').forEach(input => {
          input.addEventListener('blur', function() {
            const url = this.value.trim();
            if (url && !url.match(/^https?:\/\/.+/i)) {
              this.style.borderColor = '#dc3545';
              this.style.backgroundColor = '#fff5f5';
              let errorMsg = this.nextElementSibling;
              if (!errorMsg || !errorMsg.classList.contains('url-error')) {
                errorMsg = document.createElement('small');
                errorMsg.className = 'url-error text-danger d-block mt-1';
                errorMsg.style.fontSize = '11px';
                this.parentElement.appendChild(errorMsg);
              }
              errorMsg.textContent = '⚠ URL must start with http:// or https://';
            } else {
              this.style.borderColor = '';
              this.style.backgroundColor = '';
              const errorMsg = this.parentElement.querySelector('.url-error');
              if (errorMsg) errorMsg.remove();
            }
          });

          input.addEventListener('input', function() {
            this.style.borderColor = '';
            this.style.backgroundColor = '';
          });
        });
      }

      function addRow(){
        container.insertAdjacentHTML('beforeend', rowBuilder(idx++));
        wireRemoveButtons();
        wireUrlValidation();
      }

      btn.addEventListener('click', addRow);

      return {
        addRow,
        clear: () => { container.innerHTML = ''; idx = 1; },
        setCount: (count) => { container.innerHTML=''; idx=1; for(let i=0;i<count;i++) addRow(); },
      };
    }

    // ===== Upload UI =====
    function setupUploads(root){
      root.querySelectorAll('.upload-area input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
          const area = input.closest('.upload-area');
          const list = area?.parentElement?.querySelector('.uploaded-files-list') || area?.parentElement?.querySelector('.uploaded-files');
          if(!area || !list) return;

          if (input.files.length > 0) {
            area.style.borderColor = '#10b981';
            area.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
          } else {
            area.style.borderColor = '';
            area.style.backgroundColor = '';
            list.innerHTML = '';
            return;
          }

          if (!input.multiple) {
              const file = input.files[0];
              const fileName = file.name.length > 30 ? file.name.slice(0,30)+'...' : file.name;
              const fileSize = (file.size/1024).toFixed(1)+' KB';
              list.innerHTML = `
                <div class="uploaded-file">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ri-file-text-line file-icon"></i>
                    <div>
                      <div class="file-name fw-semibold" style="font-size:13px;">${fileName}</div>
                      <div class="file-size text-muted" style="font-size:11px;">${fileSize}</div>
                    </div>
                  </div>
                  <button type="button" class="af-row-remove remove-file" title="Remove"><i class="ri-close-line"></i></button>
                </div>
              `;
              list.querySelector('.remove-file').addEventListener('click', () => {
                input.value = '';
                list.innerHTML = '';
                area.style.borderColor = '';
                area.style.backgroundColor = '';
              });
          } else {
              let html = '';
              Array.from(input.files).forEach((file) => {
                  const fileName = file.name.length > 30 ? file.name.slice(0,30)+'...' : file.name;
                  const fileSize = (file.size/1024).toFixed(1)+' KB';
                  html += `
                    <div class="uploaded-file">
                      <div class="d-flex align-items-center gap-2">
                        <i class="ri-file-text-line file-icon"></i>
                        <div>
                          <div class="file-name fw-semibold" style="font-size:13px;">${fileName}</div>
                          <div class="file-size text-muted" style="font-size:11px;">${fileSize}</div>
                        </div>
                      </div>
                    </div>
                  `;
              });
              list.innerHTML = html + `<button type="button" class="af-btn af-btn-soft af-btn-sm w-100 justify-content-center clear-files"><i class="ri-delete-bin-line"></i> Clear All Files</button>`;
              list.querySelector('.clear-files').addEventListener('click', () => {
                input.value = '';
                list.innerHTML = '';
                area.style.borderColor = '';
                area.style.backgroundColor = '';
              });
          }

          if (input.name === 'educational_certificate') {
            validateCertificateNow(input);
          }
        });
      });
    }

    async function validateCertificateNow(input){
      const file = input.files && input.files[0];
      if (!file) return;

      const area = input.closest('.upload-area');
      const list = area?.parentElement?.querySelector('.uploaded-files-list') || area?.parentElement?.querySelector('.uploaded-files');

      if (area) {
        area.style.borderColor = '#10b981';
        area.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
      }

      const fileName = file.name.length > 30 ? file.name.slice(0,30)+'...' : file.name;
      const fileSize = (file.size/1024).toFixed(1)+' KB';
      if (list) {
        list.innerHTML = `
          <div class="uploaded-file" style="border-color:#A7F3D0; background:rgba(16,185,129,.05);">
            <div class="d-flex align-items-center gap-2">
              <i class="ri-checkbox-circle-fill text-success" style="font-size:22px;"></i>
              <div>
                <div class="file-name fw-semibold text-success" style="font-size:13px;">✓ Certificate Accepted</div>
                <div class="file-size text-muted" style="font-size:11px;">${fileName} (${fileSize})</div>
              </div>
            </div>
            <button type="button" class="af-row-remove remove-file" title="Remove"><i class="ri-close-line"></i></button>
          </div>
        `;
        list.querySelector('.remove-file').addEventListener('click', () => {
          input.value = '';
          list.innerHTML = '';
          area.style.borderColor = '';
          area.style.backgroundColor = '';
        });
      }
    }

    // ===== Fixed 3 referees =====
    function buildReferees(){
      const wrap = document.getElementById('ap3RefereeFixedRows');
      if (!wrap) return;
      let html = '';
      for (let i=1;i<=3;i++){
        html += `
          <div class="af-dyn-row">
            <input class="form-control" name="referee_name_${i}" placeholder="Name" required style="flex:1;">
            <input class="form-control" name="referee_address_${i}" placeholder="Address" required style="flex:1.4;">
            <input class="form-control" name="referee_phone_${i}" placeholder="Phone" required style="flex:1;">
          </div>
        `;
      }
      wrap.innerHTML = html;
    }

    // ===== Employment toggle show/hide =====
    function applyEmploymentVisibility() {
      const type = currentEmploymentType();
      document.querySelectorAll('.employment-only, .employment-letter-only').forEach(el => {
        el.style.display = (type === 'employed') ? '' : 'none';
      });
      document.querySelectorAll('.freelancer-only').forEach(el => {
        el.style.display = (type === 'freelancer') ? '' : 'none';
      });

      const empInput = document.querySelector('input[name="employment_letter"]');
      const refInput = document.querySelector('input[name="reference_letter"]');

      if (empInput) empInput.required = (document.getElementById('ap3_scope').value === 'local' && type === 'employed');
      if (refInput) refInput.required = (document.getElementById('ap3_scope').value === 'local' && type === 'freelancer');
    }

    // ===== Others specify toggle =====
    function applyOthersVisibility(){
      const mt = document.querySelector('select[name="medium_type"]')?.value;
      const des = document.querySelector('select[name="designation"]')?.value;

      const mtWrap = document.getElementById('ap3OtherMediumWrap');
      const desWrap = document.getElementById('ap3OtherDesignationWrap');

      if (mtWrap) mtWrap.style.display = (mt === 'Others') ? '' : 'none';
      if (desWrap) desWrap.style.display = (des === 'Others') ? '' : 'none';

      const mtOther = document.querySelector('input[name="medium_type_other"]');
      const desOther = document.querySelector('input[name="designation_other"]');
      if (mtOther) mtOther.required = (mt === 'Others');
      if (desOther) desOther.required = (des === 'Others');
    }

    // ===== Review =====
    function getUploadedFiles() {
      const files = [];
      const fileInputs = document.querySelectorAll('#ap3Form input[type="file"]');
      fileInputs.forEach(input => {
        if (input.files && input.files[0]) {
          const file = input.files[0];
          files.push({
            name: input.name,
            fileName: file.name,
            size: (file.size / 1024).toFixed(1) + ' KB'
          });
        }
      });
      return files;
    }

    function extractRows(prefix, formData, columns) {
      const rows = [];
      const indices = new Set();
      Object.keys(formData || {}).forEach(k => {
        const m = k.match(new RegExp('^' + prefix + '[a-zA-Z_]+_([0-9]+)$'));
        if (m) indices.add(parseInt(m[1], 10));
      });
      const sorted = Array.from(indices).sort((a,b)=>a-b);
      for (const i of sorted) {
        const row = {};
        let hasAny = false;
        for (const c of columns) {
          const key = `${prefix}${c}_${i}`;
          row[c] = (formData[key] || '').trim();
          if (row[c]) hasAny = true;
        }
        if (hasAny) rows.push(row);
      }
      return rows;
    }

    function showReviewModal() {
      const formData = getFormData();
      const scope = formData.journalist_scope === 'foreign' ? 'Foreign Media Practitioner' : 'Local Media Practitioner';
      const empType = formData.employment_type || '-';

      const refs = [];
      for (let i=1;i<=3;i++){
        refs.push({
          name: formData[`referee_name_${i}`] || '-',
          address: formData[`referee_address_${i}`] || '-',
          phone: formData[`referee_phone_${i}`] || '-',
        });
      }

      const pastLinks = extractRows('past_work_link_', formData, ['url','desc']);
      const files = getUploadedFiles();

      const filesHtml = files.length ? `
        <div class="list-group list-group-flush">
          ${files.map(f => `
            <div class="list-group-item d-flex align-items-center">
              <i class="ri-file-text-line me-3 fs-4" style="color:var(--af-gold)"></i>
              <div>
                <div class="fw-semibold">${f.name.replaceAll('_',' ')}</div>
                <small class="text-muted">${f.fileName} (${f.size})</small>
              </div>
              <i class="ri-checkbox-circle-fill text-success ms-auto"></i>
            </div>
          `).join('')}
        </div>` : `<div class="alert alert-danger mb-0">No documents uploaded.</div>`;

      const photoInput = document.querySelector('input[name="passport_photo"]');
      const photoFile = (photoInput && photoInput.files && photoInput.files[0]) ? photoInput.files[0] : null;
      const photoPreviewHtml = photoFile ? (() => {
        const isImg = (photoFile.type || '').startsWith('image/');
        if (!isImg) return `<div class="text-muted">Passport photo selected (${photoFile.name})</div>`;
        const url = URL.createObjectURL(photoFile);
        return `
          <div class="d-flex align-items-start gap-3">
            <img src="${url}" alt="Passport photo preview" style="width:120px;height:120px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;">
            <div>
              <div class="fw-semibold">Passport Photo</div>
              <small class="text-muted">${photoFile.name} (${formatFileSize(photoFile.size)})</small>
              <div class="text-muted mt-1">If this image looks wrong, go back and re-upload / retake.</div>
            </div>
          </div>`;
      })() : `<div class="alert alert-danger mb-0">No passport photo selected.</div>`;

      let personalExtra = '';
      if (formData.journalist_scope === 'local') {
        personalExtra = `
          <div class="col-6"><p><strong>National Reg. No:</strong> ${formData.national_reg_no || '-'}</p></div>
        `;
      } else {
        personalExtra = `
          <div class="col-6"><p><strong>Passport No:</strong> ${formData.passport_no || '-'}</p></div>
          <div class="col-6"><p><strong>Date of Expiry:</strong> ${formData.passport_expiry || '-'}</p></div>
          <div class="col-6"><p><strong>Issued at:</strong> ${formData.passport_issued_at || '-'}</p></div>
          <div class="col-6"><p><strong>First time in Zimbabwe:</strong> ${formData.first_time_in_zim || '-'}</p></div>
          <div class="col-6"><p><strong>Last here:</strong> ${formData.last_in_zim_when || '-'}</p></div>
          <div class="col-12"><p><strong>Address in Zimbabwe:</strong> ${formData.address_in_zimbabwe || '-'}</p></div>
        `;
      }

      const employmentExtraForeign = (formData.journalist_scope === 'foreign' && empType === 'employed') ? `
        <div class="col-6"><p><strong>Country based:</strong> ${formData.journalist_based_country || '-'}</p></div>
        <div class="col-6"><p><strong>Arrived on:</strong> ${formData.arrived_on || '-'}</p></div>
        <div class="col-6"><p><strong>By Air/Road:</strong> ${formData.arrival_mode || '-'}</p></div>
        <div class="col-6"><p><strong>Port of Entry:</strong> ${formData.port_of_entry || '-'}</p></div>
        <div class="col-6"><p><strong>Departing on:</strong> ${formData.departing_on || '-'}</p></div>
        <div class="col-12"><p><strong>Special assignment:</strong> ${formData.special_assignment || '-'}</p></div>
      ` : '';

      const reviewHtml = `
        <div class="review-section">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-user-line me-2"></i>Applicant Type</h6>
          <p><strong>Type:</strong> ${scope}</p>
          <p><strong>Employment Status:</strong> ${empType}</p>
        </div>

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-profile-line me-2"></i>Personal Details</h6>
          <div class="row">
            <div class="col-6"><p><strong>Title:</strong> ${formData.title || '-'}</p></div>
            <div class="col-6"><p><strong>Surname:</strong> ${formData.surname || '-'}</p></div>
            <div class="col-6"><p><strong>Name:</strong> ${formData.first_name || '-'}</p></div>
            <div class="col-6"><p><strong>Other:</strong> ${formData.other_names || '-'}</p></div>
            <div class="col-6"><p><strong>Date of Birth:</strong> ${formData.dob || '-'}</p></div>
            <div class="col-6"><p><strong>Marital Status:</strong> ${formData.marital_status || '-'}</p></div>
            <div class="col-6"><p><strong>Sex:</strong> ${formData.gender || '-'}</p></div>
            <div class="col-6"><p><strong>Nationality:</strong> ${formData.nationality || '-'}</p></div>
            ${personalExtra}
            <div class="col-6"><p><strong>Driver’s Licence No:</strong> ${formData.drivers_licence_no || '-'}</p></div>
            <div class="col-12"><p><strong>Residential Address:</strong> ${formData.address || '-'}</p></div>
            <div class="col-6"><p><strong>Phone:</strong> ${(formData.phone_country_code || '') + ' ' + (formData.phone || '-')}</p></div>
            <div class="col-6"><p><strong>Email:</strong> ${formData.email || '-'}</p></div>
          </div>
        </div>

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-briefcase-line me-2"></i>Employment</h6>
          <div class="row">
            <div class="col-6"><p><strong>Type of Medium:</strong> ${formData.medium_type || '-'}</p></div>
            <div class="col-6"><p><strong>Designation:</strong> ${formData.designation || '-'}</p></div>
            ${(formData.medium_type === 'Others') ? `<div class="col-12"><p><strong>Medium (Other):</strong> ${formData.medium_type_other || '-'}</p></div>` : ``}
            ${(formData.designation === 'Others') ? `<div class="col-12"><p><strong>Designation (Other):</strong> ${formData.designation_other || '-'}</p></div>` : ``}

            ${empType === 'employed' ? `
              <div class="col-6"><p><strong>Media Org:</strong> ${formData.media_org || '-'}</p></div>
              <div class="col-6"><p><strong>Physical Address:</strong> ${formData.media_org_address || '-'}</p></div>
              <div class="col-6"><p><strong>Phone:</strong> ${formData.media_org_phone || '-'}</p></div>
              <div class="col-6"><p><strong>Email:</strong> ${formData.media_org_email || '-'}</p></div>
              <div class="col-6"><p><strong>Editor/Publisher:</strong> ${formData.editor_publisher_name || '-'}</p></div>
              <div class="col-6"><p><strong>Immediate Supervisor:</strong> ${formData.immediate_supervisor || '-'}</p></div>
              <div class="col-6"><p><strong>String for Orgs:</strong> ${formData.string_for_orgs || '-'}</p></div>
              <div class="col-6"><p><strong>Details:</strong> ${formData.string_for_details || '-'}</p></div>
              ${employmentExtraForeign}
            ` : `
              <div class="col-12"><div class="alert alert-light border mb-0">Freelancer selected — organisation fields were not required.</div></div>
            `}
          </div>
        </div>

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-group-line me-2"></i>References</h6>
          <ul class="mb-0">
            ${refs.map((r,idx)=>`<li><strong>Reference ${idx+1}:</strong> ${r.name} — ${r.address} — ${r.phone}</li>`).join('')}
          </ul>
        </div>

        ${formData.journalist_scope === 'local' ? `
          <div class="review-section mt-4">
            <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-map-pin-line me-2"></i>Collection Office</h6>
            <p>${document.querySelector('select[name="collection_region"]')?.selectedOptions[0]?.text || '-'}</p>
          </div>
        ` : ''}

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-folder-open-line me-2"></i>Past Work Samples</h6>
          <div class="mt-2"><strong>Web Links:</strong>
            ${pastLinks.length ? `<ul class="mb-0">${pastLinks.map(r=>`<li><a href="${r.url}" target="_blank">${r.desc || r.url}</a></li>`).join('')}</ul>` : `<div class="text-muted">No web links provided.</div>`}
          </div>
        </div>

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-file-upload-line me-2"></i>Uploaded Documents</h6>
          ${filesHtml}
          <div class="mt-3">${photoPreviewHtml}</div>
        </div>

        <div class="review-section mt-4">
          <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="ri-shield-check-line me-2"></i>Declaration</h6>
          <p>I declare that all the information given above, to the best of my knowledge is true and complete.</p>
          <p><strong>Date:</strong> ${formData.declaration_date || '-'}</p>
        </div>

        <div class="alert alert-warning mt-4">
          <i class="ri-information-line me-2"></i>
          Please review all information carefully before submitting.
        </div>
      `;

      document.getElementById('ap3ReviewContent').innerHTML = reviewHtml;
      const modalEl = document.getElementById('ap3ReviewModal');
      const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
      if (modalEl && bs?.Modal) {
        const modal = bs.Modal.getOrCreateInstance ? bs.Modal.getOrCreateInstance(modalEl) : new bs.Modal(modalEl);
        modal.show();
      } else {
        alert('Review is ready, but the modal could not be opened. Please refresh the page and try again.');
      }
    }

    function showToast(message, type = 'success') {
      let container = document.getElementById('ap3-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'ap3-toast-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
      }
      const toast = document.createElement('div');
      toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} shadow`;
      toast.innerHTML = `<i class="ri-${type === 'success' ? 'check-line' : 'error-warning-line'} me-2"></i> ${message}`;
      container.appendChild(toast);
      setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
      }, 3000);
    }

    // Skip files already uploaded in a previous successful save so repeated
    // "Save Draft" clicks do not re-send unchanged files.
    const ap3SavedFileSigs = new Set();
    function ap3FileSig(input, file) {
      return input.name + '|' + file.name + '|' + file.size + '|' + file.lastModified;
    }

    // Files already stored on the server (from earlier draft saves). They are
    // not re-uploaded on Save Draft or Submit; the server keeps the stored
    // copies and promotes them to pending when the application is submitted.
    @php
      $ap3StoredDocs = ($draft ?? null)
        ? $draft->documents()->where('is_current', true)->get(['doc_type', 'original_name', 'size'])
        : [];
    @endphp
    const ap3StoredDocs = {!! json_encode($ap3StoredDocs) !!};
    function ap3IsStoredFile(inputName, file) {
      return ap3StoredDocs.some(d =>
        (d.doc_type === inputName || String(d.doc_type).startsWith(inputName + '_')) &&
        d.original_name === file.name &&
        Number(d.size) === file.size
      );
    }

    // Compress image client-side before upload (max 1.5MB, max 1920px dimension)
    function ap3CompressImage(file) {
      return new Promise(resolve => {
        if (!file.type.startsWith('image/')) { resolve(file); return; }
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement('canvas');
          let { width, height } = img;
          const maxDim = 1920;
          if (width > maxDim || height > maxDim) {
            const scale = maxDim / Math.max(width, height);
            width *= scale; height *= scale;
          }
          canvas.width = width; canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);
          canvas.toBlob(blob => resolve(blob || file), 'image/jpeg', 0.85);
        };
        img.onerror = () => resolve(file);
        img.src = URL.createObjectURL(file);
      });
    }

    async function saveDraft() {
      const textFormData = getFormData();
      textFormData.current_step = ap3CurrentStep;
      const scope = document.getElementById('ap3_scope').value;
      const region = document.querySelector('select[name="collection_region"]')?.value || 'harare';

      let response = null;

      try {
        ap3SaveDraftBtn.disabled = true;
        ap3SaveDraftBtn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';

        const fd = new FormData();
        fd.append('journalist_scope', scope || '');
        fd.append('collection_region', region);
        fd.append('form_data', JSON.stringify(textFormData));

        const pendingAp3FileSigs = [];
        const filesToCompress = [];
        document.querySelectorAll('#ap3Form input[type="file"]').forEach(input => {
          if (!visible(input)) return;
          if (input.files.length > 0) {
            const files = input.multiple ? Array.from(input.files) : [input.files[0]];
            files.forEach(file => {
              const sig = ap3FileSig(input, file);
              if (ap3SavedFileSigs.has(sig) || ap3IsStoredFile(input.name, file)) {
                ap3SavedFileSigs.add(sig);
                return;
              }
              // Compress images before upload
              if (file.type.startsWith('image/') && file.size > 500 * 1024) {
                filesToCompress.push({ input, file, sig });
              } else {
                fd.append(input.name, file);
                pendingAp3FileSigs.push(sig);
              }
            });
          }
        });

        // Compress images in parallel
        if (filesToCompress.length > 0) {
          const compressed = await Promise.all(filesToCompress.map(async ({ input, file, sig }) => {
            const blob = await ap3CompressImage(file);
            return { input, blob, sig, originalName: file.name };
          }));
          compressed.forEach(({ input, blob, sig, originalName }) => {
            fd.append(input.name, blob, originalName);
            pendingAp3FileSigs.push(sig);
          });
        }

        response = await fetch('{{ route("accreditation.saveDraft") }}', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': ap3CsrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: fd,
        });

        if (!response.ok) {
          let message = 'Server returned an error (' + response.status + ').';
          try {
            const errorJson = await response.json();
            if (errorJson.message) message += ' ' + errorJson.message;
          } catch (parseError) {}

          if (response.status === 413) {
            message = 'Your files are too large for the server to process. Please reduce file sizes and try again.';
          }

          throw new Error(message);
        }

        const result = await response.json();

        if (result.success) {
          pendingAp3FileSigs.forEach(sig => ap3SavedFileSigs.add(sig));
          showToast('Draft saved successfully! Ref: ' + (result.reference || ''), 'success');
        } else {
          showToast(result.message || 'Failed to save draft. Please try again.', 'error');
        }
      } catch (e) {
        console.error(e);
        showToast('An error occurred while saving the draft: ' + e.message, 'error');
      } finally {
        ap3SaveDraftBtn.disabled = false;
        ap3SaveDraftBtn.innerHTML = '<i class="ri-save-line"></i> Save Draft';
      }
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    let ap3PaymentData = null;

    function checkRequestSize(formData) {
      let totalSize = 0;
      for (let pair of formData.entries()) {
        if (pair[1] instanceof File) {
          totalSize += pair[1].size;
        } else {
          totalSize += (pair[1].length || 0);
        }
      }
      // Keep the browser payload below the server's 50 MB request limit,
      // while allowing the required supporting documents to be submitted together.
      const maxPostSize = 45 * 1024 * 1024;
      if (totalSize > maxPostSize) {
        alert(`The total size of your files (${(totalSize / 1024 / 1024).toFixed(2)}MB) exceeds the upload limit (45MB). Please reduce file sizes or upload fewer documents at a time.`);
        return false;
      }
      return true;
    }

    async function submitApplication() {
      if (window.ap3SubmissionPending) return;
      window.ap3SubmissionPending = true;
      const submitButton = document.getElementById('ap3SubmitCorrectionsBtn') || document.getElementById('ap3ProceedToPayBtn');
      const originalButtonHtml = submitButton?.innerHTML;
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Processing…';
      }
      let submissionSucceeded = false;
      console.log('=== SUBMIT FUNCTION CALLED ===');

      const textFormData = getFormData();
      const scope = document.getElementById('ap3_scope').value;
      const region = document.querySelector('select[name="collection_region"]')?.value || 'harare';

      console.log('Form data prepared:', { scope, region, formDataKeys: Object.keys(textFormData) });

      const submitData = new FormData();
      submitData.append('journalist_scope', scope);
      submitData.append('collection_region', region);
      submitData.append('form_data', JSON.stringify(textFormData));

      const fileInputs = document.querySelectorAll('#ap3Form input[type="file"]');
      console.log('Found', fileInputs.length, 'file inputs');

      const filesToCompress = [];
      fileInputs.forEach(input => {
        if (!visible(input)) return;
        if (input.files.length > 0) {
          const files = input.multiple ? Array.from(input.files) : [input.files[0]];
          files.forEach(file => {
            if (ap3IsStoredFile(input.name, file)) return;
            if (file.type.startsWith('image/') && file.size > 500 * 1024) {
              filesToCompress.push({ input, file });
            } else {
              submitData.append(input.name, file);
              console.log('Added file:', input.name, file.name);
            }
          });
        }
      });

      if (filesToCompress.length > 0) {
        const compressed = await Promise.all(filesToCompress.map(async ({ input, file }) => {
          const blob = await ap3CompressImage(file);
          return { input, blob, originalName: file.name };
        }));
        compressed.forEach(({ input, blob, originalName }) => {
          submitData.append(input.name, blob, originalName);
          console.log('Added compressed file:', input.name, originalName);
        });
      }

      if (ap3PaymentData) {
        for (const [key, value] of ap3PaymentData.entries()) {
          submitData.append('payment_' + key, value);
        }
      }

      if (!checkRequestSize(submitData)) {
        alert('Your files are too large. Please reduce file sizes.');
        window.ap3SubmissionPending = false;
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.removeAttribute('aria-busy');
          submitButton.innerHTML = originalButtonHtml;
        }
        return;
      }

      console.log('Starting submission...');

      try {
        const submitUrl = @json(isset($draft) && !$draft->is_draft && ($draft->status ?? null) === \App\Models\Application::CORRECTION_REQUESTED
          ? route('accreditation.applications.resubmit', $draft)
          : route('accreditation.submit'));

        console.log('Submit URL:', submitUrl);

        const response = await fetch(submitUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': ap3CsrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: submitData,
        });

        console.log('Response status:', response.status);

        const responseText = await response.text();
        console.log('Response text:', responseText.substring(0, 500));

        if (response.status === 413) {
            throw new Error('The files you uploaded are too large. Please ensure your files are within the allowed size limits and try again.');
        }

        let result;
        try {
            result = JSON.parse(responseText);
            console.log('Parsed result:', result);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Raw response:', responseText);
            throw new Error('Server returned an invalid response (' + response.status + ').');
        }

        if (response.ok && result.success) {
          submissionSucceeded = true;
          console.log('=== SUBMISSION SUCCESSFUL ===');

          localStorage.removeItem(ap3CurrentStepStorageKey);
          localStorage.removeItem(ap3MaxStepStorageKey);

          const appId = result.application_id || result.id;
          const appRef = result.reference;

          console.log('Application submitted:', { appId, appRef, fullResult: result });

          if (!appId) {
            alert('Application submitted successfully!\n\nReference: ' + appRef + '\n\nPlease pay from your dashboard.');
            window.location.href = "{{ route('accreditation.home') }}";
            return;
          }

          const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
          const reviewModal = bs?.Modal?.getInstance(document.getElementById('ap3ReviewModal'));
          if (reviewModal) {
            if (document.activeElement && document.getElementById('ap3ReviewModal')?.contains(document.activeElement)) {
              document.activeElement.blur();
            }
            reviewModal.hide();
          }

          window.ap3CurrentAppId = appId;
          window.ap3CurrentAppRef = appRef;

          console.log('Calling ap3ShowPaymentModal...');
          ap3ShowPaymentModal(appId, appRef);
        } else {
          console.error('Submission failed:', result);
          const msg = result.message || (result.errors ? Object.values(result.errors).flat().join('\n') : 'Please check all required fields.');
          alert('Submission failed: ' + msg);
        }
      } catch (error) {
        console.error('=== SUBMISSION ERROR ===');
        console.error(error);
        alert('An error occurred while submitting the application: ' + error.message);
      } finally {
        window.ap3SubmissionPending = false;
        if (!submissionSucceeded && submitButton) {
          submitButton.disabled = false;
          submitButton.removeAttribute('aria-busy');
          submitButton.innerHTML = originalButtonHtml;
        }
      }
    }

    // ===== Camera capture =====
    let ap3Stream = null;
    let ap3CapturedBlob = null;

    async function startCamera(){
      const video = document.getElementById('ap3CamVideo');
      ap3CapturedBlob = null;

      try {
        ap3Stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        video.srcObject = ap3Stream;
      } catch (e) {
        alert('Camera access failed. Please allow camera permission or upload a photo instead.');
        throw e;
      }
    }

    function stopCamera(){
      if (ap3Stream) {
        ap3Stream.getTracks().forEach(t => t.stop());
        ap3Stream = null;
      }
    }

    function blobToFile(blob, fileName){
      return new File([blob], fileName, { type: blob.type || 'image/png' });
    }

    function setFileInput(input, file){
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }

    ap3SaveDraftBtn.addEventListener('click', saveDraft);

    const proceedPayBtn = document.getElementById('ap3ProceedToPayBtn');
    if (proceedPayBtn) {
      proceedPayBtn.addEventListener('click', function() {
        submitApplication();
      });
    }

    // ===== MAIN INITIALIZATION =====
    console.log('Initializing AP3 form...');

    document.querySelectorAll('#new-application-page .app-type-card').forEach(card => {
      card.style.cursor = 'pointer';
      card.style.position = 'relative';
      card.style.zIndex = '10';
      card.removeEventListener('click', card._ap3ClickHandler);
      const handler = () => {
        console.log('Card clicked:', card.dataset.type);
        document.querySelectorAll('#new-application-page .app-type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const type = card.dataset.type;
        document.getElementById('ap3_scope').value = type;
        applyScopeVisibility(type);
        applyEmploymentVisibility();
      };
      card._ap3ClickHandler = handler;
      card.addEventListener('click', handler);
    });

    ap3PrevBtn.addEventListener('click', () => {
      ap3CurrentStep = Math.max(1, ap3CurrentStep - 1);
      ap3ShowStep(ap3CurrentStep);
        localStorage.setItem(ap3CurrentStepStorageKey, ap3CurrentStep);
    });

    ap3NextBtn.addEventListener('click', () => {
      if(!ap3ValidateStep(ap3CurrentStep)) return;
      if(ap3CurrentStep === 5){
        showReviewModal();
        return;
      }
      ap3CurrentStep = Math.min(5, ap3CurrentStep + 1);
      if (ap3CurrentStep > ap3MaxStepReached) {
        ap3MaxStepReached = ap3CurrentStep;
        localStorage.setItem(ap3MaxStepStorageKey, ap3MaxStepReached);
      }
      ap3ShowStep(ap3CurrentStep);
      localStorage.setItem(ap3CurrentStepStorageKey, ap3CurrentStep);
    });

    // Wizard progress is scoped to the signed-in applicant. Older versions
    // used global keys, which could make a different account open at step 2+.
    localStorage.removeItem('ap3_current_step');
    localStorage.removeItem('ap3_max_step');
    const ap3CurrentStepStorageKey = 'ap3_current_step_user_{{ (int) Auth::id() }}';
    const ap3MaxStepStorageKey = 'ap3_max_step_user_{{ (int) Auth::id() }}';
    const hasSavedAp3Draft = @json(isset($draft) && (bool) $draft);
    if (!hasSavedAp3Draft) {
      localStorage.removeItem(ap3CurrentStepStorageKey);
      localStorage.removeItem(ap3MaxStepStorageKey);
    }
    const savedStep = hasSavedAp3Draft ? localStorage.getItem(ap3CurrentStepStorageKey) : null;
    const savedDraftStep = @json(isset($draft) && $draft ? data_get($draft->form_data, 'current_step') : null);
    const initialStep = hasSavedAp3Draft
      ? Math.min(5, Math.max(1, parseInt(savedDraftStep || savedStep || '1', 10)))
      : 1;
    const savedMaxStep = localStorage.getItem(ap3MaxStepStorageKey);
    const parsedMaxStep = parseInt(savedMaxStep || String(initialStep), 10);
    ap3MaxStepReached = Math.min(5, Math.max(initialStep, Number.isFinite(parsedMaxStep) ? parsedMaxStep : initialStep));
    
    ap3ShowStep(initialStep);
    ap3CurrentStep = initialStep;

    setupUploads(document.getElementById('new-application-page'));
    buildReferees();
    zmcInitCountrySelector('ap3_nationality_wrapper');

    ap3Steps.forEach(stepEl => {
      stepEl.style.cursor = 'pointer';
      stepEl.addEventListener('click', () => {
        const targetStep = parseInt(stepEl.dataset.step, 10);
        if (targetStep <= ap3MaxStepReached) {
          ap3CurrentStep = targetStep;
          ap3ShowStep(ap3CurrentStep);
          localStorage.setItem(ap3CurrentStepStorageKey, ap3CurrentStep);
        } else {
          alert('Please complete the current step before navigating to this step.');
        }
      });
    });

    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('#new-application-page input[type="date"]').forEach(i => { if(!i.value) i.value = today; });

    const pastWorkRep = initRepeater('ap3PastWorkLinksRows','ap3AddPastWorkLinkRow', rowHtmlPastWorkLink);
    if (pastWorkRep) pastWorkRep.addRow();

    applyScopeVisibility(document.getElementById('ap3_scope').value || 'local');

    document.querySelectorAll('input[name="employment_type"]').forEach(r => r.addEventListener('change', applyEmploymentVisibility));
    applyEmploymentVisibility();

    document.querySelector('select[name="medium_type"]')?.addEventListener('change', applyOthersVisibility);
    document.querySelector('select[name="designation"]')?.addEventListener('change', applyOthersVisibility);
    applyOthersVisibility();

    const takeBtn = document.getElementById('ap3TakePhotoBtn');
    const captureBtn = document.getElementById('ap3CaptureBtn');
    const retakeBtn = document.getElementById('ap3RetakeBtn');
    const useBtn = document.getElementById('ap3UsePhotoBtn');
    const previewWrap = document.getElementById('ap3CapturePreview');
    const previewImg = document.getElementById('ap3PreviewImg');
    const video = document.getElementById('ap3CamVideo');
    const canvas = document.getElementById('ap3CamCanvas');

    takeBtn?.addEventListener('click', async () => {
      const modalEl = document.getElementById('ap3CameraModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      previewWrap.style.display = 'none';
      retakeBtn.style.display = 'none';
      captureBtn.style.display = '';
      await startCamera();
    });

    document.getElementById('ap3CameraCloseBtn')?.addEventListener('click', () => {
      stopCamera();
    });

    document.getElementById('ap3CameraModal')?.addEventListener('hidden.bs.modal', () => {
      stopCamera();
    });

    captureBtn?.addEventListener('click', () => {
      const w = video.videoWidth || 640;
      const h = video.videoHeight || 480;
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, w, h);

      canvas.toBlob((blob) => {
        if (!blob) return;
        ap3CapturedBlob = blob;
        previewImg.src = URL.createObjectURL(blob);
        previewWrap.style.display = '';
        retakeBtn.style.display = '';
        captureBtn.style.display = 'none';
      }, 'image/png', 0.92);
    });

    retakeBtn?.addEventListener('click', () => {
      previewWrap.style.display = 'none';
      retakeBtn.style.display = 'none';
      captureBtn.style.display = '';
      ap3CapturedBlob = null;
    });

    useBtn?.addEventListener('click', () => {
      if (!ap3CapturedBlob) return;
      const input = document.querySelector('input[name="passport_photo"]');
      const file = blobToFile(ap3CapturedBlob, 'passport_photo.png');
      setFileInput(input, file);
      const modal = bootstrap.Modal.getInstance(document.getElementById('ap3CameraModal'));
      modal.hide();
      stopCamera();
    });

    // Restore draft data if exists
    @if(isset($draft) && $draft)
      const draftData = @json($draft->form_data ?? []);
      if (draftData) {
        @if($draft->journalist_scope)
          const scopeCard = document.querySelector(`.app-type-card[data-type="{{ $draft->journalist_scope }}"]`);
          if (scopeCard) scopeCard.click();
        @endif

        Object.keys(draftData).forEach(key => {
          const field = document.querySelector(`[name="${key}"]`);
          if (!field) return;
          if (field.type === 'radio') {
            const radio = document.querySelector(`[name="${key}"][value="${draftData[key]}"]`);
            if (radio) radio.checked = true;
          } else {
            field.value = draftData[key];
          }
        });

        applyEmploymentVisibility();
        applyOthersVisibility();
      }

      const existingDocs = @json($draft->documents ?? []);
      const deleteBaseUrl = @json(route('accreditation.documents.delete', ['document' => '__ID__']));
      if (existingDocs && existingDocs.length) {
        existingDocs.forEach(doc => {
          const container = document.querySelector(`.uploaded-files[data-doc-type="${doc.doc_type}"]`);
          if (!container) return;
          const docName = doc.original_name || doc.doc_type.replace(/_/g, ' ');
          const docSize = doc.size ? ((doc.size / 1024).toFixed(1) + ' KB') : '';
          const deleteUrl = deleteBaseUrl.replace('__ID__', doc.id);
          const isImage = (doc.mime || '').startsWith('image/');
          const iconClass = isImage ? 'ri-image-line' : 'ri-file-text-line';
          const el = document.createElement('div');
          el.className = 'existing-doc-item';
          el.innerHTML = `
            <div class="doc-info">
              <i class="${iconClass}"></i>
              <div>
                <div class="doc-name">${docName}</div>
                <div class="doc-meta">${docSize}</div>
              </div>
            </div>
            <button type="button" class="btn-remove-doc" onclick="zmcRemoveDocument(${doc.id}, this, '${deleteUrl}')">
              <i class="ri-delete-bin-line"></i> Remove
            </button>
          `;
          container.appendChild(el);

          const fileInput = container.closest('.af-field')?.querySelector('input[type="file"]');
          if (fileInput) fileInput.removeAttribute('required');
        });
      }
    @endif

    // ==================== PAYMENT MODAL FUNCTIONS ====================
    const paymentInitiateUrl = '{{ route("paynow.initiate", ["applicationId" => "__APP_ID__"]) }}';
    const paymentProofUploadUrl = '{{ route("payments.upload_proof", ["application" => "__APP_ID__"]) }}';

    if (typeof ap3SelectedCurrency === 'undefined') {
      window.ap3SelectedCurrency = 'USD';
    }

    window.ap3ShowPaymentModal = function(appId, appRef) {
      console.log('Opening payment modal with:', { appId, appRef });
      const metaEl = document.getElementById('ap3_payment_meta');
      const infoEl = document.getElementById('ap3_payment_ref_info');
      if (metaEl) metaEl.textContent = `Application: ${appRef}`;
      if (infoEl) infoEl.textContent = `Your application ${appRef} has been submitted. Application fee is $${ap3AccreditationFeeUSD.toFixed(2)} USD. Please proceed with payment.`;
      const modal = document.getElementById('ap3PaymentModal');
      if (modal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
      }
    };

    window.ap3SelectCurrency = function(currency) {
      window.ap3SelectedCurrency = currency;
      document.querySelectorAll('.currency-option').forEach(opt => opt.classList.remove('selected'));
      document.querySelector(`.currency-option[data-currency="${currency}"]`)?.classList.add('selected');
      const radioEl = document.getElementById(`ap3_currency_${currency.toLowerCase()}`);
      if (radioEl) radioEl.checked = true;
      const rbzSection = document.getElementById('ap3_rbz_section');
      const btnText = document.getElementById('ap3_paynow_btn_text');
      if (currency === 'ZWG') {
        rbzSection?.classList.remove('d-none');
        if (btnText) btnText.textContent = 'Open PayNow (ZWG)';
        ap3LoadCurrentRbzRate();
      } else {
        rbzSection?.classList.add('d-none');
        if (btnText) btnText.textContent = 'Open PayNow (USD)';
      }
    };

    document.getElementById('ap3_rbz_rate_input')?.addEventListener('input', function() {
      const rate = parseFloat(this.value) || 0;
      const zwgAmount = rate * ap3AccreditationFeeUSD;
      const el = document.getElementById('ap3_calc_zwg_amount');
      if (el) el.textContent = `ZWG ${zwgAmount.toFixed(2)}`;
    });

    async function ap3LoadCurrentRbzRate() {
      const input = document.getElementById('ap3_rbz_rate_input');
      const dateLabel = document.getElementById('ap3_rbz_rate_effective_date');
      if (!input || input.dataset.loading === 'true') return;
      input.dataset.loading = 'true';
      input.value = '';
      try {
        const response = await fetch(@json(route('paynow.rbz-rate')), { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.available) throw new Error(data.message || 'Rate is unavailable.');
        input.value = data.rate;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        if (dateLabel) dateLabel.textContent = data.effective_date ? `Effective ${data.effective_date} · maintained by Accounts` : 'Maintained by Accounts';
      } catch (error) {
        if (dateLabel) dateLabel.textContent = error.message || 'The exchange rate is unavailable. Contact Accounts.';
      } finally {
        delete input.dataset.loading;
      }
    }

    document.getElementById('ap3BtnOpenPayNow')?.addEventListener('click', async function() {
      const currency = window.ap3SelectedCurrency || 'USD';
      const appId = window.ap3CurrentAppId;

      if (!appId) {
        alert('Application ID not found. Please refresh and try again.');
        return;
      }

      if (currency === 'ZWG') {
        const rate = parseFloat(document.getElementById('ap3_rbz_rate_input')?.value) || 0;
        if (rate <= 0) {
          alert('The current RBZ exchange rate is unavailable. Please contact Accounts before proceeding with ZWG payment.');
          document.getElementById('ap3_rbz_rate_input')?.focus();
          return;
        }
      }

      const btn = this;
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Initiating payment...';

      try {
        const url = paymentInitiateUrl.replace('__APP_ID__', appId);
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': ap3CsrfToken
          },
          body: JSON.stringify({
            currency: currency,
            ...(currency === 'ZWG' ? { rbz_rate: document.getElementById('ap3_rbz_rate_input')?.value } : {})
          })
        });

        const data = await response.json();

        if (response.ok && data.success && data.redirect_url) {
          document.getElementById('ap3_paynow_step1')?.classList.add('d-none');
          document.getElementById('ap3_paynow_redirecting')?.classList.remove('d-none');
          window.location.href = data.redirect_url;
        } else {
          alert(data.message || 'Failed to initiate payment. Please try again.');
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      } catch (error) {
        console.error('Payment initiation error:', error);
        alert('Network error. Please check your connection and try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });

    document.getElementById('ap3ProofForm')?.addEventListener('submit', async function(e) {
      e.preventDefault();
      const appId = window.ap3CurrentAppId;
      if (!appId) {
        alert('Application ID not found. Please refresh and try again.');
        return;
      }

      const btn = document.getElementById('ap3BtnSubmitProof');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
      }

      try {
        const formData = new FormData(this);
        const url = paymentProofUploadUrl.replace('__APP_ID__', appId);
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': ap3CsrfToken
          },
          body: formData
        });

        const data = await response.json();

        if (response.ok && (data.success || data.ok)) {
          alert(data.message || 'Payment proof uploaded successfully! Your payment will be verified by Accounts.');
          window.location.href = "{{ route('accreditation.home') }}";
        } else {
          alert(data.message || 'Failed to upload proof. Please try again.');
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-upload-cloud-2-line"></i> Submit Payment Proof';
          }
        }
      } catch (error) {
        console.error('Proof upload error:', error);
        alert('Network error. Please check your connection and try again.');
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="ri-upload-cloud-2-line"></i> Submit Payment Proof';
        }
      }
    });

    // Close payment modal - redirect to dashboard
    document.getElementById('ap3PaymentClose')?.addEventListener('click', function() {
      window.location.href = "{{ route('accreditation.home') }}";
    });

  }); // end DOMContentLoaded
</script>
@endpush
