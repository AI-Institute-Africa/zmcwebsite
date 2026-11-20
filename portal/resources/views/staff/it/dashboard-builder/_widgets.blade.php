<div class="row g-3">
    @forelse($widgets as $widget)
        <div class="{{ $widget->size }}">
            @php
                $data = $widgetData[$widget->id] ?? null;
                $color = $widget->config['color'] ?? 'primary';
                $icon = $widget->config['icon'] ?? 'ri-bar-chart-box-line';
            @endphp

            @if($widget->widget_type === 'stats_card' || $widget->widget_type === 'counter')
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:var(--bs-{{ $color }}-bg-subtle, #e8e8e8);">
                            <i class="{{ $icon }}" style="font-size:22px; color:var(--bs-{{ $color }}, #333);"></i>
                        </div>
                        <div>
                            <h1 class="fw-bold mb-0">{{ is_numeric($data) ? number_format($data) : ($data ?? 0) }}</h1>
                            <div class="text-muted" style="font-size:13px;">{{ $widget->title }}</div>
                        </div>
                    </div>
                </div>

            @elseif(in_array($widget->widget_type, ['bar_chart', 'pie_chart', 'line_chart']))
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="fw-bold m-0" style="font-size:14px;">{{ $widget->title }}</h2>
                    </div>
                    <div class="card-body">
                        <canvas id="widgetChart{{ $widget->id }}" height="200"></canvas>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart === 'undefined') return;
                    var ctx = document.getElementById('widgetChart{{ $widget->id }}');
                    if (!ctx) return;
                    var rawData = @json($data ?? []);
                    var labels = Object.keys(rawData).map(function(l){ return l.replace(/_/g, ' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); }); });
                    var values = Object.values(rawData);
                    var colors = ['#1a1a1a','#f5c518','#3b82f6','#10b981','#ef4444','#8b5cf6','#f59e0b','#06b6d4'];

                    @if($widget->widget_type === 'pie_chart')
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors.slice(0, values.length) }] },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
                    });
                    @elseif($widget->widget_type === 'bar_chart')
                    new Chart(ctx, {
                        type: 'bar',
                        data: { labels: labels, datasets: [{ label: '{{ $widget->title }}', data: values, backgroundColor: 'rgba(26,26,26,0.8)' }] },
                        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
                    });
                    @else
                    new Chart(ctx, {
                        type: 'line',
                        data: { labels: labels, datasets: [{ label: '{{ $widget->title }}', data: values, borderColor: '#1a1a1a', tension: 0.3, fill: false }] },
                        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
                    });
                    @endif
                });
                </script>

            @elseif($widget->widget_type === 'table' || $widget->widget_type === 'recent_list')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h3 class="fw-bold m-0" style="font-size:14px;">{{ $widget->title }}</h3>
                    </div>
                    <div class="card-body p-0">
                        @if(is_array($data) && !empty($data))
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                                    <thead class="table-light">
                                        <tr>
                                            @foreach(array_keys(reset($data) ?: []) as $col)
                                                <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($data, 0, $widget->config['limit'] ?? 10) as $row)
                                        <tr>
                                            @foreach($row as $val)
                                                <td>{{ is_array($val) ? json_encode($val) : Str::limit($val, 40) }}</td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif(is_array($data))
                            <div class="p-3">
                                @foreach($data as $key => $val)
                                    <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:13px;">
                                        <span>{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <strong>{{ is_numeric($val) ? number_format($val) : $val }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-3 text-muted text-center">No data available.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="ri-layout-grid-line" style="font-size:48px; opacity:0.3;"></i>
                <p class="mt-2">No widgets configured for this dashboard.</p>
            </div>
        </div>
    @endforelse
</div>
