<script>
    document.addEventListener('DOMContentLoaded', function() {
        // initial data from server
        const metrics = window.metrics || {};

        // Convert server stageCounts (object) to series + labels for donut
        function buildStageDonutOptions(stageCounts) {
            const labels = Object.keys(stageCounts);
            const series = labels.map(lbl => stageCounts[lbl] || 0);
            return {
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: labels,
                series: series,
                legend: {
                    position: 'bottom'
                },
            };
        }

        // Daily throughput chart
        function buildDailyOptions(dailySeries) {
            const categories = Object.keys(dailySeries);
            const series = [{
                name: 'Events',
                data: Object.values(dailySeries)
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
                        text: 'Events'
                    }
                }
            };
        }

        // QC bar chart
        function buildQcBarOptions(qcCounts) {
            const labels = Object.keys(qcCounts);
            const data = Object.values(qcCounts);
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

        // Render charts
        let stageChart, dailyChart, qcChart;

        function renderAll(m) {
            // KPI updates
            if (m.totalProducts !== undefined) document.getElementById('kpi-total-products').innerText = m
                .totalProducts;
            if (m.totalProductsToday !== undefined) {
                document.getElementById('kpi-shipped').innerText = (m.stageCounts['packaging'] || 0);
                // in-daily-production calculation
                let total = 0;
                Object.values(m.totalProductsToday).forEach(v => total += v);
                const inProd = total || 0;
                document.getElementById('kpi-in-daily-production').innerText = inProd;
            }
            if (m.qcPassRate !== undefined) document.getElementById('kpi-qc-pass').innerText = m.qcPassRate !==
                null ? m.qcPassRate + '%' : 'N/A';

            // stage donut
            const stageOpts = buildStageDonutOptions(m.stageCounts || {});
            if (stageChart) stageChart.updateOptions(stageOpts);
            else {
                stageChart = new ApexCharts(document.querySelector("#stageDonut"), stageOpts);
                stageChart.render();
            }

            // daily
            const dailyOpts = buildDailyOptions(m.dailySeries || {});
            if (dailyChart) dailyChart.updateOptions(dailyOpts);
            else {
                dailyChart = new ApexCharts(document.querySelector("#dailyThroughput"), dailyOpts);
                dailyChart.render();
            }

            // qc
            const qcOpts = buildQcBarOptions(m.qcCounts || {});
            if (qcChart) qcChart.updateOptions(qcOpts);
            else {
                qcChart = new ApexCharts(document.querySelector("#qcBar"), qcOpts);
                qcChart.render();
            }

            // ---------------- recent activity table refresh --------------------
            // if (Array.isArray(m.recentActivities)) {
            //     const body = document.getElementById('recent-activity-body');
            //     body.innerHTML = '';
            //     m.recentActivities.forEach(act => {
            //         const tr = document.createElement('tr');
            //         tr.innerHTML = `<td>${act.changed_at}</td>
            //                     <td>${act.sku}</td>
            //                     <td>${act.qa_code || ''}</td>
            //                     <td>${act.stage}</td>
            //                     <td>${act.status}</td>
            //                     <td>${(act.comments||'').substring(0,80)}</td>`;
            //         body.appendChild(tr);
            //     });
            // }
        }

        // initial render
        renderAll(metrics);

        // Poll metrics every 30 seconds (adjust as needed)
        // setInterval(() => {
        //     axios.get('/dashboard/metrics')
        //         .then(resp => {
        //             console.log(resp)
        //             if (resp.data) {
        //                 renderAll(resp.data);
        //             }
        //         })
        //         .catch(err => {
        //             console.error('Failed to refresh dashboard metrics', err);
        //         });
        // }, 30000);


        const DASHBOARD_METRICS_URL = "{{ url('dashboard/metrics') }}";
        setInterval(() => {
            axios.get(DASHBOARD_METRICS_URL)
                .then(resp => {
                    console.log(resp)
                    if (resp.data) {
                        renderAll(resp.data);
                    }
                })
                .catch(err => {
                    console.error('Failed to refresh dashboard metrics', err);
                });
        }, 10000);
    });
</script>
