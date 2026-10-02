/**
 * RosenCharts - D3.js Visual Charts Library for Dkript Enterprise Systems
 * Con soporte responsivo universal (Apple iPad/iPhone, Samsung, Xiaomi, Mac y Laptops)
 */

const RosenCharts = {
    // Registro interno para re-renderizado automático en redimensión/rotación
    _registry: {},

    // Comportamiento Seguro ante Estado Vacío
    renderEmptyState(container, customMessage) {
        const el = typeof container === 'string' ? document.getElementById(container) : container;
        if (!el) return;

        el.innerHTML = `
            <div class="rosen-empty-state">
                <i class="bi bi-bar-chart-line rosen-empty-state-icon"></i>
                <p class="rosen-empty-state-text">
                    ${customMessage || 'Las métricas se activarán automáticamente al registrar los primeros datos.'}
                </p>
            </div>
        `;
    },

    // 1. Donut Chart (Distribución)
    renderDonut(containerId, data, options = {}) {
        this._registry[containerId] = { type: 'donut', data, options };
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!data || data.length === 0 || data.every(d => d.value === 0)) {
            this.renderEmptyState(container, options.emptyMessage);
            return;
        }

        container.innerHTML = '';
        const baseWidth = Math.max(container.clientWidth || 320, 240);
        const baseHeight = options.height || 260;
        const radius = Math.min(baseWidth, baseHeight) / 2 - 20;

        const svg = d3.select(container)
            .append('svg')
            .attr('viewBox', `0 0 ${baseWidth} ${baseHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${baseWidth / 2}, ${baseHeight / 2})`);

        const defaultPalette = ['#0062f5', '#00d4ff', '#7928ca', '#10b981', '#f59e0b', '#ec4899', '#06b6d4'];
        const color = d3.scaleOrdinal()
            .domain(data.map(d => d.label))
            .range(data.map((d, i) => d.color || defaultPalette[i % defaultPalette.length]));

        const pie = d3.pie()
            .value(d => d.value)
            .sort(null);

        const arc = d3.arc()
            .innerRadius(radius * 0.6)
            .outerRadius(radius);

        const total = d3.sum(data, d => d.value);

        // Tooltip
        const tooltip = d3.select(container)
            .append('div')
            .attr('class', 'rosen-tooltip');

        svg.selectAll('path')
            .data(pie(data))
            .enter()
            .append('path')
            .attr('d', arc)
            .attr('fill', d => color(d.data.label))
            .attr('stroke', '#ffffff')
            .style('stroke-width', '2px')
            .style('cursor', 'pointer')
            .on('mouseover', (event, d) => {
                tooltip.style('opacity', 1)
                    .html(`${d.data.label}: <strong>${d.data.value}</strong> (${((d.data.value / total) * 100).toFixed(1)}%)`);
            })
            .on('mousemove', (event) => {
                tooltip.style('left', (event.offsetX + 10) + 'px')
                    .style('top', (event.offsetY - 25) + 'px');
            })
            .on('mouseout', () => {
                tooltip.style('opacity', 0);
            });

        // Texto central
        svg.append('text')
            .attr('text-anchor', 'middle')
            .attr('dy', '-0.1em')
            .style('font-size', '24px')
            .style('font-weight', '800')
            .style('fill', '#1e293b')
            .text(total);

        svg.append('text')
            .attr('text-anchor', 'middle')
            .attr('dy', '1.4em')
            .style('font-size', '12px')
            .style('font-weight', '600')
            .style('fill', '#64748b')
            .text(options.centerLabel || 'Total');

        // Leyenda adaptable
        const legendDiv = document.createElement('div');
        legendDiv.className = 'rosen-legend';
        data.forEach(d => {
            const item = document.createElement('div');
            item.className = 'rosen-legend-item';
            item.innerHTML = `<span class="rosen-legend-bullet" style="background:${d.color || '#3b82f6'};"></span>${d.label}: <strong>${d.value}</strong>`;
            legendDiv.appendChild(item);
        });
        container.appendChild(legendDiv);
    },

    // 2. Area / Bar Chart (Actividad con Curva Suave Responsiva)
    renderArea(containerId, data, options = {}) {
        this._registry[containerId] = { type: 'area', data, options };
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!data || data.length === 0 || data.every(d => d.value === 0)) {
            this.renderEmptyState(container, options.emptyMessage);
            return;
        }

        container.innerHTML = '';
        const margin = { top: 20, right: 15, bottom: 30, left: 35 };
        const baseWidth = Math.max(container.clientWidth || 360, 240);
        const baseHeight = options.height || 260;
        const width = baseWidth - margin.left - margin.right;
        const height = baseHeight - margin.top - margin.bottom;

        const svg = d3.select(container)
            .append('svg')
            .attr('viewBox', `0 0 ${baseWidth} ${baseHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left}, ${margin.top})`);

        const x = d3.scalePoint()
            .domain(data.map(d => d.period))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.value) * 1.15 || 10])
            .range([height, 0]);

        // Gradiente
        const defs = svg.append('defs');
        const gradient = defs.append('linearGradient')
            .attr('id', 'area-gradient-' + containerId)
            .attr('x1', '0%').attr('y1', '0%')
            .attr('x2', '0%').attr('y2', '100%');

        gradient.append('stop').attr('offset', '0%').attr('stop-color', '#0062f5').attr('stop-opacity', 0.45);
        gradient.append('stop').attr('offset', '100%').attr('stop-color', '#00d4ff').attr('stop-opacity', 0.02);

        // Curva de Área
        const area = d3.area()
            .x(d => x(d.period))
            .y0(height)
            .y1(d => y(d.value))
            .curve(d3.curveMonotoneX);

        // Línea superior
        const line = d3.line()
            .x(d => x(d.period))
            .y(d => y(d.value))
            .curve(d3.curveMonotoneX);

        svg.append('path')
            .datum(data)
            .attr('fill', `url(#area-gradient-${containerId})`)
            .attr('d', area);

        svg.append('path')
            .datum(data)
            .attr('fill', 'none')
            .attr('stroke', '#0062f5')
            .attr('stroke-width', 3)
            .attr('d', line);

        // Puntos
        svg.selectAll('circle')
            .data(data)
            .enter()
            .append('circle')
            .attr('cx', d => x(d.period))
            .attr('cy', d => y(d.value))
            .attr('r', 5)
            .attr('fill', '#ffffff')
            .attr('stroke', '#0062f5')
            .attr('stroke-width', 2.5);

        // Ejes
        svg.append('g')
            .attr('transform', `translate(0, ${height})`)
            .call(d3.axisBottom(x).tickSize(0))
            .attr('color', '#94a3b8')
            .style('font-size', '11px')
            .select('.domain').remove();

        svg.append('g')
            .call(d3.axisLeft(y).ticks(4).tickSize(-width))
            .attr('color', '#e2e8f0')
            .style('font-size', '11px')
            .select('.domain').remove();
    },

    // 3. Line Chart (Tendencias y Promedios)
    renderLine(containerId, data, options = {}) {
        this._registry[containerId] = { type: 'line', data, options };
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!data || data.length === 0 || data.every(d => d.value === 0)) {
            this.renderEmptyState(container, options.emptyMessage);
            return;
        }

        container.innerHTML = '';
        const margin = { top: 20, right: 15, bottom: 30, left: 35 };
        const baseWidth = Math.max(container.clientWidth || 360, 240);
        const baseHeight = options.height || 260;
        const width = baseWidth - margin.left - margin.right;
        const height = baseHeight - margin.top - margin.bottom;

        const svg = d3.select(container)
            .append('svg')
            .attr('viewBox', `0 0 ${baseWidth} ${baseHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left}, ${margin.top})`);

        const x = d3.scalePoint()
            .domain(data.map(d => d.label))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.value) * 1.15 || 10])
            .range([height, 0]);

        const line = d3.line()
            .x(d => x(d.label))
            .y(d => y(d.value))
            .curve(d3.curveCatmullRom);

        svg.append('path')
            .datum(data)
            .attr('fill', 'none')
            .attr('stroke', '#7928ca')
            .attr('stroke-width', 3)
            .attr('d', line);

        svg.selectAll('circle')
            .data(data)
            .enter()
            .append('circle')
            .attr('cx', d => x(d.label))
            .attr('cy', d => y(d.value))
            .attr('r', 5)
            .attr('fill', '#ffffff')
            .attr('stroke', '#7928ca')
            .attr('stroke-width', 2.5);

        svg.append('g')
            .attr('transform', `translate(0, ${height})`)
            .call(d3.axisBottom(x).tickSize(0))
            .attr('color', '#94a3b8')
            .style('font-size', '11px')
            .select('.domain').remove();

        svg.append('g')
            .call(d3.axisLeft(y).ticks(4).tickSize(-width))
            .attr('color', '#e2e8f0')
            .style('font-size', '11px')
            .select('.domain').remove();
    },

    // Redibujado automático con debounce ante cambio de tamaño o rotación (portrait / landscape)
    refreshAll() {
        Object.keys(this._registry).forEach(id => {
            const item = this._registry[id];
            if (item.type === 'donut') this.renderDonut(id, item.data, item.options);
            else if (item.type === 'area') this.renderArea(id, item.data, item.options);
            else if (item.type === 'line') this.renderLine(id, item.data, item.options);
        });
    }
};

// Listener global para redimensionamiento fluido en Mac, tablets y móviles
let _rosenResizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(_rosenResizeTimeout);
    _rosenResizeTimeout = setTimeout(() => {
        RosenCharts.refreshAll();
    }, 200);
});

window.addEventListener('orientationchange', () => {
    setTimeout(() => {
        RosenCharts.refreshAll();
    }, 300);
});
