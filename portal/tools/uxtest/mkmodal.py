import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
import re, sys
S = WORK
root = PORTAL
layout = open(root + '/resources/views/layouts/portal.blade.php', encoding='utf-8').read()
css = "\n".join(b for b in re.findall(r'<style[^>]*>(.*?)</style>', layout, re.S) if '{{' not in b and '@if' not in b and '@php' not in b)
uxcss = open(root + '/public/css/zmc-ux.css', encoding='utf-8').read()
uxjs = open(root + '/public/js/zmc-ux.js', encoding='utf-8').read()
dash = open(root + '/resources/views/portal/accreditation/dashboard.blade.php', encoding='utf-8').read()
i = dash.index('<div class="modal fade" id="welcomeRequirementsModal"')
j = dash.index('</div>\n</div>\n', dash.index('Welcome, Practitioner'))  # rough end; refine below
# take the modal block up to the next top-level "<script" or "@endsection"/"@push" after it
tail = dash[i:]
m = re.search(r'\n(<script|@push|@endsection|@endpush)', tail)
modal = tail[: m.start() if m else 6000]
modal = re.sub(r'@[a-zA-Z]+\([^)]*\)', '', modal)           # drop blade directives
modal = re.sub(r'\{\{.*?\}\}', '', modal, flags=re.S)       # drop echo tags
modal = re.sub(r'\{\{--.*?--\}\}', '', modal, flags=re.S)

body = '''
<div class="app"><aside class="vertical-menu"><div class="navbar-brand-box" style="padding:14px;color:#fff;font-weight:700">Media Practitioner Accreditation</div>
<ul class="sidebar-menu"><li class="active"><a href="/portal/accreditation/dashboard"><i class="ri-home-line"></i> <span>Dashboard</span></a></li><li><a href="/portal/accreditation/new"><i class="ri-file-add-line"></i> <span>New Accreditation</span></a></li></ul></aside>
<main class="main"><header class="topbar"><div class="topbar-row--main"><div class="topbar-left"><button class="topbar-toggle" type="button"><i class="ri-menu-2-line"></i></button></div></div></header>
<section class="content" style="padding:20px">
<div class="row g-3"><div class="col-md-3"><div class="bg-white rounded-4 border p-3 h-100">Drafts <b>0</b></div></div><div class="col-md-3"><div class="bg-white rounded-4 border p-3 h-100">Active <b>0</b></div></div></div>
<button class="btn btn-primary mt-3" id="opener" data-bs-toggle="modal" data-bs-target="#welcomeRequirementsModal">Open</button>
''' + modal + '''
</section></main></div>
<pre id="results" style="display:none"></pre>
'''

test = r'''
<script>
const out = {};
window.addEventListener('load', async () => {
  const sleep = ms => new Promise(r => setTimeout(r, ms));
  await sleep(150);                                   // open EARLY, while entry animations are still running (like the welcome modal on load)
  const m = document.getElementById('welcomeRequirementsModal');
  const inst = bootstrap.Modal.getOrCreateInstance(m); inst.show();
  await sleep(1600);                                  // let fade + all entry animations finish
  const dlg = m.querySelector('.modal-content').getBoundingClientRect();
  const cx = dlg.left + dlg.width / 2, cy = Math.min(dlg.top + 60, innerHeight - 10);
  const top = document.elementFromPoint(cx, cy);
  const closeBtn = m.querySelector('[data-bs-dismiss="modal"]');
  const cb = closeBtn ? closeBtn.getBoundingClientRect() : null;
  const topAtClose = cb ? document.elementFromPoint(cb.left + cb.width / 2, cb.top + cb.height / 2) : null;
  const bd = document.querySelector('.modal-backdrop');
  const z = el => el ? getComputedStyle(el).zIndex : null;
  const stackingAncestors = [];
  for (let e = m.parentElement; e && e !== document.documentElement; e = e.parentElement) {
    const cs = getComputedStyle(e);
    if (cs.transform !== 'none' || (cs.opacity !== '1') || cs.willChange !== 'auto' || cs.filter !== 'none' || (cs.position !== 'static' && cs.zIndex !== 'auto') || cs.isolation === 'isolate')
      stackingAncestors.push(e.tagName.toLowerCase() + '.' + String(e.className).split(' ')[0] + ' [transform=' + cs.transform + ' opacity=' + cs.opacity + ' z=' + cs.zIndex + ' anim=' + cs.animationName + ']');
  }
  out.modalShown = m.classList.contains('show');
  out.modalZ = z(m); out.backdropZ = z(bd);
  out.topAtModalCenterIsInsideModal = !!(top && m.contains(top));
  out.closeButtonClickable = !!(topAtClose && (topAtClose === closeBtn || closeBtn.contains(topAtClose)));
  out.modalRect = [Math.round(dlg.left), Math.round(dlg.top), Math.round(dlg.width), Math.round(dlg.height)];
  out.viewport = [innerWidth, innerHeight];
  out.stackingAncestors = stackingAncestors;
  document.getElementById('results').textContent = JSON.stringify(out);
  document.title = 'DONE';
});
</script>
'''

def page(extra_css=""):
    return f'''<!doctype html><html data-bs-theme="dark"><head><meta charset=utf-8><title>harness</title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel=stylesheet><link href="/vendor/remixicon/remixicon.css" rel=stylesheet>
<style>{css}</style><style>{uxcss}</style><style>{extra_css}</style>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script></head>
<body>{body}<script>{uxjs}</script>{test}</body></html>'''

open(S + "/srv/modal_test.html", "w", encoding="utf-8").write(page())
print("modal chars", len(modal))
