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

VAR_SKIP = re.compile(r'(accent|primary|gold|yellow|success|danger|info|warn|brand|tone|sidebar|green|red|blue|orange|purple)', re.I)
VAR_LINE = re.compile(r'(line|border|hair|divider|rule)', re.I)
VAR_TEXT = re.compile(r'(ink|text|muted|fg|heading|title|strong|body|label|slate|dark)', re.I)
VAR_BG = re.compile(r'(^--bg|bg$|surface|card|panel|paper|soft|white)', re.I)
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
                        if 'gradient' in decls[prop]:
                            # a gradient whose stops are ALL light/pale (e.g. linear-gradient(#FFFCF3, #fff)) is a light box on a dark page:
                            # replace it with the dark card surface. Gradients with any dark/saturated stop are brand fills: leave alone.
                            stops = [hexval(x) for x in re.findall(r'#[0-9a-fA-F]{3,8}|white', decls[prop])]
                            stops = [x for x in stops if x]
                            if stops and 'rgb' not in decls[prop] and all(lum(x) > .75 and hsv_s(x) < .30 for x in stops):
                                out.append("background: var(--c-card) !important"); out.append("background-image: none !important"); own_bg = 'dark'
                            else:
                                own_bg = 'complex'
                            continue
                        if 'url(' in decls[prop]: own_bg = 'complex'; continue
                        mw = re.match(r'rgba\(\s*255\s*,\s*255\s*,\s*255\s*,\s*(0?\.\d+|1)\s*\)', decls[prop].strip(), re.I)
                        if mw and float(mw.group(1)) >= 0.3:         # translucent white panel: stays a grey slab on a dark page
                            out.append('background-color: rgba(255,255,255,.06) !important'); own_bg = 'dark'; continue
                        m = re.match(HEX, decls[prop].split()[0]) if decls[prop] else None
                        hv = hexval(m.group(1)) if m else None
                        if hv:
                            tgt = map_bg(hv)
                            if tgt: out.append(f"background-color: {tgt} !important"); own_bg = 'dark'
                            else: own_bg = 'kept'
                if 'color' in decls and own_bg not in ('kept', 'complex') and (own_bg == 'dark' or not SKIP_COLOR.search(sel)):
                    # (a rule that also gets a dark background must get readable text even for btn/badge/pill/tag classes)
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
                # custom properties holding page colours (e.g. --mh-ink:#0B0F17, --zd-muted, --card): remap by NAME so
                # everything that reads them follows the theme. Brand/accent colours are left alone.
                for k_, v_ in decls.items():
                    if not k_.startswith('--') or VAR_SKIP.search(k_): continue
                    mfb_ = re.match(r'var\(\s*--[\w-]+\s*,\s*(#[0-9a-fA-F]{3,8})\s*\)', v_.strip())      # var(--x, #fallback)
                    hv_ = hexval(mfb_.group(1)) if mfb_ else (hexval(v_.split()[0]) if v_ and re.match(HEX, v_.split()[0]) else None)
                    if not hv_: continue
                    if VAR_LINE.search(k_):
                        if lum(hv_) > .6 and hsv_s(hv_) < .25: out.append(f"{k_}: var(--c-border)")
                    elif VAR_TEXT.search(k_):
                        if lum(hv_) < .4:
                            tgt_ = map_text(hv_)
                            if tgt_: out.append(f"{k_}: {tgt_}")
                    elif VAR_BG.search(k_):
                        tgt_ = map_bg(hv_)
                        if tgt_: out.append(f"{k_}: {tgt_}")
                if not out: continue
                css = "; ".join(sorted(set(out))) + ";"
                for one in [x.strip() for x in sel.split(',') if x.strip()]:
                    if 'data-bs-theme' in one: continue                     # the view already styles dark itself
                    if ':root' in one or '@' in one or re.search(r'#(\s|$)|^[#.]?\s*$', one): continue
                    if one.count('(') != one.count(')') or one.count('[') != one.count(']'): continue
                    if one.startswith(('>', '+', '~')) or '\n' in one: continue
                    css_one = css
                    if ':hover' in one or ':focus' in one:
                        # hover/focus feedback must differ from the resting surface, or the state looks dead
                        css_one = re.sub(r'background-color: var\(--(?:c-card|dm-surface-2)\) !important', 'background-color: var(--dm-hover) !important', css)
                    rules[(one, css_one)] = rules.get((one, css_one), 0) + 1
    lines = []
    for (sel, css), n in sorted(rules.items()):
        lines.append(f'[data-bs-theme="dark"] {sel} {{ {css} }}')
    return "\n".join(lines), len(rules), len(files)

if __name__ == "__main__":
    css, n, nf = build()
    print("files:", nf, "rules:", n, "css KB:", round(len(css) / 1024, 1))
    print("\n".join(css.split("\n")[:12]))
