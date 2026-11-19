import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
"""Render selected views to screenshots (light/dark desktop + phone) and build contact sheets.
usage: shots.py render <label> <view-substring-or-index,...>   |   shots.py sheet <label> <count>"""
import re, sys, os, json, subprocess, glob
S = WORK
root = PORTAL
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
manifest = json.load(open(S + "/srv/frag/manifest.json"))

def build_page(theme):
    layout = open(root + '/resources/views/layouts/portal.blade.php', encoding='utf-8').read()
    _blocks = [b for b in re.findall(r'<style[^>]*>(.*?)</style>', layout, re.S) if '{{' not in b and '@if' not in b and '@php' not in b]
    css = "\n".join(b for b in _blocks if 'LIGHT MODE CONTRAST FIXES' not in b)
    uiblock = "\n".join(b for b in _blocks if 'LIGHT MODE CONTRAST FIXES' in b)
    uxcss =open(root + '/public/css/zmc-ux.css', encoding='utf-8').read()
    darkcss = open(root + '/public/css/zmc-dark.css', encoding='utf-8').read()
    uxjs = open(root + '/public/js/zmc-ux.js', encoding='utf-8').read()
    page = '''<!doctype html><html data-bs-theme="__T__"><head><meta charset=utf-8><title>shot</title><meta name="viewport" content="width=device-width, initial-scale=1">
<link href="/vendor/bootstrap/bootstrap.min.css" rel=stylesheet><link href="/vendor/remixicon/remixicon.css" rel=stylesheet>
<style>__CSS__</style><style>__UX__</style><style>__DARK__</style><style>__UIBLOCK__</style><script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script></head>
<body><div class="app"><aside class="vertical-menu"><div class="navbar-brand-box" style="padding:14px;color:#fff;font-weight:700">ZMC Portal</div><ul class="sidebar-menu"><li class="menu-title">Section</li><li class="active"><a href="/staff/x"><i class="ri-home-line"></i> <span>Dashboard</span></a></li><li><a href="/staff/y"><i class="ri-file-list-3-line"></i> <span>Applications</span></a></li></ul></aside>
<main class="main"><header class="topbar"><div class="topbar-row--main"><div class="topbar-left"><button class="topbar-toggle"><i class="ri-menu-2-line"></i></button></div></div></header><section class="content staff-page-surface" id="stage"></section></main></div>
<script>__JS__</script>
<script>(async()=>{const i=new URLSearchParams(location.search).get('i');if(i===null){document.title='READY';return;}const h=await (await fetch('/frag/'+i+'.html')).text();const st=document.getElementById('stage');st.innerHTML=h;await new Promise(r=>setTimeout(r,900));if(window.zmcContrastGuard)window.zmcContrastGuard(st);document.title='READY';})();</script></body></html>'''
    page = page.replace("__T__", theme).replace("__CSS__", css).replace("__UX__", uxcss).replace("__DARK__", darkcss).replace("__UIBLOCK__", uiblock).replace("__JS__", uxjs)
    open(f"{S}/srv/shot_{theme}.html", "w", encoding="utf-8").write(page)

def pick(spec):
    idx = []
    for part in spec.split(","):
        part = part.strip()
        if part.isdigit(): idx.append(int(part))
        else: idx += [i for i, v in enumerate(manifest) if part in v]
    return list(dict.fromkeys(idx))

def render(label, spec):
    os.makedirs(f"{S}/shots/{label}", exist_ok=True)
    for t in ("light", "dark"): build_page(t)
    for i in pick(spec):
        for t, w, tag in (("light", 1366, "L"), ("dark", 1366, "D"), ("light", 500, "P")):
            out = f"{S}/shots/{label}/{i}_{tag}.png"
            subprocess.run([CHROME, "--headless=new", "--disable-gpu", f"--window-size={w},1000", "--virtual-time-budget=4000", f"--screenshot={out}",
                            f"http://127.0.0.1:8767/shot_{t}.html?i={i}"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=90)
        print("shot", i, manifest[i])

def sheet(label):
    from PIL import Image, ImageDraw
    files = sorted(glob.glob(f"{S}/shots/{label}/*_L.png"), key=lambda p: int(os.path.basename(p).split('_')[0]))
    for n, i0 in enumerate(range(0, len(files), 4)):
        group = files[i0:i0 + 4]
        W, H = 440, 300
        sh = Image.new("RGB", (W * 3, H * len(group) + 0), (30, 30, 30))
        for r, lp in enumerate(group):
            idx = int(os.path.basename(lp).split('_')[0])
            for c, tag in enumerate(("L", "D", "P")):
                p = f"{S}/shots/{label}/{idx}_{tag}.png"
                if not os.path.exists(p): continue
                im = Image.open(p).convert("RGB"); im.thumbnail((W - 6, H - 22))
                sh.paste(im, (c * W + 3, r * H + 20))
            ImageDraw.Draw(sh).text((6, r * H + 4), f"{idx} {manifest[idx]}", fill=(250, 204, 21))
        sh.save(f"{S}/shots/{label}_sheet{n + 1}.png")
        print("sheet", n + 1)

if __name__ == "__main__":
    if sys.argv[1] == "render": render(sys.argv[2], sys.argv[3])
    else: sheet(sys.argv[2])
