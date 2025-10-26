<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- INITIAL DATA ---
        // Metrics data is injected from the server (Laravel Blade)
        const metrics = window.metrics || {};

        // Date series (last 30 days) and stage-wise QC event data
        const QC_DATES = {!! json_encode($metrics['qc_dates'] ?? []) !!};
        const QC_STAGE_SERIES = {!! json_encode($metrics['qc_stage_series'] ?? []) !!};
        console.log(QC_STAGE_SERIES); // Debug output to verify data shape

        // --- CHART VARIABLES ---
        let stageChart = null, // Donut chart for stage distribution
            qcEventChart = null, // (Reserved for another chart type if needed)
            qcChart = null; // Bar chart for QC status

        // Holds per-stage mini area charts (1 per stage)
        let perStageCharts = {};

        // State variables for donut hover interactions
        let hoveredIndex = null;
        let hoveredPercent = null;
        let hoveredLabel = null;

        // Current chart labels/series (used by formatters)
        let currentLabels = [];
        let currentSeries = [];

        // --- UTIL FUNCTION ---
        // Executes a function asynchronously (avoids DOM not ready issues)
        function later(fn) {
            window.setTimeout(fn, 0);
        }

        // --- DONUT CHART BUILDER ---
        function buildStageDonutOptions(stageCounts) {
            // Convert data to chart-compatible format
            const labels = Object.keys(stageCounts || {});
            const series = labels.map(lbl => Number(stageCounts[lbl] || 0));

            // Store globally for formatters
            currentLabels = labels;
            currentSeries = series;

            return {
                chart: {
                    type: 'donut',
                    height: 300,
                    events: {
                        // When hovering a slice, show % and label in center
                        dataPointMouseEnter: function(_, __, config) {
                            const idx = config.dataPointIndex;
                            if (typeof idx === 'number' && Array.isArray(currentSeries)) {
                                hoveredIndex = idx;
                                const total = currentSeries.reduce((a, b) => a + (Number(b) || 0), 0);
                                hoveredPercent = (total > 0) ? (currentSeries[idx] / total * 100) : 0;
                                hoveredLabel = currentLabels[idx] || null;
                                updateDonutCenter(hoveredLabel, hoveredPercent.toFixed(1) + '%');
                            }
                        },
                        // Reset donut center when hover ends
                        dataPointMouseLeave: resetDonutHover,
                        mouseLeave: resetDonutHover
                    }
                },

                series: series,
                labels: labels,

                tooltip: {
                    enabled: false
                },

                // Show actual count instead of percentage
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        try {
                            const idx = opts.seriesIndex;
                            return currentSeries[idx] ?? val;
                        } catch {
                            return val;
                        }
                    }
                },

                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                name: {
                                    show: true
                                },
                                value: {
                                    show: true
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: () =>
                                        currentSeries.reduce((a, b) => a + (Number(b) || 0), 0)
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom'
                }
            };
        }

        // --- DONUT CENTER UPDATERS ---
        // Updates text in the donut chart center
        function updateDonutCenter(name, value) {
            try {
                const container = document.querySelector('#stageDonut');
                if (!container) return;
                const nameEl = container.querySelector('.apexcharts-donut-label-name');
                const valueEl = container.querySelector('.apexcharts-donut-label-value');
                if (nameEl) nameEl.textContent = name ?? '';
                if (valueEl) valueEl.textContent = value ?? '';
            } catch {}
        }

        // Reset donut center to default (first slice)
        function setDonutDefaultCenter() {
            try {
                const defaultName = currentLabels[0] || '';
                const defaultCount = currentSeries[0] || 0;
                updateDonutCenter(defaultName, defaultCount);
            } catch {}
        }

        // Reset hover-related state
        function resetDonutHover() {
            hoveredIndex = null;
            hoveredPercent = null;
            hoveredLabel = null;
            setDonutDefaultCenter();
        }

        // --- QC STATUS BAR CHART ---
        function buildQcBarOptions(qcCounts) {
            const labels = Object.keys(qcCounts || {});
            const data = Object.values(qcCounts || {});
            return {
                chart: {
                    type: 'bar',
                    height: 240
                },
                series: [{
                    name: 'Count',
                    data
                }],
                xaxis: {
                    categories: labels
                }
            };
        }

        // Helper: converts stage name to safe DOM ID
        function safeId(stage) {
            return 'qc-' + (String(stage || '').replace(/[^a-z0-9]+/gi, '_').toLowerCase());
        }

        // --- AREA CHART (PER STAGE, 30 DAYS) ---
        function buildPerStageAreaOptions(categories, data, stageLabel) {
            return {
                chart: {
                    type: 'area',
                    height: 320,
                    zoom: {
                        enabled: false
                    }
                },
                series: [{
                    name: stageLabel || 'QC Events',
                    data
                }],
                xaxis: {
                    categories
                },
                yaxis: {
                    title: {
                        text: 'QC Events'
                    }
                },
                tooltip: {
                    x: {
                        format: 'yyyy-MM-dd'
                    }
                }
            };
        }

        // Render / update small area charts for each stage
        function renderPerStageCharts(dates, stageSeries) {
            // Destroy charts no longer in stageSeries
            Object.keys(perStageCharts).forEach(st => {
                if (!stageSeries[st]) {
                    try {
                        perStageCharts[st].destroy();
                    } catch {}
                    delete perStageCharts[st];
                }
            });

            // Create/update each stage chart
            Object.keys(stageSeries || {}).forEach(stage => {
                const dayMap = stageSeries[stage] || {};
                const orderedData = dates.map(d => Number(dayMap[d] ?? 0));
                const el = document.querySelector('#' + safeId(stage));
                if (!el) return; // skip missing container

                const opts = buildPerStageAreaOptions(dates, orderedData, stage.replace(/_/g, ' '));
                if (perStageCharts[stage]) {
                    // Try to update existing chart
                    try {
                        perStageCharts[stage].updateOptions(opts);
                    } catch {
                        // Fallback: destroy and recreate
                        try {
                            perStageCharts[stage].destroy();
                        } catch {}
                        perStageCharts[stage] = new ApexCharts(el, opts);
                        perStageCharts[stage].render();
                    }
                } else {
                    // Create new chart
                    perStageCharts[stage] = new ApexCharts(el, opts);
                    perStageCharts[stage].render();
                }
            });
        }

        // --- MAIN DASHBOARD RENDER FUNCTION ---
        function renderAll(m) {
            m = m || {};

            // Update KPI text values (daily counts, totals)
            const setTextIfExists = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.innerText = value ?? 0;
            };
            setTextIfExists('daily_bonding', m.daily_bonding);
            setTextIfExists('daily_tape_edge_qc', m.daily_tape_edge_qc);
            setTextIfExists('daily_zip_cover_qc', m.daily_zip_cover_qc);
            setTextIfExists('daily_packaging', m.daily_packaging);
            setTextIfExists('total_packaging', m.total_packaging);

            // --- DONUT CHART (STAGE DISTRIBUTION) ---
            const stageCounts = m.stageCounts || {};
            const stageOpts = buildStageDonutOptions(stageCounts);
            const stageContainer = document.querySelector("#stageDonut");
            if (stageContainer) {
                if (stageChart) {
                    // Update data if chart already exists
                    try {
                        later(() => stageChart.updateOptions({
                            series: stageOpts.series,
                            labels: stageOpts.labels
                        }));
                    } catch {
                        // Recreate if update fails
                        try {
                            stageChart.destroy();
                        } catch {}
                        stageChart = new ApexCharts(stageContainer, stageOpts);
                        stageChart.render().then(setDonutDefaultCenter);
                    }
                    later(setDonutDefaultCenter);
                } else {
                    // Create new donut chart
                    stageChart = new ApexCharts(stageContainer, stageOpts);
                    stageChart.render().then(setDonutDefaultCenter);
                }
            }

            // --- PER STAGE AREA CHARTS (30 DAYS) ---
            const dates = Array.isArray(m.qc_dates) ? m.qc_dates : QC_DATES;
            const stageSeries = (m.qc_stage_series && typeof m.qc_stage_series === 'object') ?
                m.qc_stage_series :
                QC_STAGE_SERIES;
            renderPerStageCharts(dates, stageSeries);

            // --- QC STATUS BAR CHART ---
            const qcOpts = buildQcBarOptions(m.qcCounts || {});
            const qcBarEl = document.querySelector("#qcBar");
            if (qcBarEl) {
                if (qcChart) {
                    try {
                        qcChart.updateOptions(qcOpts);
                    } catch {}
                } else {
                    qcChart = new ApexCharts(qcBarEl, qcOpts);
                    qcChart.render();
                }
            }
        }

        // --- INITIAL RENDER ---
        renderAll(metrics);

        // --- AUTO REFRESH (every 10 seconds) ---
        const DASHBOARD_METRICS_URL = "{{ url('dashboard/metrics') }}";
        setInterval(() => {
            axios.get(DASHBOARD_METRICS_URL)
                .then(resp => {
                    if (resp.data) {
                        resetDonutHover(); // reset donut state for new data
                        renderAll(resp.data); // re-render dashboard
                    }
                })
                .catch(err => {
                    console.error('Failed to refresh dashboard metrics', err);
                });
        }, 10000);

        // --- CLEANUP ---
        // Destroy charts on page unload (avoids memory leaks in SPAs)
        window.addEventListener('beforeunload', function() {
            try {
                stageChart?.destroy();
                qcEventChart?.destroy();
                qcChart?.destroy();
                Object.values(perStageCharts).forEach(chart => {
                    try {
                        chart.destroy();
                    } catch {}
                });
            } catch {}
        });
    });
</script>
