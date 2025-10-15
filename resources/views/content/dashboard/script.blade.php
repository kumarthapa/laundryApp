<script>
    document.addEventListener('DOMContentLoaded', function() {
        // initial data from server
        const metrics = window.metrics || {};

        // Blade-injected date series and per-stage series (30 days)
        const QC_DATES = {!! json_encode($metrics['qc_dates'] ?? []) !!};
        const QC_STAGE_SERIES = {!! json_encode($metrics['qc_stage_series'] ?? []) !!};

        // --- global chart/state vars ---
        let stageChart = null,
            qcEventChart = null,
            qcChart = null;

        // map for per-stage small-area-charts (destroy/recreate on update)
        let perStageCharts = {};

        // hover state for donut center
        let hoveredIndex = null;
        let hoveredPercent = null;
        let hoveredLabel = null;

        // keep the current series/labels so formatters can read them safely
        let currentLabels = [];
        let currentSeries = [];

        // small safe timeout runner to avoid DOM-not-ready errors when updating
        function later(fn) {
            window.setTimeout(fn, 0);
        }

        // Build stage donut options and keep currentSeries/currentLabels in sync
        function buildStageDonutOptions(stageCounts) {
            const labels = Object.keys(stageCounts || {});
            const series = labels.map(lbl => Number(stageCounts[lbl] || 0));

            currentLabels = labels;
            currentSeries = series;

            return {
                chart: {
                    type: 'donut',
                    height: 300,
                    events: {
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
                        dataPointMouseLeave: function() {
                            hoveredIndex = null;
                            hoveredPercent = null;
                            hoveredLabel = null;
                            setDonutDefaultCenter();
                        },
                        mouseLeave: function() {
                            hoveredIndex = null;
                            hoveredPercent = null;
                            hoveredLabel = null;
                            setDonutDefaultCenter();
                        }
                    }
                },

                series: series,
                labels: labels,

                tooltip: {
                    enabled: false
                },

                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        try {
                            const idx = opts.seriesIndex;
                            return (Array.isArray(currentSeries) && typeof currentSeries[idx] !==
                                'undefined') ? currentSeries[idx] : val;
                        } catch (e) {
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
                                    show: true,
                                    formatter: function(name) {
                                        return name;
                                    }
                                },
                                value: {
                                    show: true,
                                    formatter: function(val) {
                                        return val;
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function() {
                                        return currentSeries.reduce((a, b) => a + (Number(b) || 0), 0);
                                    }
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

        // update donut center DOM elements directly (fast, avoids re-render)
        function updateDonutCenter(name, value) {
            try {
                const container = document.querySelector('#stageDonut');
                if (!container) return;
                const nameEl = container.querySelector('.apexcharts-donut-label .apexcharts-donut-label-name');
                const valueEl = container.querySelector(
                    '.apexcharts-donut-label .apexcharts-donut-label-value');
                if (nameEl) nameEl.textContent = name ?? '';
                if (valueEl) valueEl.textContent = value ?? '';
            } catch (e) {
                // silent
            }
        }

        function setDonutDefaultCenter() {
            try {
                const defaultName = (Array.isArray(currentLabels) && currentLabels.length) ? currentLabels[0] :
                    '';
                const defaultCount = (Array.isArray(currentSeries) && currentSeries.length) ? (currentSeries[
                    0] || 0) : 0;
                updateDonutCenter(defaultName, defaultCount);
            } catch (e) {}
        }

        // Daily throughput (area) - unchanged except made defensive
        // function buildQCeventyOptions(dailySeries) {
        //     const categories = Object.keys(dailySeries || {});
        //     const series = [{
        //         name: 'QC Events',
        //         data: Object.values(dailySeries || {})
        //     }];
        //     return {
        //         chart: {
        //             type: 'area',
        //             height: 320,
        //             zoom: {
        //                 enabled: false
        //             }
        //         },
        //         series: series,
        //         xaxis: {
        //             categories: categories
        //         },
        //         yaxis: {
        //             title: {
        //                 text: 'QC Events'
        //             }
        //         }
        //     };
        // }

        // QC bar chart builder (unchanged)
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
                    data: data
                }],
                xaxis: {
                    categories: labels
                }
            };
        }

        // helper: slugify stage names to DOM ids (mirrors server side Str::slug behaviour)
        function safeId(stage) {
            return 'qc-' + (String(stage || '').replace(/[^a-z0-9]+/gi, '_').toLowerCase());
        }

        // create options for a per-stage area chart (30 days)
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
                    data: data
                }],
                xaxis: {
                    categories: categories
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

        // Render / update per-stage small charts. This is defensive: destroys old chart if options change.
        function renderPerStageCharts(dates, stageSeries) {
            // destroy charts that are no longer present in stageSeries
            const existingStages = Object.keys(perStageCharts);
            const newStages = Object.keys(stageSeries || {});
            existingStages.forEach(st => {
                if (!newStages.includes(st)) {
                    try {
                        perStageCharts[st].destroy();
                    } catch (e) {}
                    delete perStageCharts[st];
                }
            });

            // iterate newStages and create/update
            newStages.forEach(stage => {
                const dayMap = stageSeries[stage] || {};
                const orderedData = dates.map(d => Number(dayMap[d] ?? 0));
                const elId = safeId(stage);
                const el = document.querySelector('#' + elId);

                // skip if container not present (server-side rendered)
                if (!el) return;

                const opts = buildPerStageAreaOptions(dates, orderedData, stage.replace(/_/g, ' '));
                if (perStageCharts[stage]) {
                    try {
                        perStageCharts[stage].updateOptions(opts);
                    } catch (e) {
                        // if updateOptions fails (rare), destroy + recreate
                        try {
                            perStageCharts[stage].destroy();
                        } catch (ee) {}
                        perStageCharts[stage] = new ApexCharts(el, opts);
                        perStageCharts[stage].render();
                    }
                } else {
                    perStageCharts[stage] = new ApexCharts(el, opts);
                    perStageCharts[stage].render();
                }
            });
        }

        // Render all charts (called on initial load and subsequent poll updates)
        function renderAll(m) {
            m = m || {};

            // KPI updates (defensive DOM checks)
            const setTextIfExists = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.innerText = (typeof value !== 'undefined' && value !== null) ? value : 0;
            };
            setTextIfExists('daily_bonding', m.daily_bonding || 0);
            setTextIfExists('daily_tape_edge_qc', m.daily_tape_edge_qc || 0);
            setTextIfExists('daily_zip_cover_qc', m.daily_zip_cover_qc || 0);
            setTextIfExists('daily_packaging', m.daily_packaging || 0);
            setTextIfExists('total_packaging', m.total_packaging || 0);

            // ---------- stage donut ----------
            const stageCounts = m.stageCounts || {};
            const stageOpts = buildStageDonutOptions(stageCounts);
            const stageContainer = document.querySelector("#stageDonut");
            if (stageContainer) {
                if (stageChart) {
                    // update series & labels only (fast)
                    try {
                        later(() => stageChart.updateOptions({
                            series: stageOpts.series,
                            labels: stageOpts.labels
                        }));
                    } catch (e) {
                        // fallback to re-create
                        try {
                            stageChart.destroy();
                        } catch (ee) {}
                        stageChart = new ApexCharts(stageContainer, stageOpts);
                        stageChart.render().then(setDonutDefaultCenter);
                    }
                    // ensure center text is consistent after update
                    later(setDonutDefaultCenter);
                } else {
                    stageChart = new ApexCharts(stageContainer, stageOpts);
                    stageChart.render().then(setDonutDefaultCenter);
                }
            }

            // ---------- per-stage (30-day area) charts ----------
            // Use Blade-provided QC_DATES and QC_STAGE_SERIES as canonical data
            // BUT if server payload supplies updated qcStageSeries / qcDates, prefer it.
            const dates = (m.qc_dates && Array.isArray(m.qc_dates)) ? m.qc_dates : QC_DATES;
            const stageSeries = (m.qc_stage_series && typeof m.qc_stage_series === 'object') ? m
                .qc_stage_series : QC_STAGE_SERIES;
            renderPerStageCharts(dates, stageSeries);

            // ---------- qc event area (bondingQcEvent) - old single chart (keeps for backward compat) ----------
            // const qcEventsOpts = buildQCeventyOptions(m.qcEventSeries || {});
            // const bondingEl = document.querySelector("#bondingQcEvent");
            // if (bondingEl) {
            //     if (qcEventChart) {
            //         try {
            //             qcEventChart.updateOptions(qcEventsOpts);
            //         } catch (e) {}
            //     } else {
            //         qcEventChart = new ApexCharts(bondingEl, qcEventsOpts);
            //         qcEventChart.render();
            //     }
            // }

            // ---------- qc bar ----------
            const qcOpts = buildQcBarOptions(m.qcCounts || {});
            const qcBarEl = document.querySelector("#qcBar");
            if (qcBarEl) {
                if (qcChart) {
                    try {
                        qcChart.updateOptions(qcOpts);
                    } catch (e) {}
                } else {
                    qcChart = new ApexCharts(qcBarEl, qcOpts);
                    qcChart.render();
                }
            }
        }

        // initial render
        renderAll(metrics);

        // Poll metrics every 10 seconds (keeps original behavior)
        const DASHBOARD_METRICS_URL = "{{ url('dashboard/metrics') }}";
        setInterval(() => {
            axios.get(DASHBOARD_METRICS_URL)
                .then(resp => {
                    if (resp.data) {
                        // reset hover so center shows default for new data
                        hoveredIndex = null;
                        hoveredPercent = null;
                        hoveredLabel = null;
                        renderAll(resp.data);
                    }
                })
                .catch(err => {
                    // keep console output for debugging
                    console.error('Failed to refresh dashboard metrics', err);
                });
        }, 10000);

        // Optional: destroy charts when page unloads to free memory (good practice)
        window.addEventListener('beforeunload', function() {
            try {
                if (stageChart) stageChart.destroy();
                if (qcEventChart) qcEventChart.destroy();
                if (qcChart) qcChart.destroy();
                Object.keys(perStageCharts).forEach(k => {
                    try {
                        perStageCharts[k].destroy();
                    } catch (e) {}
                });
            } catch (e) {
                /* ignore */
            }
        });
    });
</script>
