"""Read every <style> block in the portal views and emit dark-mode overrides for the rules that set an
unreadable text colour, a light background, or a light border. Returns CSS text (also usable as a module)."""
import re, glob, os, colorsys

import os
root = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".."))).replace("\\", "/") + "/resources/views"
CARD = '#1e293b'

def norm(h):
    h = h.lower().lstrip('#')
    if len(h) in (3, 4): h = ''.join(c * 2 for c in h[:3])
    return '#' + h[:6]

def lin(v):
    v /= 255
    return v / 12.92 if v <= .03928 else ((v + .055) / 1.055) ** 2.4

def rgb(h): return tuple(int(h[i:i + 2], 16) for i in (1, 3, 5))
def lum(h):
    r, g, b = rgb(h); return .2126 * lin(r) + .7152 * lin(g) + .0722 * lin(b)
def contrast(a, b):
    la, lb = lum(a), lum(b); return (max(la, lb) + .05) / (min(la, lb) + .05)
def is_neutral(h):
    r, g, b = rgb(h); return (max(r, g, b) - min(r, g, b)) <= 40
def hsv_s(h):
    r, g, b = rgb(h); m = max(r, g, b); return (m - min(r, g, b)) / m if m else 0

def lighten_to(h, target=5.6):
    r, g, b = (c / 255 for c in rgb(h)); hh, ll, ss = colorsys.rgb_to_hls(r, g, b)
    for step in range(80):
        rr, gg, bb = colorsys.hls_to_rgb(hh, min(.95, ll + step * .01), ss)
        cand = '#%02x%02x%02x' % (round(rr * 255), round(gg * 255), round(bb * 255))
        if contrast(cand, CARD) >= target: return cand
    return '#f1f5f9'

def darken_tint(h):
    r, g, b = (c / 255 for c in rgb(h)); hh, ll, ss = colorsys.rgb_to_hls(r, g, b)
    rr, gg, bb = colorsys.hls_to_rgb(hh, .16, min(.45, ss))
    return '#%02x%02x%02x' % (round(rr * 255), round(gg * 255), round(bb * 255))

def map_text(h):
    if contrast(h, CARD) >= 4.5: return None
    if is_neutral(h):
        l = lum(h); return '#f1f5f9' if l < .06 else ('#cbd5e1' if l < .14 else '#94a3b8')
    return lighten_to(h)

def map_bg(h):
    if lum(h) < .55 or hsv_s(h) >= .40: return None      # dark, or a saturated brand/accent fill: leave alone
    if is_neutral(h): return 'var(--c-card)' if lum(h) > .925 else 'var(--dm-surface-2)'
    return darken_tint(h)

HEX = r'(#[0-9a-fA-F]{3,8}|white|black)\b'
def hexval(v):
    v = v.strip().lower()
    if v == 'white': return '#ffffff'
    if v == 'black': return '#000000'
    return norm(v) if re.fullmatch(r'#[0-9a-f]{3,8}', v) else None

decl_re = re.compile(r'([a-z-]+)\s*:\s*([^;{}]+?)\s*(?:!important)?\s*(?:;|$)', re.I)
rule_re = re.compile(r'([^{}@]+)\{([^{}]*)\}')

SKIP_COLOR = re.compile(r'(btn|button|badge|chip|pill|cta|alert|toast|tooltip|page-link|stat-pill|tag\b)', re.I)

def build():
    rules = {}                                            # (selector, css) -> source count
    files = [f for f in glob.glob(root + '/**/*.blade.php', recursive=True)
             if '.bak' not in f and not re.search(r'/(emails|mail)/|card_print|card_back|certificate_|_pdf\.|/pdf/', f.replace('\\', '/'))]
    for f in files:
        s = open(f, encoding='utf-8', errors='ignore').read()
        blocks_all = re.findall(r'<style[^>]*>(.*?)</style>', s, re.S | re.I)
        fvars = {}
        for b_ in blocks_all:
            for nm, hx in re.findall(r'(--[\w-]+)\s*:\s*(#[0-9a-fA-F]{3,8}|white|black)\b', b_):
                fvars.setdefault(nm, hx)
        for blk in blocks_all:
            blk = re.sub(r'/\*.*?\*/', '', blk, flags=re.S)
            blk = re.sub(r'\{\{.*?\}\}|\{!!.*?!!\}|@(?!media|keyframes|font-face|supports|import)\w+(\([^)]*\))?', '', blk, flags=re.S)
            for sel, body in rule_re.findall(blk):
                sel = sel.strip()
                if not sel or sel.startswith(('from', 'to', '@')) or re.match(r'^\d+%', sel) or ':root' in sel or sel in ('html', 'body') and False: continue
                decls = {m.group(1).lower(): m.group(2).strip() for m in decl_re.finditer(body)}
                for k_, v_ in list(decls.items()):
                    mv = re.match(r'var\(\s*(--[\w-]+)\s*(?:,\s*([^)]+))?\)', v_)
                    if mv and not k_.startswith('--'):
                        res = fvars.get(mv.group(1)) or (mv.group(2) or '').strip()
                        if res and hexval(res): decls[k_] = res
                out = []
                own_bg = None
                for prop in ('background', 'background-color'):
                    if prop in decls:
                        if 'gradient' in decls[prop] or 'url(' in decls[prop]: own_bg = 'complex'; continue
                        m = re.match(HEX, decls[prop].split()[0]) if decls[prop] else None
                        hv = hexval(m.group(1)) if m else None
                        if hv:
                            tgt = map_bg(hv)
                            if tgt: out.append(f"background-color: {tgt} !important"); own_bg = 'dark'
                            else: own_bg = 'kept'
                if 'color' in decls and own_bg not in ('kept', 'complex') and not SKIP_COLOR.search(sel):
                    hv = hexval(decls['color'])
                    if hv:
                        tgt = map_text(hv)
                        if tgt: out.append(f"color: {tgt} !important")
                for prop in ('border', 'border-color', 'border-top', 'border-bottom', 'border-left', 'border-right'):
                    if prop in decls:
                        m = re.search(HEX, decls[prop]); hv = hexval(m.group(1)) if m else None
                        if hv and lum(hv) > .6 and hsv_s(hv) < .25:
                            side = prop if prop != 'border' else 'border-color'
                            side = side.replace('border-', 'border-') + ('-color' if prop in ('border-top', 'border-bottom', 'border-left', 'border-right') else '')
                            out.append(f"{side}: var(--c-border) !important")
                if not out: continue
                css = "; ".join(sorted(set(out))) + ";"
                for one in [x.strip() for x in sel.split(',') if x.strip()]:
                    if ':root' in one or '@' in one or re.search(r'#(\s|$)|^[#.]?\s*$', one): continue
                    if one.count('(') != one.count(')') or one.count('[') != one.count(']'): continue
                    if one.startswith(('>', '+', '~')) or '\n' in one: continue
                    rules[(one, css)] = rules.get((one, css), 0) + 1
    lines = []
    for (sel, css), n in sorted(rules.items()):
        lines.append(f'[data-bs-theme="dark"] {sel} {{ {css} }}')
    return "\n".join(lines), len(rules), len(files)

if __name__ == "__main__":
    css, n, nf = build()
    print("files:", nf, "rules:", n, "css KB:", round(len(css) / 1024, 1))
    print("\n".join(css.split("\n")[:12]))
