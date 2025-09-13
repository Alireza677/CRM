export default function geoPicker(initialState = '', initialCity = '', locations = {}) {
    return {
      locations,
      state: initialState || '',
      city: initialCity || '',
  
      openState: false,
      openCity: false,
  
      stateQuery: '',
      cityQuery: '',
  
      filteredStates: [],
      filteredCities: [],
  
      init() {
        this.filteredStates = Object.keys(this.locations);
        this.filteredCities = this.state && this.locations[this.state] ? this.locations[this.state] : [];
      },
  
      toggleMenu(which) {
        if (which === 'state') {
          this.openState = !this.openState;
          this.openCity = false;
          if (this.openState) {
            this.stateQuery = '';
            this.filterStates();
          }
        } else if (which === 'city') {
          if (!this.state) return;
          this.openCity = !this.openCity;
          this.openState = false;
          if (this.openCity) {
            this.cityQuery = '';
            this.filterCities();
          }
        }
      },
  
      selectState(s) {
        this.state = s;
        this.city = '';
        this.openState = false;
        this.filteredCities = this.locations[this.state] || [];
      },
  
      selectCity(c) {
        this.city = c;
        this.openCity = false;
      },
  
      filterStates() {
        const q = (this.stateQuery || '').trim();
        const all = Object.keys(this.locations);
        this.filteredStates = q ? all.filter(s => s.includes(q)) : all;
      },
  
      filterCities() {
        const q = (this.cityQuery || '').trim();
        const all = this.locations[this.state] || [];
        this.filteredCities = q ? all.filter(c => c.includes(q)) : all;
      },
    }
  }
  