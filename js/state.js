const State = (() => {
  let currentUser        = null;
  let plants             = [];
  let alarms             = [];
  let speciesDB          = [];
  let filterStatus       = 'all';
  let dashChartType      = 'Umidita_Suolo';
  let selectedSpecies    = null;
  let addStep            = 1;
  let selectedChartPlant = null;
  let confirmCallback    = null;

  return {
    getUser:      ()  => currentUser,
    setUser:      (u) => { currentUser = u; },

    getPlants:    ()  => plants,
    setPlants:    (p) => {
      plants = p;
      if (!selectedChartPlant && plants.length) {
        selectedChartPlant = plants[0].ID_Esemplare ?? plants[0].id;
      }
    },

    getAlarms:    ()  => alarms,
    setAlarms:    (a) => { alarms = a; },

    getSpeciesDB: ()  => speciesDB,
    setSpeciesDB: (s) => { speciesDB = s; },

    getFilter:     ()  => filterStatus,
    setFilter:     (f) => { filterStatus = f; },

    getDashChart:  ()  => dashChartType,
    setDashChart:  (t) => { dashChartType = t; },

    getSelSpecies: ()  => selectedSpecies,
    setSelSpecies: (s) => { selectedSpecies = s; },

    getAddStep:    ()  => addStep,
    setAddStep:    (n) => { addStep = n; },

    getChartPlant: ()  => selectedChartPlant,
    setChartPlant: (id)=> { selectedChartPlant = id; },

    getConfirmCb:  ()  => confirmCallback,
    setConfirmCb:  (cb)=> { confirmCallback = cb; },

    getLivePlant:  ()  => plants.find(p => p.live) || plants[0] || null,
    unreadAlarms:  ()  => alarms.filter(a => !a.Letto_Da_Utente && !a.read).length,

    addPlant(p)  { plants.push(p); },
    removePlant(id) {
      plants = plants.filter(p => (p.ID_Esemplare ?? p.id) !== id);
      alarms = alarms.filter(a => (a.ID_Esemplare ?? a.plantId) !== id);
      if (selectedChartPlant === id) {
        selectedChartPlant = plants.length ? (plants[0].ID_Esemplare ?? plants[0].id) : null;
      }
    },
  };
})();
