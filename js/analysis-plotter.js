'use strict';

/**
 * Plot result from the beam analysis calculation into a graph
 */
class AnalysisPlotter {
    constructor(container) {
        this.container = container;
    }

    /**
     * Plot equation.
     *
     * @param {Object{beam : Beam, load : float, equation: Function}}  The equation data
     */
    plot(data) {
        let totalL = data.beam.primarySpan + (data.beam.secondarySpan || 0);
        let points = [];
        let steps = 200;

        for(let i = 0; i <= steps; i++) {
           let x = (totalL * i) / steps;
           let result = data.equation(x);
           points.push({ x: x, y: result.y || 0 });
        }
        
        let canvas = document.getElementById(this.container);
        let ctx = canvas.getContext('2d');
        
        let labelArray = this.container.replace('_plot', '').split('_');
        let label = labelArray.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

        if (this.chart) {
            this.chart.destroy();
        }

        this.chart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: label,
                    data: points,
                    borderColor: 'blue',
                    backgroundColor: 'rgba(0, 0, 255, 0.1)',
                    borderWidth: 2,
                    showLine: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: { display: true, text: 'Distance (m)' }
                    },
                    y: {
                        title: { display: true, text: label }
                    }
                }
            }
        });
    }
}