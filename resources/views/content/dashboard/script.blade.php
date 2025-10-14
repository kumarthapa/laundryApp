<script>
    document.addEventListener('DOMContentLoaded', function() {
        // initial data from server
        const metrics = window.metrics || {};

        // --- global chart/state vars (single declaration) ---
        let stageChart = null,
            qcEventChart = null,
            qcChart = null;
        let hoveredIndex = null;
        let hoveredPercent = null;
        let hoveredLabel = null;

        // keep the current series/labels so we don't touch internal Apex globals
        let currentLabels = [];
        let currentSeries = [];

        // safe update helper (ensure chart DOM exists and swallow errors)
        function safeUpdate(opts) {
            if (!stageChart) return;
            try {
                // small timeout lets apex create DOM nodes before update runs
                window.setTimeout(() => {
                    try {
                        stageChart.updateOptions(opts, false, false, false);
                    } catch (e) {
                        // swallow - prevents "getAttribute / filter" errors when DOM not ready
                        // console.warn('safeUpdate failed', e);
                    }
                }, 0);
            } catch (e) {
                // ignore
            }
        }

        // central value: show percentage while hovering; otherwise show first stage count
        function centerValueFormatter(_val, _opts) {
            try {
                if (hoveredIndex !== null && hoveredPercent !== null) {
                    return hoveredPercent.toFixed(1) + '%';
                }
                // default: first series count (or 0)
                return (Array.isArray(currentSeries) && currentSeries.length) ? (currentSeries[0] || 0) : 0;
            } catch (e) {
                return '';
            }
        }

        // central name: show hovered name while hovering; otherwise first label
        function centerNameFormatter(name, _opts) {
            try {
                if (hoveredIndex !== null && hoveredLabel) return hoveredLabel;
                return (Array.isArray(currentLabels) && currentLabels.length) ? (currentLabels[0] || name) :
                    name;
            } catch (e) {
                return name;
            }
        }

        // slice label: show raw count for each slice
        function sliceDataLabelFormatter(val, opts) {
            try {
                const idx = opts.seriesIndex;
                return (Array.isArray(currentSeries) && typeof currentSeries[idx] !== 'undefined') ?
                    currentSeries[idx] : val;
            } catch (e) {
                return val;
            }
        }

        // Build donut options (keeps currentSeries/currentLabels in sync)
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
                        // hover -> update center DOM directly
                        dataPointMouseEnter: function(event, chartContext, config) {
                            const idx = config.dataPointIndex;
                            if (typeof idx === 'number' && Array.isArray(currentSeries)) {
                                hoveredIndex = idx;
                                const total = currentSeries.reduce((a, b) => a + (Number(b) || 0), 0);
                                hoveredPercent = (total > 0) ? (currentSeries[idx] / total * 100) : 0;
                                hoveredLabel = currentLabels[idx] || null;

                                // update center text directly (no updateOptions)
                                updateDonutCenter(hoveredLabel, hoveredPercent.toFixed(1) + '%');
                            }
                        },
                        // leave -> revert to default center
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

                // disable apex tooltip to avoid the popup blinking
                tooltip: {
                    enabled: false
                },

                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        try {
                            const idx = opts.seriesIndex;
                            return (Array.isArray(currentSeries) && typeof currentSeries[idx] !==
                                    'undefined') ?
                                currentSeries[idx] :
                                val;
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
                                // initial formatters won't be re-bound on hover; we update DOM directly
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

        /* Helper: update donut center DOM elements directly to avoid re-render 22 */
        function updateDonutCenter(name, value) {
            try {
                const container = document.querySelector('#stageDonut');
                if (!container) return;
                // ApexCharts renders the labels inside .apexcharts-donut-label
                const nameEl = container.querySelector('.apexcharts-donut-label .apexcharts-donut-label-name');
                const valueEl = container.querySelector(
                    '.apexcharts-donut-label .apexcharts-donut-label-value');
                if (nameEl) nameEl.textContent = name ?? '';
                if (valueEl) valueEl.textContent = value ?? '';
            } catch (e) {
                // ignore silently
            }
        }

        /* Helper: set default center to first label + first count (call after render or on mouse leave) */
        function setDonutDefaultCenter() {
            try {
                const defaultName = (Array.isArray(currentLabels) && currentLabels.length) ? currentLabels[0] :
                    '';
                const defaultCount = (Array.isArray(currentSeries) && currentSeries.length) ? (currentSeries[
                    0] || 0) : 0;
                updateDonutCenter(defaultName, defaultCount);
            } catch (e) {}
        }


        // Daily throughput chart builder (unchanged)
        function buildQCeventyOptions(dailySeries) {
            const categories = Object.keys(dailySeries || {});
            const series = [{
                name: 'QC Events',
                data: Object.values(dailySeries || {})
            }];
            return {
                chart: {
                    type: 'area',
                    height: 320,
                    zoom: {
                        enabled: false
                    }
                },
                series: series,
                xaxis: {
                    categories: categories
                },
                yaxis: {
                    title: {
                        text: 'QC Events'
                    }
                }
            };
        }

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

        // Render all charts
        function renderAll(m) {
            // KPI updates
            if (m.daily_bonding !== undefined) document.getElementById('daily_bonding').innerText = m
                .daily_bonding || 0;
            if (m.daily_tape_edge_qc !== undefined) document.getElementById('daily_tape_edge_qc').innerText = m
                .daily_tape_edge_qc || 0;
            if (m.daily_zip_cover_qc !== undefined) document.getElementById('daily_zip_cover_qc').innerText = m
                .daily_zip_cover_qc || 0;
            if (m.daily_packaging !== undefined) document.getElementById('daily_packaging').innerText = m
                .daily_packaging || 0;
            if (m.total_packaging !== undefined) document.getElementById('total_packaging').innerText = m
                .total_packaging || 0;

            // stage donut
            const stageOpts = buildStageDonutOptions(m.stageCounts || {});
            if (stageChart) {
                // update; safeUpdate will prevent DOM timing errors
                try {
                    stageChart.updateOptions(stageOpts);
                } catch (e) {}
            } else {
                stageChart = new ApexCharts(document.querySelector("#stageDonut"), stageOpts);
                stageChart.render();
            }

            // QC Event
            const qcEventsOpts = buildQCeventyOptions(m.qcEventSeries || {});
            if (qcEventChart) qcEventChart.updateOptions(qcEventsOpts);
            else {
                qcEventChart = new ApexCharts(document.querySelector("#bondingQcEvent"), qcEventsOpts);
                qcEventChart.render();
            }

            // qc
            const qcOpts = buildQcBarOptions(m.qcCounts || {});
            if (qcChart) qcChart.updateOptions(qcOpts);
            else {
                qcChart = new ApexCharts(document.querySelector("#qcBar"), qcOpts);
                qcChart.render();
            }
        }

        // initial render
        renderAll(metrics);

        // Poll metrics every 10 seconds
        const DASHBOARD_METRICS_URL = "{{ url('dashboard/metrics') }}";
        setInterval(() => {
            axios.get(DASHBOARD_METRICS_URL)
                .then(resp => {
                    if (resp.data) {
                        // Reset hover state so new data shows default center
                        hoveredIndex = null;
                        hoveredPercent = null;
                        hoveredLabel = null;
                        renderAll(resp.data);
                    }
                })
                .catch(err => {
                    console.error('Failed to refresh dashboard metrics', err);
                });
        }, 10000);
    });
</script>
