import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
import re, os, json, glob, subprocess, sys
S = WORK
root = PORTAL
views_dir = root + "/resources/views"
frag_dir = S + "/srv/frag"
os.makedirs(frag_dir, exist_ok=True)
for f in glob.glob(frag_dir + "/*.html"): os.remove(f)

SKIP = re.compile(r'(^|/)(emails|mail|reports/pdf|receipts)/|_pdf\.blade|/pdf/|card_print|card_back|certificate_|levy_receipt|receipt_pdf|/layouts/|/partials/|errors/|components/')
def strip_blade(s):
    s = re.sub(r'\{\{--.*?--\}\}', '', s, flags=re.S)
    s = re.sub(r'@php.*?@endphp', '', s, flags=re.S)
    s = re.sub(r'<\?php.*?\?>', '', s, flags=re.S)
    s = re.sub(r'<script\b.*?</script>', '', s, flags=re.S | re.I)
    m = re.search(r'<body[^>]*>(.*)</body>', s, re.S | re.I)
    if m: s = m.group(1)
    s = re.sub(r"@method\(\s*['\"](delete|put|patch)['\"]\s*\)", lambda m: '<input type="hidden" name="_method" value="' + m.group(1).upper() + '">', s, flags=re.I)
    s = re.sub(r'\{!!.*?!!\}', '', s, flags=re.S)
    s = re.sub(r'class\s*=\s*"[^"]*"', lambda m: re.sub(r'\{\{.*?\}\}', 'primary', m.group(0), flags=re.S), s)
    s = re.sub(r'\{\{.*?\}\}', 'Sample', s, flags=re.S)
    for _ in range(3):
        s = re.sub(r'@(if|elseif|foreach|forelse|for|while|isset|empty|unless|can|cannot|role|hasanyrole|hasrole|auth|guest|include|includeIf|includeWhen|extends|section|push|stack|yield|component|slot|error|json|lang|method|class|props)\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)', '', s)
    s = re.sub(r'@(else|endif|endforeach|endforelse|endfor|endwhile|endisset|endempty|endunless|endcan|endcannot|endrole|endhasanyrole|endsection|endpush|endauth|endguest|endcomponent|endslot|enderror|csrf|stop|show|empty|break|continue)\b', '', s)
    return s

manifest = []
for f in sorted(glob.glob(views_dir + "/**/*.blade.php", recursive=True)):
    rel = os.path.relpath(f, views_dir).replace("\\", "/")
    if '.bak' in rel or SKIP.search("/" + rel): continue
    raw = open(f, encoding="utf-8", errors="ignore").read()
    frag = strip_blade(raw).strip()
    if len(frag) < 80 or '<' not in frag: continue
    i = len(manifest)
    open(f"{frag_dir}/{i}.html", "w", encoding="utf-8").write(frag)
    manifest.append(rel)
json.dump(manifest, open(S + "/srv/frag/manifest.json", "w"))
print("fragments:", len(manifest))
