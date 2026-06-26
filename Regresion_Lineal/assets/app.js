/* ============================================================
   app.js — utilidades compartidas del dashboard de regresion
   - Tema claro/oscuro persistente (localStorage)
   - crearGraficaRegresion(): grafica scatter + linea reutilizable
     con resaltado de outliers y banda de prediccion
   ============================================================ */

const GRAFICAS = []; // registro de graficas activas para refrescar colores al cambiar tema

/* ---------- Tema ---------- */
function temaActual() {
  return document.documentElement.getAttribute('data-theme') || 'dark';
}

function aplicarTema(tema) {
  document.documentElement.setAttribute('data-theme', tema);
  try { localStorage.setItem('tema', tema); } catch (e) {}
  const btn = document.getElementById('theme-toggle');
  if (btn) btn.textContent = tema === 'light' ? '\u{1F319}' : '☀️'; // 🌙 / ☀️
  GRAFICAS.forEach(actualizarColoresGrafica);
}

function initTheme() {
  // El tema ya fue aplicado por el script inline del <head>; aqui solo sincronizamos el boton.
  const btn = document.getElementById('theme-toggle');
  if (btn) {
    btn.textContent = temaActual() === 'light' ? '\u{1F319}' : '☀️';
    btn.addEventListener('click', () => {
      aplicarTema(temaActual() === 'light' ? 'dark' : 'light');
    });
  }
}

function colorVar(nombre) {
  return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

function coloresGrafica() {
  return {
    texto: colorVar('--text-muted'),
    grid:  temaActual() === 'light' ? 'rgba(100,116,139,0.18)' : 'rgba(148,163,184,0.12)',
    punto: colorVar('--accent-soft'),
    linea: colorVar('--orange'),
    outlier: colorVar('--red'),
    banda: temaActual() === 'light' ? 'rgba(37,99,235,0.10)' : 'rgba(96,165,250,0.12)',
  };
}

function actualizarColoresGrafica(chart) {
  if (!chart || !chart.options) return;
  const c = coloresGrafica();
  const sc = chart.options.scales;
  if (sc) {
    ['x', 'y'].forEach(ax => {
      if (!sc[ax]) return;
      sc[ax].grid.color = c.grid;
      sc[ax].ticks.color = c.texto;
      if (sc[ax].title) sc[ax].title.color = c.texto;
    });
  }
  if (chart.options.plugins && chart.options.plugins.legend)
    chart.options.plugins.legend.labels.color = c.texto;
  chart.update('none');
}

/* ---------- Grafica de regresion ---------- */
/*
  cfg = {
    puntos: [{x, y, mes, outlier}],
    linea:  [{x, y}, {x, y}],
    banda:  [{x, yInf, ySup}, ...]   (opcional)
    ejeX, ejeY: etiquetas de los ejes
    prediccion: {x, y}               (opcional, punto destacado)
  }
*/
function crearGraficaRegresion(canvasId, cfg) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const c = coloresGrafica();

  const scatter = cfg.puntos.map(p => ({ x: p.x, y: p.y }));
  const coloresPunto = cfg.puntos.map(p => p.outlier ? c.outlier : c.punto);
  const radios = cfg.puntos.map(p => p.outlier ? 10 : 8);

  const datasets = [];

  // Banda de prediccion (dos lineas que se rellenan entre si)
  if (cfg.banda && cfg.banda.length) {
    datasets.push({
      type: 'line', label: 'Limite superior',
      data: cfg.banda.map(b => ({ x: b.x, y: b.ySup })),
      borderColor: 'transparent', pointRadius: 0, fill: '+1',
      backgroundColor: c.banda, tension: 0, order: 3,
    });
    datasets.push({
      type: 'line', label: 'Limite inferior',
      data: cfg.banda.map(b => ({ x: b.x, y: b.yInf })),
      borderColor: 'transparent', pointRadius: 0, fill: false,
      tension: 0, order: 3,
    });
  }

  datasets.push({
    type: 'scatter', label: 'Datos reales', data: scatter,
    backgroundColor: coloresPunto, pointRadius: radios, pointHoverRadius: 12, order: 1,
  });
  datasets.push({
    type: 'line', label: 'Linea de regresion', data: cfg.linea,
    borderColor: c.linea, borderWidth: 2.5, pointRadius: 0, tension: 0, fill: false, order: 2,
  });

  if (cfg.prediccion) {
    datasets.push({
      type: 'scatter', label: 'Prediccion',
      data: [{ x: cfg.prediccion.x, y: cfg.prediccion.y }],
      backgroundColor: c.linea, borderColor: '#fff', borderWidth: 2,
      pointRadius: 9, pointHoverRadius: 11, order: 0,
    });
  }

  // Limites de ejes calculados a mano: el autoescalado de Chart.js falla cuando
  // hay banda de prediccion (datasets con fill), asi que los fijamos nosotros.
  const ys = [], xs = [];
  cfg.puntos.forEach(p => { ys.push(p.y); xs.push(p.x); });
  cfg.linea.forEach(p => { ys.push(p.y); xs.push(p.x); });
  (cfg.banda || []).forEach(b => { ys.push(b.yInf, b.ySup); xs.push(b.x); });
  if (cfg.prediccion) { ys.push(cfg.prediccion.y); xs.push(cfg.prediccion.x); }
  const yLo = Math.min(...ys), yHi = Math.max(...ys);
  const xLo = Math.min(...xs), xHi = Math.max(...xs);
  const padY = (yHi - yLo) * 0.08 || 1;
  const padX = (xHi - xLo) * 0.04 || 1;

  const meses = cfg.puntos.map(p => p.mes || '');
  const chart = new Chart(ctx, {
    data: { datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: c.texto, filter: it => !it.text.startsWith('Limite') },
        },
        tooltip: {
          callbacks: {
            label: ictx => {
              if (ictx.dataset.label === 'Datos reales')
                return `${meses[ictx.dataIndex] || ''}: (${ictx.raw.x}, ${ictx.raw.y})`;
              if (ictx.dataset.label === 'Prediccion')
                return `Prediccion: (${ictx.raw.x}, ${ictx.raw.y.toFixed(2)})`;
              if (ictx.dataset.label.startsWith('Limite')) return null;
              return `Y = ${ictx.raw.y.toFixed(2)}`;
            },
          },
        },
      },
      scales: {
        x: { type: 'linear', min: xLo - padX, max: xHi + padX, title: { display: true, text: cfg.ejeX || 'X', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grid } },
        y: { min: yLo - padY, max: yHi + padY, title: { display: true, text: cfg.ejeY || 'Y', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grid } },
      },
    },
  });
  GRAFICAS.push(chart);
  return chart;
}

/* ---------- Grafica de residuos ---------- */
/* puntos = [{x, residuo, mes, outlier}] */
function crearGraficaResiduos(canvasId, puntos) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const c = coloresGrafica();
  const data = puntos.map(p => ({ x: p.x, y: p.residuo }));
  const colores = puntos.map(p => p.outlier ? c.outlier : c.punto);
  const xs = puntos.map(p => p.x);
  const xMin = Math.min(...xs), xMax = Math.max(...xs);
  const meses = puntos.map(p => p.mes || '');

  const chart = new Chart(ctx, {
    data: {
      datasets: [
        { type: 'scatter', label: 'Residuo', data, backgroundColor: colores, pointRadius: 7, pointHoverRadius: 10, order: 1 },
        { type: 'line', label: 'Cero', data: [{ x: xMin, y: 0 }, { x: xMax, y: 0 }], borderColor: c.linea, borderWidth: 1.5, borderDash: [6, 4], pointRadius: 0, fill: false, order: 2 },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { labels: { color: c.texto } },
        tooltip: { callbacks: { label: ictx => ictx.dataset.label === 'Residuo'
          ? `${meses[ictx.dataIndex] || ''}: residuo ${ictx.raw.y.toFixed(2)}` : null } },
      },
      scales: {
        x: { type: 'linear', title: { display: true, text: 'Inversion (X)', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grid } },
        y: { title: { display: true, text: 'Residuo (Y - Y estimado)', color: c.texto }, ticks: { color: c.texto }, grid: { color: c.grid } },
      },
    },
  });
  GRAFICAS.push(chart);
  return chart;
}

document.addEventListener('DOMContentLoaded', initTheme);
