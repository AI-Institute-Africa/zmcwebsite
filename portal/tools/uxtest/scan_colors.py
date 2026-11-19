import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
"""Scan every Blade view for inline hex colours and write a JSON the dark-mode generator consumes."""
import re, json, os, colorsys, glob
import os
root = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".."))).replace("\\", "/") + "/resources/views"
S = WORK
def norm(h):
    h = h.lower().lstrip('#')
    if len(h) in (3, 4): h = ''.join(c * 2 for c in h[:3])
    return '#' + h[:6]

def lin(v):
    v /= 255
    return v / 12.92 if v <= .03928 else ((v + .055) / 1.055) ** 2.4

def lum(h):
    r, g, b = (int(h[i:i + 2], 16) for i in (1, 3, 5))
    return .2126 * lin(r) + .7152 * lin(g) + .0722 * lin(b)

def contrast(a, b):
    la, lb = lum(a), lum(b)
    return (max(la, lb) + .05) / (min(la, lb) + .05)

text_literals, bg_literals = {}, {}
style_re = re.compile(r'style\s*=\s*"([^"]*)"', re.S | re.I)
t_re = re.compile(r'(?<![-\w])color\s*:\s*(#[0-9a-fA-F]{3,8})\b')
b_re = re.compile(r'(?<![-\w])background(?:-color)?\s*:\s*(#[0-9a-fA-F]{3,8})\b')
files = [f for f in glob.glob(root + '/**/*.blade.php', recursive=True) if '.bak' not in f and '/emails/' not in f.replace('\\', '/') and '/mail/' not in f.replace('\\', '/')]
for f in files:
    s = open(f, encoding='utf-8', errors='ignore').read()
    for m in style_re.finditer(s):
        st = m.group(1)
        for t in t_re.findall(st): text_literals[t.lower()] = text_literals.get(t.lower(), 0) + 1
        for b in b_re.findall(st): bg_literals[b.lower()] = bg_literals.get(b.lower(), 0) + 1

CARD = '#1e293b'
def lighten_to(h, target=5.6):
    r, g, b = (int(h[i:i + 2], 16) / 255 for i in (1, 3, 5))
    hh, ll, ss = colorsys.rgb_to_hls(r, g, b)
    for step in range(0, 80):
        l2 = min(0.95, ll + step * 0.01)
        rr, gg, bb = colorsys.hls_to_rgb(hh, l2, min(1, ss * 1.0))
        cand = '#%02x%02x%02x' % (round(rr * 255), round(gg * 255), round(bb * 255))
        if contrast(cand, CARD) >= target: return cand
    return '#f1f5f9'

def darken_tint(h):
    r, g, b = (int(h[i:i + 2], 16) / 255 for i in (1, 3, 5))
    hh, ll, ss = colorsys.rgb_to_hls(r, g, b)
    rr, gg, bb = colorsys.hls_to_rgb(hh, 0.16, min(0.45, ss))
    return '#%02x%02x%02x' % (round(rr * 255), round(gg * 255), round(bb * 255))

def is_neutral(h):
    r, g, b = (int(h[i:i + 2], 16) for i in (1, 3, 5))
    return (max(r, g, b) - min(r, g, b)) <= 40

text_map = {}   # literal -> replacement colour (only where text would be unreadable on the dark card)
for lit, n in text_literals.items():
    h = norm(lit)
    if contrast(h, CARD) >= 4.5: continue          # already readable on dark: leave alone
    if is_neutral(h):
        l = lum(h)
        text_map[lit] = '#f1f5f9' if l < 0.06 else ('#cbd5e1' if l < 0.14 else '#94a3b8')
    else:
        text_map[lit] = lighten_to(h)

bg_light = {}   # literal -> dark surface
for lit, n in bg_literals.items():
    h = norm(lit)
    if lum(h) < 0.55: continue
    r, g, b = (int(h[i:i + 2], 16) for i in (1, 3, 5))
    hsv_s = (max(r, g, b) - min(r, g, b)) / max(r, g, b) if max(r, g, b) else 0
    if hsv_s >= 0.40: continue                      # saturated brand/accent fill (e.g. #facc15): keep as is
    if is_neutral(h):
        bg_light[lit] = '#1e293b' if lum(h) > 0.925 else '#263449'
    else:
        bg_light[lit] = darken_tint(h)

json.dump({'text': text_map, 'bg': bg_light, 'counts': {'text': text_literals, 'bg': bg_literals}}, open(S + '/colors.json', 'w'), indent=1)
print('views scanned:', len(files))
print('distinct inline text colours:', len(text_literals), '-> need remap:', len(text_map))
print('distinct inline backgrounds:', len(bg_literals), '-> light, need remap:', len(bg_light))
print('top unreadable text colours:', sorted(((text_literals[k], k, v) for k, v in text_map.items()), reverse=True)[:12])
print('top light backgrounds:', sorted(((bg_literals[k], k, v) for k, v in bg_light.items()), reverse=True)[:10])
