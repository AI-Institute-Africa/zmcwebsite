import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
"""Generate the dark-mode + motion CSS and splice it into portal.blade.php (between markers)."""
import re, sys

ROOT = PORTAL + "/resources/views/layouts/portal.blade.php"
S_DIR = os.path.dirname(os.path.abspath(__file__))

import json
COL = json.load(open(WORK + "/colors.json"))

def variants(prop, val):
    """Attribute selectors matching `prop:val` exactly (value ends at ; or ' !important' or end of attribute), with and
    without a space after the colon, ASCII case-insensitive (the `i` flag) so '#0B0F17' and '#0b0f17' both match and
    '#fff' never matches '#fff3cd'."""
    out = []
    for sp in ("", " "):
        core = f"{prop}:{sp}{val}"
        out += [f'[style*="{core};" i]', f'[style*="{core} !important" i]', f'[style$="{core}" i]']
    return out

D = '[data-bs-theme="dark"]'
GUARD = ':where(:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([class*="bg-secondary"]):not([style*="background" i]))'

def group_rule(selectors, body, guard=""):
    return f"{D} :is(\n  " + ",\n  ".join(selectors) + f"\n){guard} {{\n  {body}\n}}"

parts = []
# --- light backgrounds -> dark surfaces (grouped by target colour)
by_target = {}
BASE_SURFACE = ["#fff", "#ffffff", "white", "#fafafa", "#f9fafb"]
BASE_INSET = ["#f8fafc", "#f1f5f9", "#f3f4f6", "#f7f9fc", "#f5f8f7", "#f4f6f8", "#eef2f7", "#f8f9fa"]
for lit in BASE_SURFACE: by_target.setdefault("var(--c-card)", set()).add(lit)
for lit in BASE_INSET: by_target.setdefault("var(--dm-surface-2)", set()).add(lit)
for lit, tgt in COL["bg"].items():
    key = "var(--c-card)" if tgt == "#1e293b" else ("var(--dm-surface-2)" if tgt == "#263449" else tgt)
    by_target.setdefault(key, set()).add(lit)
for tgt, lits in sorted(by_target.items()):
    sels = [s_ for lit in sorted(lits) for prop in ("background", "background-color") for s_ in variants(prop, lit)]
    parts.append(group_rule(sels, f"background-color: {tgt} !important; color: var(--c-text);"))

# --- dark/low-contrast text -> readable on dark surfaces (grouped by replacement colour)
by_text = {}
BASE_TEXT = {"#0f172a": "#f1f5f9", "#1e293b": "#f1f5f9", "#111827": "#f1f5f9", "#111": "#f1f5f9", "#000": "#f1f5f9", "#000000": "#f1f5f9",
             "#1a1a1a": "#f1f5f9", "#212529": "#f1f5f9", "#172b2b": "#f1f5f9", "#173b3d": "#f1f5f9", "#0b0f17": "#f1f5f9",
             "#334155": "#cbd5e1", "#475569": "#cbd5e1", "#374151": "#cbd5e1", "#4b5563": "#cbd5e1", "#48605d": "#cbd5e1",
             "#64748b": "#94a3b8", "#6b7280": "#94a3b8"}
for lit, rep in {**BASE_TEXT, **COL["text"]}.items(): by_text.setdefault(rep, set()).add(lit)
for rep, lits in sorted(by_text.items()):
    sels = [s_ for lit in sorted(lits) for s_ in variants("color", lit)]
    parts.append(group_rule(sels, f"color: {rep} !important;", GUARD))

# --- hard-coded light borders
border_light = ["#e2e8f0", "#e7ecf3", "#e8edf1", "#e4ebe8", "#e5e7eb", "#eef2f7", "#dee2e6", "#cbd5e1", "#f1f5f9"]
border_items = []
for v in border_light:
    for sp in ("", " "):
        border_items += [f'[style*="solid{sp}{v};" i]', f'[style$="solid{sp}{v}" i]', f'[style*="border-color:{sp}{v};" i]', f'[style*="border-color:{sp}{v} " i]']
border_items = list(dict.fromkeys(border_items))
parts.append(group_rule(border_items, "border-color: var(--c-border) !important;"))

generated = "\n".join(parts)

BLOCK = r"""
  /* ===== LIGHT MODE CONTRAST FIXES (2026-11-19) ===== */
  .text-info { color: #0a6c8a !important; }
  .badge.bg-info, .btn-info, .bg-info:not(.bg-info-subtle) { color: #082f3a !important; }
  .pagination .page-item.active .page-link { color: #111 !important; }
  .alert-warning { color: #9a3412 !important; }
  /* Bootstrap's default red fails AA with white text (4.4:1): slightly deeper red passes (5.9:1) */
  .btn-danger, .badge.bg-danger { background-color: #c62828 !important; border-color: #c62828 !important; color: #fff !important; }
  .badge.bg-primary.text-dark { color: #fff !important; }
  .text-primary { color: #0a58ca !important; }

  /* ===== DARK MODE FIXES (2026-11-19) ===== */
  [data-bs-theme="dark"] { --dm-surface-2: #263449; --dm-text-strong: #f1f5f9; color-scheme: dark; }
  [data-bs-theme="dark"] body { background: var(--c-bg) !important; color: var(--c-text); }

  /* Inline light colours in views -> dark equivalents (generated, exact-terminated selectors) */
__GENERATED__

  /* Bootstrap components that had no dark override */
  [data-bs-theme="dark"] .table { --bs-table-color: var(--c-text); --bs-table-bg: transparent; --bs-table-border-color: var(--c-border);
    --bs-table-striped-bg: rgba(255,255,255,.04); --bs-table-hover-bg: rgba(250,204,21,.08); --bs-table-hover-color: var(--c-text); color: var(--c-text); }
  [data-bs-theme="dark"] .table-light, [data-bs-theme="dark"] .table > :not(caption) > * > .table-light,
  [data-bs-theme="dark"] thead.table-light th, [data-bs-theme="dark"] .table thead th {
    --bs-table-bg: var(--dm-surface-2); --bs-table-color: #e2e8f0; background-color: var(--dm-surface-2) !important; color: #e2e8f0 !important; border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .btn-light { background: var(--dm-surface-2) !important; border-color: var(--c-border) !important; color: var(--c-text) !important; }
  [data-bs-theme="dark"] .btn-light:hover { background: #324259 !important; }
  [data-bs-theme="dark"] .btn-outline-secondary, [data-bs-theme="dark"] .btn-outline-dark { color: #cbd5e1 !important; border-color: #475569 !important; }
  [data-bs-theme="dark"] .btn-outline-secondary:hover, [data-bs-theme="dark"] .btn-outline-dark:hover { background: #334155 !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-primary { border-color: #facc15 !important; }
  [data-bs-theme="dark"] .input-group-text { background: var(--dm-surface-2) !important; border-color: var(--c-border) !important; color: var(--c-muted) !important; }
  [data-bs-theme="dark"] .form-control::placeholder, [data-bs-theme="dark"] textarea::placeholder { color: #7c8aa0 !important; opacity: 1; }
  [data-bs-theme="dark"] .form-control:disabled, [data-bs-theme="dark"] .form-select:disabled, [data-bs-theme="dark"] .form-control[readonly] { background: #172033 !important; color: #94a3b8 !important; }
  [data-bs-theme="dark"] .form-check-input { background-color: var(--dm-surface-2); border-color: #64748b; }
  [data-bs-theme="dark"] .form-check-input:checked { background-color: #facc15; border-color: #facc15; }
  [data-bs-theme="dark"] .list-group { --bs-list-group-bg: var(--c-card); --bs-list-group-color: var(--c-text); --bs-list-group-border-color: var(--c-border); }
  [data-bs-theme="dark"] .list-group-item { background: var(--c-card) !important; color: var(--c-text) !important; border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .list-group-item-action:hover { background: var(--dm-surface-2) !important; }
  [data-bs-theme="dark"] .nav-tabs { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link, [data-bs-theme="dark"] .nav-pills .nav-link { color: #cbd5e1 !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link:hover { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link.active { background: var(--c-card) !important; color: #facc15 !important; border-color: var(--c-border) var(--c-border) var(--c-card) !important; }
  [data-bs-theme="dark"] .nav-pills .nav-link.active { background: #facc15 !important; color: #111 !important; }
  [data-bs-theme="dark"] .page-link { background: var(--c-card); border-color: var(--c-border); color: #cbd5e1; }
  [data-bs-theme="dark"] .pagination .page-item.active .page-link { background: #facc15 !important; border-color: #facc15 !important; color: #111 !important; }
  [data-bs-theme="dark"] .page-item.disabled .page-link { background: #172033; color: #64748b; }
  [data-bs-theme="dark"] .accordion { --bs-accordion-bg: var(--c-card); --bs-accordion-color: var(--c-text); --bs-accordion-border-color: var(--c-border);
    --bs-accordion-btn-bg: var(--c-card); --bs-accordion-btn-color: var(--c-text); --bs-accordion-active-bg: var(--dm-surface-2); --bs-accordion-active-color: #facc15; }
  [data-bs-theme="dark"] .breadcrumb-item, [data-bs-theme="dark"] .breadcrumb-item a { color: #cbd5e1; }
  [data-bs-theme="dark"] .breadcrumb-item.active { color: var(--c-muted); }
  [data-bs-theme="dark"] .progress { background: #334155; }
  [data-bs-theme="dark"] .popover, [data-bs-theme="dark"] .tooltip-inner { --bs-popover-bg: var(--dm-surface-2); --bs-popover-border-color: var(--c-border); --bs-popover-body-color: var(--c-text); --bs-popover-header-bg: #324259; --bs-popover-header-color: var(--c-text); }
  [data-bs-theme="dark"] hr { border-color: var(--c-border); opacity: 1; }
  [data-bs-theme="dark"] code { color: #fda4af; }
  [data-bs-theme="dark"] a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):not(.list-group-item):not([class*="menu"]) { --bs-link-color-rgb: 250, 204, 21; }
  [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-body, [data-bs-theme="dark"] .bg-body-tertiary { background-color: var(--c-card) !important; color: var(--c-text); }
  [data-bs-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-body-secondary { background-color: var(--dm-surface-2) !important; color: var(--c-text); }
  [data-bs-theme="dark"] .text-body, [data-bs-theme="dark"] .text-black, [data-bs-theme="dark"] .text-dark, [data-bs-theme="dark"] .text-secondary { color: #e2e8f0 !important; }
  [data-bs-theme="dark"] .text-muted, [data-bs-theme="dark"] .text-body-secondary { color: var(--c-muted) !important; }
  [data-bs-theme="dark"] .border, [data-bs-theme="dark"] .border-light, [data-bs-theme="dark"] .border-bottom, [data-bs-theme="dark"] .border-top, [data-bs-theme="dark"] .border-end, [data-bs-theme="dark"] .border-start { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .shadow-sm, [data-bs-theme="dark"] .shadow { box-shadow: 0 6px 20px rgba(0,0,0,.35) !important; }
  [data-bs-theme="dark"] .alert-success { --bs-alert-bg: rgba(34,197,94,.14); --bs-alert-color: #86efac; --bs-alert-border-color: rgba(34,197,94,.35); }
  [data-bs-theme="dark"] .alert-danger  { --bs-alert-bg: rgba(239,68,68,.14); --bs-alert-color: #fca5a5; --bs-alert-border-color: rgba(239,68,68,.35); }
  [data-bs-theme="dark"] .alert-warning { --bs-alert-bg: rgba(250,204,21,.13); --bs-alert-color: #fde68a; --bs-alert-border-color: rgba(250,204,21,.35);
    background-color: rgba(251,146,60,.14) !important; border-color: rgba(251,146,60,.4) !important; color: #fed7aa !important; }
  [data-bs-theme="dark"] .alert-danger { color: #fecaca !important; }
  [data-bs-theme="dark"] .alert-info    { --bs-alert-bg: rgba(59,130,246,.14); --bs-alert-color: #93c5fd; --bs-alert-border-color: rgba(59,130,246,.35); }
  [data-bs-theme="dark"] .bg-success-subtle { background-color: rgba(34,197,94,.16) !important; color: #86efac !important; }
  [data-bs-theme="dark"] .bg-danger-subtle  { background-color: rgba(239,68,68,.16) !important; color: #fca5a5 !important; }
  [data-bs-theme="dark"] .bg-warning-subtle { background-color: rgba(250,204,21,.15) !important; color: #fde68a !important; }
  [data-bs-theme="dark"] .bg-info-subtle, [data-bs-theme="dark"] .bg-primary-subtle { background-color: rgba(59,130,246,.16) !important; color: #93c5fd !important; }
  [data-bs-theme="dark"] .bg-secondary-subtle { background-color: rgba(148,163,184,.16) !important; color: #cbd5e1 !important; }
  [data-bs-theme="dark"] .badge.bg-success-subtle { color: #86efac !important; }
  [data-bs-theme="dark"] .text-success { color: #4ade80 !important; }
  [data-bs-theme="dark"] .text-danger  { color: #f87171 !important; }
  [data-bs-theme="dark"] .text-warning { color: #fbbf24 !important; }
  [data-bs-theme="dark"] .text-primary, [data-bs-theme="dark"] .text-info { color: #60a5fa !important; }
  [data-bs-theme="dark"] .btn-success { background: #15803d !important; border-color: #15803d !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-outline-success { color: #4ade80 !important; border-color: #4ade80 !important; }
  [data-bs-theme="dark"] .btn-outline-success:hover { background: #15803d !important; color: #fff !important; }
  [data-bs-theme="dark"] .bg-warning, [data-bs-theme="dark"] .btn-warning, [data-bs-theme="dark"] .bg-warning .text-dark,
  [data-bs-theme="dark"] .bg-warning.text-dark, [data-bs-theme="dark"] .bg-info.text-dark, [data-bs-theme="dark"] .badge.bg-info, [data-bs-theme="dark"] .btn-info { color: #111 !important; }
  [data-bs-theme="dark"] .btn-outline-primary { color: #93c5fd !important; border-color: #60a5fa !important; }
  [data-bs-theme="dark"] .btn-outline-primary:hover { background: #2563eb !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-outline-danger { color: #fca5a5 !important; border-color: #f87171 !important; }
  [data-bs-theme="dark"] .btn-outline-danger:hover { background: #dc2626 !important; color: #fff !important; }
  [data-bs-theme="dark"] .text-info { color: #67e8f9 !important; }
  [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
  [data-bs-theme="dark"] .zmc-tabs { background: var(--c-card); border-color: var(--c-border); box-shadow: 0 2px 10px rgba(0,0,0,.3); }
  [data-bs-theme="dark"] .zmc-tabs a, [data-bs-theme="dark"] .zmc-tabs button { color: #cbd5e1; }
  [data-bs-theme="dark"] .zmc-tabs a:hover, [data-bs-theme="dark"] .zmc-tabs button:hover { background: var(--dm-surface-2); color: #fff; }
  [data-bs-theme="dark"] .zmc-tabs a[aria-selected="true"], [data-bs-theme="dark"] .zmc-tabs button[aria-selected="true"] { background: #facc15; color: #111; }
  [data-bs-theme="dark"] .zmc-tabs .zmc-tab-count { background: rgba(148,163,184,.25); }
  [data-bs-theme="dark"] html, html[data-bs-theme="dark"] { scrollbar-color: #475569 #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-track, [data-bs-theme="dark"] body *::-webkit-scrollbar-track { background: #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-thumb, [data-bs-theme="dark"] body *::-webkit-scrollbar-thumb { background: #475569; border-color: #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-thumb:hover, [data-bs-theme="dark"] body *::-webkit-scrollbar-thumb:hover { background: #64748b; }
  [data-bs-theme="dark"] .sidebar-menu::-webkit-scrollbar-track { background: rgba(255,255,255,.12); }
  [data-bs-theme="dark"] .sidebar-menu::-webkit-scrollbar-thumb { background: #facc15; }

  /* ===== MOTION (subtle; disabled by prefers-reduced-motion) ===== */
  @keyframes zmc-rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
  @keyframes zmc-fade { from { opacity: 0; } to { opacity: 1; } }
  @keyframes zmc-slide-in { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: none; } }
  @keyframes zmc-pop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }

  /* IMPORTANT: entry animations use fill-mode "backwards" (never "both"/"forwards"): a finished animation that keeps
     filling leaves transform:matrix(1,0,0,1,0,0) on the element, which creates a stacking context + containing block
     and traps position:fixed modals under the page-level backdrop. Anything that contains a modal is never animated. */
  .main > *:not(.topbar):not(.content):not(.modal):not(.toast-container):not(:has(.modal)) { animation: zmc-rise .5s cubic-bezier(.2,.7,.2,1) backwards; }
  .content > *:not(.modal):not(.toast-container):not(:has(.modal)) { animation: zmc-rise .5s cubic-bezier(.2,.7,.2,1) backwards; }
  .main .row > [class*="col"]:not(:has(.modal)) { animation: zmc-rise .5s cubic-bezier(.2,.7,.2,1) backwards; }
  .main .row > [class*="col"]:nth-child(1) { animation-delay: .04s; } .main .row > [class*="col"]:nth-child(2) { animation-delay: .09s; }
  .main .row > [class*="col"]:nth-child(3) { animation-delay: .14s; } .main .row > [class*="col"]:nth-child(4) { animation-delay: .19s; }
  .main .row > [class*="col"]:nth-child(5) { animation-delay: .24s; } .main .row > [class*="col"]:nth-child(n+6) { animation-delay: .29s; }
  .alert:not(:has(.modal)) { animation: zmc-rise .4s ease-out backwards; }
  /* opacity only: Popper positions dropdowns with its own transform, which a transform animation would override (menu jumps) */
  .dropdown-menu.show { animation: zmc-fade .16s ease-out; }
  .modal.fade .modal-dialog { transform: translateY(-14px) scale(.98); transition: transform .28s cubic-bezier(.2,.7,.2,1); }
  .modal.show .modal-dialog { transform: none; }
  .tab-pane.active.show:not(:has(.modal)), .tab-pane.fade.show:not(:has(.modal)) { animation: zmc-fade .3s ease-out; }

  /* interactions */
  .sidebar-menu li a { transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease; }
  .sidebar-menu li:not(.menu-title) a:hover { transform: translateX(4px); }
  .sidebar-menu li.active a { box-shadow: inset 3px 0 0 #facc15; }
  .sidebar-menu li { animation: zmc-slide-in .4s cubic-bezier(.2,.7,.2,1) backwards; }
  .sidebar-menu li:nth-child(1) { animation-delay: .02s; } .sidebar-menu li:nth-child(2) { animation-delay: .05s; }
  .sidebar-menu li:nth-child(3) { animation-delay: .08s; } .sidebar-menu li:nth-child(4) { animation-delay: .11s; }
  .sidebar-menu li:nth-child(5) { animation-delay: .14s; } .sidebar-menu li:nth-child(6) { animation-delay: .17s; }
  .sidebar-menu li:nth-child(7) { animation-delay: .20s; } .sidebar-menu li:nth-child(8) { animation-delay: .23s; }
  .sidebar-menu li:nth-child(n+9) { animation-delay: .26s; }
  .main .row > [class*="col"] > .h-100 { transition: transform .22s ease, box-shadow .22s ease; }
  .main .row > [class*="col"] > .h-100:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(15,35,48,.14) !important; }
  [data-bs-theme="dark"] .main .row > [class*="col"] > .h-100:hover { box-shadow: 0 14px 30px rgba(0,0,0,.5) !important; }
  .btn { transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .12s ease, box-shadow .18s ease; }
  .btn:hover:not(:disabled) { transform: translateY(-1px); }
  .btn:active:not(:disabled) { transform: translateY(0) scale(.97); }
  .table tbody tr { transition: background-color .15s ease; }
  .zmc-tabs a, .zmc-tabs button { transition: background-color .22s ease, color .22s ease, transform .15s ease; }
  .zmc-tabs a:hover, .zmc-tabs button:hover { transform: translateY(-1px); }
  .zmc-tabs a[aria-selected="true"], .zmc-tabs button[aria-selected="true"] { animation: zmc-pop .25s ease-out; }
  .form-control, .form-select { transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }

  /* smooth light <-> dark switch (class added briefly by script when the theme attribute changes) */
  html.zmc-theme-anim, html.zmc-theme-anim *, html.zmc-theme-anim *::before, html.zmc-theme-anim *::after {
    transition: background-color .35s ease, color .35s ease, border-color .35s ease, box-shadow .35s ease, fill .35s ease !important; }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
  }
"""

import sys
sys.path.insert(0, S_DIR)
import gen_views_dark
views_css, n_rules, n_files = gen_views_dark.build()
dark_css = ("/* GENERATED by tools/gen-dark (2026-11-19). Dark-mode remaps for inline colours and per-view <style> rules.\n"
            "   Do not edit by hand: regenerate from the views. */\n"
            + generated + "\n\n/* ---- per-view <style> rules (" + str(n_rules) + " rules from " + str(n_files) + " views) ---- */\n" + views_css + "\n")
open(PORTAL + "/public/css/zmc-dark.css", "w", encoding="utf-8", newline="").write(dark_css)
print("zmc-dark.css KB:", round(len(dark_css) / 1024, 1), "| view rules:", n_rules)
css = BLOCK.replace("__GENERATED__", "  /* inline-colour and per-view dark remaps live in public/css/zmc-dark.css */")

src = open(ROOT, encoding="utf-8").read()
start = "  /* ===== LIGHT MODE CONTRAST FIXES (2026-11-19) ===== */"
legacy = "  /* ===== DARK MODE FIXES (2026-11-19) ===== */"
if start not in src and legacy in src:
    start = legacy  # first run of an older version: replace from the old marker
end_marker = "</style>"
if start in src:  # idempotent: replace previous generation
    a = src.index(start)
    b = src.index(end_marker, a)
    src = src[:a] + css.lstrip("\n") + src[b:]
else:
    anchor = "  .text-warning { color: #a16207 !important; }\n"
    assert anchor in src
    src = src.replace(anchor, anchor + css, 1)

# JS: brief transition class when theme attribute changes
js_anchor = "      // Accessibility: label the sidebar nav and mark the current page"
js = """      // Smooth theme switch: add a short-lived class whenever data-bs-theme changes
      (function () {
        const root = document.documentElement; let t;
        new MutationObserver(function () {
          root.classList.add('zmc-theme-anim'); clearTimeout(t);
          t = setTimeout(function () { root.classList.remove('zmc-theme-anim'); }, 500);
        }).observe(root, { attributes: true, attributeFilter: ['data-bs-theme'] });
      })();
"""
if "zmc-theme-anim'); clearTimeout" not in src:
    assert js_anchor in src
    src = src.replace(js_anchor, js + js_anchor, 1)

open(ROOT, "w", encoding="utf-8", newline="").write(src)
print("generated selector chars:", len(generated), "| total file chars:", len(src))
