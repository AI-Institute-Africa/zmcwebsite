import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
import re, json, html, sys, collections
S = WORK
def load(name):
    """Reads work/<name>.json (written by pp/run_page.js), or the older audit_out_<name>.html wrapper."""
    import os
    jp = f"{S}/{name}.json"
    if os.path.exists(jp):
        return json.load(open(jp, encoding="utf-8"))
    d = open(f"{S}/audit_out_{name}.html", encoding="utf-8", errors="ignore").read()
    m = re.search(r'<pre id="results"[^>]*>(.*?)</pre>', d, re.S)
    if not m or not m.group(1).strip(): return None
    return json.loads(html.unescape(m.group(1)))

def summary(name, top=8, show_axe=True):
    r = load(name)
    if r is None: print(f"== {name}: no results"); return None
    n = len(r)
    sev = [x for x in r if x["severe"]]; low = [x for x in r if x["low"]]
    print(f"== {name}: views={n}")
    print(f"   contrast: <3:1 on {len(sev)} views ({sum(x['severe'] for x in r)} el) | <4.5:1 on {len(low)} views ({sum(x['low'] for x in r)} el) | guard had to fix {sum(x['fixed'] for x in r)} el on {sum(1 for x in r if x['fixed'])} views")
    mod = sum(x["modals"] for x in r); badm = [x for x in r if x["modalBad"]]
    print(f"   modals: {mod} tested | views with problems: {len(badm)} -> {collections.Counter(w for x in badm for w in x['modalBad'])}")
    print(f"   JS errors: {sum(1 for x in r if x['errors'])} views")
    print(f"   overflow >4px horizontally: {sum(1 for x in r if x['overflowX'] > 4)} views | h1 missing on {sum(1 for x in r if x['h1'] == 0)} / multiple on {sum(1 for x in r if x['h1'] > 1)} / skipped heading levels on {sum(1 for x in r if x['hskip'])} views")
    t = sum(x["tables"] for x in r); tb = [x for x in r if x["tableBad"]]
    if t:
        print(f"   tables: {t} tested | views with failures: {len(tb)} -> {collections.Counter(w.split(' for')[0].split(' ')[0] + ' ' + (w.split(' ')[1] if len(w.split(' '))>1 else '') for x in tb for w in x['tableBad']).most_common(5)}")
        print(f"   confirm dialogs: {sum(x['confirms'] for x in r)} delete forms | not intercepted: {sum(x['confirmBad'] for x in r)}")
        print(f"   dropdowns: {sum(x['dds'] for x in r)} tested | unstable: {sum(x['ddBad'] for x in r)}   required-field inline errors: {sum(x['reqs'] for x in r)} tested | missing: {sum(x['reqBad'] for x in r)}")
    gs = sorted(x["ms_guard"] for x in r); es = sorted(x["ms_enh"] for x in r)
    p = lambda a, q: a[min(len(a) - 1, int(len(a) * q))]
    print(f"   perf ms: guard median {p(gs,.5)} p95 {p(gs,.95)} max {gs[-1]} | enhanceTables median {p(es,.5)} p95 {p(es,.95)} max {es[-1]}")
    slow = sorted(r, key=lambda x: -(x["ms_guard"] + x["ms_enh"]))[:4]
    print("   slowest:", [(x["v"].split('/')[-1], x["ms_guard"], x["ms_enh"]) for x in slow])
    if show_axe and any(x["axe"] for x in r):
        agg = collections.defaultdict(lambda: [0, 0, set()])
        for x in r:
            for v in x["axe"]:
                a = agg[(v["id"], v["impact"])]; a[0] += v["n"]; a[1] += 1; a[2].add(x["v"])
        rows = sorted(agg.items(), key=lambda kv: ({"critical": 0, "serious": 1, "moderate": 2, "minor": 3}.get(kv[0][1], 4), -kv[1][0]))
        print("   axe (rule, impact): nodes / views")
        for (rid, imp), (nodes, views, _) in rows[:14]: print(f"     {imp:8} {rid:32} {nodes:>5} nodes / {views:>3} views")
        crit = sum(1 for x in r for v in x["axe"] if v["impact"] in ("critical", "serious"))
        print(f"   views with serious/critical axe violations: {sum(1 for x in r if any(v['impact'] in ('critical','serious') for v in x['axe']))}")
    return r

if __name__ == "__main__":
    for nme in sys.argv[1:]:
        summary(nme)
        print()
