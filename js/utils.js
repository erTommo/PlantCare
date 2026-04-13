const Utils = (() => {
  function rand(a, b) { return Math.random() * (b - a) + a; }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function chartColor(type) {
    return { umidita: '#82d44e', temp: '#e9a825', luce: '#5ab8d8' }[type];
  }
  function dateStr() { return new Date().toISOString().split('T')[0].replace(/-/g, ''); }
  function fmtLux(v) { return v > 999 ? (v / 1000).toFixed(1) + 'k' : String(v); }
  function downloadCSV(filename, headers, rows) {
    const csv = [headers.join(','), ...rows.map(r => r.map(v => `"${v}"`).join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
  }
  function downloadJSON(filename, data) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
  }
  return { rand, clamp, chartColor, dateStr, fmtLux, downloadCSV, downloadJSON };
})();
