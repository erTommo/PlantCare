const Charts = (() => {

  function render(id, data, dims, color, tMin = null, tMax = null, dataMin = null, dataMax = null) {
    const el = document.getElementById(id);
    if (!el) return;

    const W = dims.w, H = dims.h, pad = 14;
    const mn    = dataMin !== null ? dataMin : Math.min(...data) * 0.9;
    const mx    = dataMax !== null ? dataMax : Math.max(...data) * 1.05;
    const range = mx - mn || 1;

    const pts = data.map((v, i) => [
      pad + (i / (data.length - 1)) * (W - pad * 2),
      H - pad - ((v - mn) / range) * (H - pad * 2)
    ]);

    const pathD = pts.map((p, i) => (i === 0 ? `M${p[0]},${p[1]}` : `L${p[0]},${p[1]}`)).join(' ');
    const areaD = pathD + ` L${pts[pts.length - 1][0]},${H} L${pts[0][0]},${H} Z`;

    let threshLines = '';
    if (tMin !== null && tMax !== null) {
      const yMn = Utils.clamp(H - pad - ((tMin - mn) / range) * (H - pad * 2), 0, H);
      const yMx = Utils.clamp(H - pad - ((tMax - mn) / range) * (H - pad * 2), 0, H);
      threshLines = `
        <line x1="${pad}" y1="${yMn}" x2="${W - pad}" y2="${yMn}" stroke="#e9a825" stroke-width="1" stroke-dasharray="5,4" opacity=".4"/>
        <line x1="${pad}" y1="${yMx}" x2="${W - pad}" y2="${yMx}" stroke="#5ab8d8" stroke-width="1" stroke-dasharray="5,4" opacity=".4"/>`;
    }

    const dots = pts
      .filter((_, i) => i % 6 === 0)
      .map(p => `<circle cx="${p[0]}" cy="${p[1]}" r="2.5" fill="${color}" opacity=".8"/>`)
      .join('');

    el.innerHTML = `
      <defs>
        <linearGradient id="g${id}" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%"   stop-color="${color}" stop-opacity=".2"/>
          <stop offset="100%" stop-color="${color}" stop-opacity="0"/>
        </linearGradient>
      </defs>
      ${threshLines}
      <path d="${areaD}" fill="url(#g${id})"/>
      <path d="${pathD}" fill="none" stroke="${color}" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
      ${dots}`;
  }

  function renderSimple(id, data, dims, color) {
    render(id, data, dims, color);
  }

  return { render, renderSimple };
})();
