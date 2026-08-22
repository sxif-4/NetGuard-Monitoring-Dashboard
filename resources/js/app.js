import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.Chart = Chart;

function hexToRgba(hex, alpha) {
    const value = hex.replace('#', '');
    const r = parseInt(value.substring(0, 2), 16);
    const g = parseInt(value.substring(2, 4), 16);
    const b = parseInt(value.substring(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function buildData(points, label, color) {
    return {
        labels: points.map((point) => point.bucket),
        datasets: [{
            label,
            data: points.map((point) => point.value),
            borderColor: color,
            backgroundColor: hexToRgba(color, 0.1),
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 0,
            pointHitRadius: 12,
            spanGaps: true,
        }],
    };
}

function buildOptions(label, unit) {
    // Chart.js draws to a <canvas>, which can't pick up CSS `dark:` variants --
    // read the theme once at build time instead so gridlines/ticks are legible
    // against both the light and the dark (navy) page background.
    const dark = document.documentElement.classList.contains('dark');
    const gridColor = dark ? 'rgba(255, 255, 255, 0.06)' : '#e1e0d9';
    const tickColor = dark ? '#71717a' : '#898781';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
            x: {
                grid: { display: false },
                ticks: { maxTicksLimit: 6, color: tickColor },
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: {
                    color: tickColor,
                    callback: (value) => `${value}${unit}`,
                },
            },
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => `${label}: ${context.formattedValue}${unit}`,
                },
            },
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('trendChart', ({ chartId, label, unit, color, points }) => {
        // A Chart.js instance is a complex object graph (canvas context, plugins,
        // internal getters/setters, circular refs) that must never be wrapped by
        // Alpine's reactivity Proxy — storing it as `this.chart` causes Alpine to
        // recurse into it and blow the call stack. Keeping it in this closure
        // instead of on the returned (reactive) object sidesteps that entirely.
        let chart = null;
        let listener = null;
        const eventName = `metrics-series-updated-${chartId}`;

        return {
            init() {
                // Livewire's morph/hydration can re-run x-init on an already-
                // initialized canvas (e.g. on wire:navigate) — destroy any prior
                // instance first so Chart.js doesn't throw "canvas already in use".
                window.Chart.getChart(this.$refs.canvas)?.destroy();

                chart = new window.Chart(this.$refs.canvas, {
                    type: 'line',
                    data: buildData(points, label, color),
                    options: buildOptions(label, unit),
                });

                listener = (event) => {
                    const updated = event.detail.points;
                    chart.data.labels = updated.map((point) => point.bucket);
                    chart.data.datasets[0].data = updated.map((point) => point.value);
                    chart.update();
                };
                window.addEventListener(eventName, listener);
            },

            destroy() {
                window.removeEventListener(eventName, listener);
                chart?.destroy();
            },
        };
    });
});
