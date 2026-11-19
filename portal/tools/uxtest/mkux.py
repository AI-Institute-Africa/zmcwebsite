import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
import re, sys
S = WORK
root = PORTAL
layout = open(root + '/resources/views/layouts/portal.blade.php', encoding='utf-8').read()
blocks = [b for b in re.findall(r'<style[^>]*>(.*?)</style>', layout, re.S) if '{{' not in b and '@if' not in b and '@php' not in b]
layout_css = "\n".join(blocks)
uxcss = open(root + '/public/css/zmc-ux.css', encoding='utf-8').read()
uxjs = open(root + '/public/js/zmc-ux.js', encoding='utf-8').read()
vend = "/vendor"

rows = "".join(
    '<tr><td>APP-%03d</td><td>%s</td><td>%d</td><td>2026-11-%02d</td><td><span class="zmc-chip zmc-chip--warning">Under Officer Review</span></td><td><a class="btn btn-sm btn-light" href="#"><i class="ri-eye-line"></i></a></td></tr>'
    % (i, n, v, d) for i, (n, v, d) in enumerate([("Zulu", 30, 5), ("Alpha", 4, 18), ("Mike", 150, 2), ("Delta", 22, 25), ("Bravo", 9, 11), ("Echo", 1000, 1), ("Charlie", 55, 9), ("Foxtrot", 7, 30)], start=1))

body = '''
<div class="app"><aside class="vertical-menu"><div class="navbar-brand-box" style="padding:14px;color:#fff;font-weight:700">Registrar</div>
<ul class="sidebar-menu">
<li class="menu-title">Applications</li>
<li class="active"><a href="/staff/registrar/applications"><i class="ri-file-list-3-line"></i> <span>All Applications</span></a></li>
<li><a href="/staff/registrar/processed"><i class="ri-stack-line"></i> <span>Processed</span></a></li>
<li><a href="/staff/registrar/payment-oversight"><i class="ri-eye-line"></i> <span>Payment Statuses</span></a></li>
<li class="menu-title">Reports</li>
<li><a href="/staff/reports"><i class="ri-bar-chart-line"></i> <span>Operational Reports</span></a></li>
<li><a href="/staff/records"><i class="ri-id-card-line"></i> <span>Accredited</span></a></li>
</ul></aside>
<main class="main">
<header class="topbar"><div class="topbar-row--main"><div class="topbar-left"><button class="topbar-toggle" type="button"><i class="ri-menu-2-line"></i></button>
<form class="zmc-search" action="/staff/search" method="GET" role="search"><i class="ri-search-line"></i><input id="zmcGlobalSearch" type="search" name="q" placeholder="Search by reference or name"><kbd>/</kbd></form></div></div></header>
<section class="content staff-page-surface" style="padding:20px">
<div class="alert alert-success" id="flash">Application saved successfully.</div>
<div class="alert alert-danger" id="err">Something failed.</div>

<section class="zmc-queue" aria-label="My queue"><div class="zmc-queue__intro"><div class="zmc-queue__title"><i class="ri-inbox-line"></i> My queue</div><div class="zmc-queue__sub">Applications waiting for registrar decision</div></div>
<a class="zmc-queue__stat" href="#"><span class="zmc-queue__num">12</span><span class="zmc-queue__lbl">Waiting on you</span></a>
<a class="zmc-queue__stat is-alert" href="#"><span class="zmc-queue__num">3</span><span class="zmc-queue__lbl">Overdue (past SLA)</span></a>
<div class="zmc-queue__stat"><span class="zmc-queue__num">5</span><span class="zmc-queue__lbl">Updated in last 24 h</span></div>
<div class="zmc-queue__stat"><span class="zmc-queue__num">4 days</span><span class="zmc-queue__lbl">Oldest item waiting</span></div></section>

<div class="card mb-3"><div class="table-responsive"><table class="table table-hover mb-0" id="t1"><thead><tr><th>Reference</th><th>Applicant</th><th>Amount</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>''' + rows + '''</tbody></table></div></div>

<div class="card mb-3"><table class="table" id="t2"><thead><tr><th>Name</th><th>Status</th></tr></thead><tbody><tr><td colspan="2">No pending renewals.</td></tr></tbody></table></div>

<form id="delform" method="POST" action="/staff/things/5/delete" target="ifr" class="mb-2"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-danger btn-sm" id="delbtn" type="submit">Delete record</button></form>
<form id="saveform" method="POST" action="/staff/save" target="ifr" class="mb-2"><button class="btn btn-primary btn-sm" id="savebtn" type="submit" data-loading="Saving…">Save</button></form>
<iframe name="ifr" id="ifr" style="display:none"></iframe>

<form id="app" class="mb-3" onsubmit="return false"><div class="step-content active">
<div class="mb-3"><label for="fn">Full name</label><input id="fn" name="full_name" class="form-control" required></div>
<div class="mb-3"><label for="em">Email</label><input id="em" name="email" type="email" class="form-control" required></div>
<div class="mb-3"><label>Notes</label><input name="notes" class="form-control"></div><div class="mb-3"><label>ID number</label><input name="national_id" class="form-control" value="63-123456X78"></div>
<button type="button" class="btn btn-light" id="iconbtn"><i class="ri-printer-line"></i></button>
</div></form>
</section></main></div>
<pre id="results" style="display:none"></pre>
'''

test = r'''
<script>
window.__results = [];
const R = (n, ok, d) => window.__results.push((ok ? "PASS " : "FAIL ") + n + (d ? " :: " + d : ""));
const sleep = ms => new Promise(r => setTimeout(r, ms));
(async () => { try {
  // seed storage BEFORE the ux script boots so recents/pins/draft paths are exercised
  await sleep(600);
  const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];

  // toasts / flash
  R("flash success alert became a toast", !$("#flash") && $$(".zmc-toast--success").length === 1);
  R("danger alert keeps inline + role=alert", $("#err") && $("#err").getAttribute("role") === "alert");

  // a11y
  R("skip link added", !!$(".zmc-skip") && $(".content").id === "main-content");
  R("icon-only button labelled", $("#iconbtn").getAttribute("aria-label") === "Printer");

  // table: enhanced
  const t1 = $("#t1");
  R("table enhanced + scope=col", t1.classList.contains("zmc-table") && t1.querySelector("th").getAttribute("scope") === "col");
  R("data-label for phone layout", t1.querySelector("td").getAttribute("data-label") === "Reference" && t1.classList.contains("zmc-stack"));
  R("toolbar (filter/CSV/print) present", !!document.querySelector(".zmc-tabletools") && !!document.querySelector('[data-act="csv"]') && !!document.querySelector('[data-act="print"]'));
  R("count text", /Showing 8 of 8/.test(document.querySelector(".zmc-tabletools__count").textContent), document.querySelector(".zmc-tabletools__count").textContent);

  const col = i => [...t1.tBodies[0].rows].filter(r => !r.hidden && r.cells.length > 1).map(r => r.cells[i].textContent.trim());
  const th = i => t1.tHead.rows[0].cells[i];
  th(1).click();                       // sort applicant asc
  R("sort text asc", col(1).join() === ["Alpha","Bravo","Charlie","Delta","Echo","Foxtrot","Mike","Zulu"].join(), col(1).join());
  R("aria-sort ascending", th(1).getAttribute("aria-sort") === "ascending");
  th(1).click();
  R("sort text desc", col(1)[0] === "Zulu" && th(1).getAttribute("aria-sort") === "descending");
  th(2).click();                       // numeric asc
  R("sort numeric (not alphabetical)", col(2).join() === ["4","7","9","22","30","55","150","1000"].join(), col(2).join());
  th(3).click();                       // date asc
  R("sort dates", col(3)[0] === "2026-11-01" && col(3)[7] === "2026-11-30", col(3).join());
  R("Actions column not sortable", !th(5).classList.contains("zmc-sortable"));

  const fin = $(".zmc-tabletools input");
  fin.value = "charl"; fin.dispatchEvent(new Event("input", { bubbles: true }));
  R("filter narrows rows", col(1).join() === "Charlie", col(1).join());
  fin.value = "zzzz"; fin.dispatchEvent(new Event("input", { bubbles: true }));
  R("filter shows 'no match' row", !!t1.querySelector(".zmc-nomatch"));
  fin.value = ""; fin.dispatchEvent(new Event("input", { bubbles: true }));
  R("clearing filter restores rows + removes no-match", col(1).length === 8 && !t1.querySelector(".zmc-nomatch"));

  // CSV content
  let csv = "";
  const origBlob = window.Blob; window.Blob = function (parts) { csv = parts.join(""); return new origBlob(parts); };
  const origClick = HTMLAnchorElement.prototype.click; HTMLAnchorElement.prototype.click = function () {};
  document.querySelector('[data-act="csv"]').click();
  HTMLAnchorElement.prototype.click = origClick; window.Blob = origBlob;
  const lines = csv.replace("\ufeff", "").split("\r\n");
  R("CSV header excludes Actions", lines[0] === "Reference,Applicant,Amount,Date,Status", lines[0]);
  R("CSV has all 8 rows", lines.length === 9, "lines=" + lines.length);

  // empty table
  R("empty state styled", !!$("#t2 td.zmc-empty") && !!$("#t2 .zmc-empty__icon"));

  // confirm dialog
  const submitted = [];
  document.getElementById("ifr").addEventListener("load", () => submitted.push("iframe-load"));
  $("#delbtn").click();
  await sleep(700);
  R("DELETE form opens confirm modal", !!$("#zmcConfirm.show"), $("#zmcConfirmBody") && $("#zmcConfirmBody").textContent.slice(0, 50));
  $("#zmcConfirm [data-bs-dismiss=modal].btn-light").click();
  await sleep(600);
  R("Cancel does not submit", !$("#delform").dataset.zmcConfirmed);

  // loading button
  $("#savebtn").click();
  await sleep(100);
  R("submit button shows loading + disabled", $("#savebtn").disabled && /Saving/.test($("#savebtn").textContent) && !!$("#savebtn .spinner-border"));

  // sidebar
  R("count badge on matching link", !!$('.sidebar-menu a[href="/staff/registrar/applications"] .zmc-badge') && $('.sidebar-menu a[href="/staff/registrar/applications"] .zmc-badge').textContent === "12");
  const titles = $$(".sidebar-menu li.menu-title.zmc-collapsible");
  R("group titles collapsible", titles.length === 2, "n=" + titles.length);
  const rep = titles.find(t => t.textContent.trim() === "Reports");
  rep.click();
  R("collapse hides members", rep.nextElementSibling.hidden === true && rep.getAttribute("aria-expanded") === "false");
  R("group with active link stays open", titles[0].getAttribute("aria-expanded") === "true");
  rep.click();
  R("expand restores members", rep.nextElementSibling.hidden === false);
  const pin = $('.sidebar-menu a[href="/staff/records"]').parentElement.querySelector(".zmc-pin");
  R("pin button present with aria-pressed", !!pin && pin.getAttribute("aria-pressed") === "false");
  pin.click();
  R("pin saved to storage", JSON.parse(localStorage.getItem("zmc.pins") || "[]").length === 1);

  // inline validation
  const fn = $("#fn");
  fn.dispatchEvent(new Event("focusout", { bubbles: true }));
  fn.focus(); fn.blur();
  await sleep(100);
  R("required label gets marker class", $("label[for=fn]").classList.contains("zmc-required"));
  const em = $("#em"); em.value = "not-an-email"; em.dispatchEvent(new Event("focusout", { bubbles: true }));
  await sleep(50);
  R("invalid email shows inline message + aria", em.classList.contains("is-invalid") && em.getAttribute("aria-invalid") === "true" && !!em.getAttribute("aria-describedby") && !!document.getElementById(em.getAttribute("aria-describedby")), (document.getElementById(em.getAttribute("aria-describedby")) || {}).textContent);
  em.value = "a@b.co"; em.dispatchEvent(new Event("input", { bubbles: true }));
  R("message clears when fixed", !em.classList.contains("is-invalid") && !document.querySelector('.zmc-fielderr[data-for="em"]'));

  // draft autosave
  fn.value = "Jane Moyo"; fn.dispatchEvent(new Event("input", { bubbles: true }));
  await sleep(1100);
  const draft = JSON.parse(localStorage.getItem("zmc.draft:" + location.pathname) || "null");
  R("draft autosaved", draft && draft.data.full_name === "Jane Moyo", JSON.stringify(draft && draft.data));
  R("draft status shown", /Draft saved/.test($("#zmcDraftStatus span").textContent), $("#zmcDraftStatus span").textContent);
  R("sensitive identifier field not stored", draft && !("national_id" in draft.data));
  $(".zmc-draft__clear").click();
  R("Clear button removes draft", localStorage.getItem("zmc.draft:" + location.pathname) === null);
  R("password/_token not stored", draft && !("password" in draft.data) && !("_token" in draft.data));

  // search shortcut
  document.activeElement.blur();
  document.dispatchEvent(new KeyboardEvent("keydown", { key: "/", bubbles: true }));
  R("'/' focuses search", document.activeElement && document.activeElement.id === "zmcGlobalSearch");

  document.getElementById("results").textContent = JSON.stringify(window.__results);
  document.title = "DONE";
} catch (e) { R("EXCEPTION", false, e.message + " @ " + String(e.stack || "").slice(0, 300)); document.getElementById("results").textContent = JSON.stringify(window.__results); document.title = "ERR"; } })();
</script>
'''

CONTRAST = r'''
<script>
function lum(c){const a=c.map(v=>{v/=255;return v<=.03928?v/12.92:Math.pow((v+.055)/1.055,2.4)});return .2126*a[0]+.7152*a[1]+.0722*a[2];}
function parse(s){const m=s.match(/rgba?\(([^)]+)\)/);if(!m)return null;const p=m[1].split(/[ ,\/]+/).map(Number);return {r:p[0],g:p[1],b:p[2],a:p.length>3?p[3]:1};}
function bgOf(el){if(el.closest('.vertical-menu'))return {r:24,g:24,b:24};let layers=[];while(el){const c=parse(getComputedStyle(el).backgroundColor);if(c&&c.a>0){layers.push(c);if(c.a>=1)break;}el=el.parentElement;}
 const dark=document.documentElement.getAttribute('data-bs-theme')==='dark';let out=dark?{r:15,g:23,b:42}:{r:247,g:249,b:252};
 if(layers.length&&layers[layers.length-1].a>=1) out={r:0,g:0,b:0};
 for(let i=layers.length-1;i>=0;i--){const l=layers[i];out={r:l.r*l.a+out.r*(1-l.a),g:l.g*l.a+out.g*(1-l.a),b:l.b*l.a+out.b*(1-l.a)};}return out;}
const res=[];
document.querySelectorAll('.content *').forEach(el=>{
  const own=[...el.childNodes].some(n=>n.nodeType===3&&n.textContent.trim()); if(!own)return;
  if(el.closest('[hidden]'))return;
  const cs=getComputedStyle(el);const fg=parse(cs.color);const bg=bgOf(el);
  const L1=lum([fg.r,fg.g,fg.b]),L2=lum([bg.r,bg.g,bg.b]);const ratio=(Math.max(L1,L2)+.05)/(Math.min(L1,L2)+.05);
  res.push({t:el.textContent.trim().slice(0,30),cls:(el.className||'').toString().slice(0,40),ratio:+ratio.toFixed(2)});
});
document.getElementById('results').textContent=JSON.stringify(res);
</script>'''

def page(theme, extra_test=True):
    return f'''<!doctype html><html data-bs-theme="{theme}"><head><meta charset=utf-8><title>harness</title>
<link href="{vend}/bootstrap/bootstrap.min.css" rel=stylesheet><link href="{vend}/remixicon/remixicon.css" rel=stylesheet>
<style>{layout_css}</style><style>{uxcss}</style>
<script src="{vend}/bootstrap/bootstrap.bundle.min.js"></script>
<script>window.ZMC_BADGES = {{"/staff/registrar/applications": 12}};</script></head>
<body>{body}
<script>{uxjs}</script>
{test if extra_test else ""}
</body></html>'''

open(S + "/srv/ux_light.html", "w", encoding="utf-8").write(page("light"))
open(S + "/srv/ux_dark.html", "w", encoding="utf-8").write(page("dark", False).replace("</body>", CONTRAST + "</body>"))
open(S + "/srv/ux_light_static.html", "w", encoding="utf-8").write(page("light", False).replace("</body>", CONTRAST + "</body>"))
print("ok")
