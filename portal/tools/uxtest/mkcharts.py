import os
_HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get("UXTEST_WORK", os.path.join(_HERE, "work")).replace("\\", "/")
PORTAL = os.environ.get("PORTAL_ROOT", os.path.normpath(os.path.join(_HERE, "..", ".."))).replace("\\", "/")
import re
S = WORK
root = PORTAL
layout = open(root + '/resources/views/layouts/portal.blade.php', encoding='utf-8').read()
blocks = [b for b in re.findall(r'<style[^>]*>(.*?)</style>', layout, re.S) if '{{' not in b and '@if' not in b and '@php' not in b]
css = "\n".join(b for b in blocks if 'LIGHT MODE CONTRAST FIXES' not in b)
uiblock = "\n".join(b for b in blocks if 'LIGHT MODE CONTRAST FIXES' in b)
uxcss = open(root + '/public/css/zmc-ux.css', encoding='utf-8').read()
darkcss = open(root + '/public/css/zmc-dark.css', encoding='utf-8').read()
uxjs = open(root + '/public/js/zmc-ux.js', encoding='utf-8').read()

page = '''<!doctype html><html data-bs-theme="dark"><head><meta charset=utf-8><title>charts</title>
<link href="/vendor/bootstrap/bootstrap.min.css" rel=stylesheet><link href="/vendor/remixicon/remixicon.css" rel=stylesheet>
<style>__CSS__</style><style>__UX__</style><style>__DARK__</style><style>__UI__</style>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/vendor/apexcharts.min.js"></script><script src="/vendor/chart.umd.min.js"></script></head>
<body><div class="app"><aside class="vertical-menu"><ul class="sidebar-menu"><li class="active"><a href="/staff/x">Item</a></li></ul></aside>
<main class="main"><section class="content staff-page-surface">
<div class="row g-3"><div class="col-md-6"><div class="card p-3"><h6>Apex line</h6><div id="apex"></div></div></div>
<div class="col-md-6"><div class="card p-3"><h6>Chart.js bar</h6><canvas id="cjs" height="160"></canvas></div></div></div>
</section></main></div>
<script>__UXJS__</script>
<script>
// created exactly like the views do: no theme settings at all
document.addEventListener('DOMContentLoaded', () => {
  window.__apex = new ApexCharts(document.querySelector('#apex'), { chart: { type: 'line', height: 220 }, series: [{ name: 'Applications', data: [12, 30, 22, 41, 38, 52] }], xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'] } });
  window.__apex.render();
  window.__cjs = new Chart(document.getElementById('cjs'), { type: 'bar', data: { labels: ['A', 'B', 'C', 'D'], datasets: [{ label: 'Payments', data: [5, 9, 4, 11], backgroundColor: '#facc15' }] }, options: { animation: false } });
});
</script></body></html>'''
page = (page.replace("__CSS__", css).replace("__UX__", uxcss).replace("__DARK__", darkcss).replace("__UI__", uiblock).replace("__UXJS__", uxjs))
open(S + "/srv/charts_test.html", "w", encoding="utf-8").write(page)
print("charts page written")
