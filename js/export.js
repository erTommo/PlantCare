const Export = (() => {

  function plantsCSV() {
    UI.showCsvProgress('Preparazione dati piante...', () => {
      const headers = ['ID_Esemplare','Soprannome','Specie','Stato','Umidita_Suolo','Temperatura','Luminosita'];
      const rows    = State.getPlants().map(raw => {
        const p = window._normalizePlant ? window._normalizePlant(raw) : raw;
        return [
          p.id, p.nick, p.species, p.status,
          p.s.umidita_suolo ?? '—', p.s.temperatura ?? '—', p.s.luminosita ?? '—',
        ];
      });
      Utils.downloadCSV('piante_' + Utils.dateStr() + '.csv', headers, rows);
      UI.toast('📥', 'CSV piante esportato!');
    });
  }

  function alarmsCSV() {
    UI.showCsvProgress('Preparazione dati allarmi...', () => {
      const headers = ['ID_Allarme','Pianta','Tipo_Allarme','Data_Ora','Valore_Rilevato','Letto'];
      const rows    = State.getAlarms().map(raw => {
        const a = window._normalizeAlarm ? window._normalizeAlarm(raw) : raw;
        return [a.id, a.plant, a.type, a.time, a.val, a.read ? 'Sì' : 'No'];
      });
      Utils.downloadCSV('allarmi_' + Utils.dateStr() + '.csv', headers, rows);
      UI.toast('📥', 'CSV allarmi esportato!');
    });
  }

  function backupJSON() {
    const user = State.getUser();
    Utils.downloadJSON('plantcare_backup_' + Utils.dateStr() + '.json', {
      export_date: new Date().toISOString(),
      user:        { nome: user?.Nome ?? user?.nome, email: user?.Email ?? user?.email },
      piante:      State.getPlants(),
      allarmi:     State.getAlarms(),
    });
    UI.toast('📥', 'Backup JSON scaricato!');
  }

  function singlePlantCSV(id) {
    const raw = State.getPlants().find(x => (x.ID_Esemplare ?? x.id) === id);
    if (!raw) return;
    const p = window._normalizePlant ? window._normalizePlant(raw) : raw;
    const headers = ['Ora','Umidita_Suolo','Temperatura','Luminosita'];
    const rows    = Array.from({ length: 10 }, (_, i) => {
      const d = new Date(Date.now() - i * 3600000);
      return [
        d.toLocaleTimeString('it-IT'),
        p.s.umidita_suolo !== null ? Math.round(p.s.umidita_suolo + Utils.rand(-5, 5)) : '—',
        p.s.temperatura   !== null ? Math.round((p.s.temperatura  + Utils.rand(-1, 1)) * 10) / 10 : '—',
        p.s.luminosita    !== null ? Math.round(p.s.luminosita    + Utils.rand(-100, 100)) : '—',
      ];
    });
    Utils.downloadCSV(`${p.nick}_rilevazioni_${Utils.dateStr()}.csv`, headers, rows);
    UI.toast('📥', `CSV ${p.nick} esportato!`);
    UI.closeModalDirect();
  }

  return { plantsCSV, alarmsCSV, backupJSON, singlePlantCSV };
})();
