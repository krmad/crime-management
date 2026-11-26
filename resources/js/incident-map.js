import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

function tileLayer(config) {
    return L.tileLayer(config.tileUrl, {
        attribution: config.attribution,
        maxZoom: 19,
    });
}

export function registerIncidentMapComponents(Alpine) {
    Alpine.data('incidentLocationPicker', (config) => ({
        latitude: config.latitude ?? null,
        longitude: config.longitude ?? null,
        query: '',
        results: [],
        searching: false,
        searchMessage: '',
        map: null,
        marker: null,

        init() {
            const center = this.hasCoordinates()
                ? [Number(this.latitude), Number(this.longitude)]
                : config.center;

            this.map = L.map(this.$refs.map, { scrollWheelZoom: false })
                .setView(center, this.hasCoordinates() ? config.detailZoom : config.zoom);

            tileLayer(config).addTo(this.map);

            if (this.hasCoordinates()) {
                this.placeMarker(center[0], center[1], false);
            }

            this.map.on('click', (event) => {
                this.selectCoordinates(event.latlng.lat, event.latlng.lng);
            });
        },

        hasCoordinates() {
            return this.latitude !== null
                && this.longitude !== null
                && String(this.latitude).trim() !== ''
                && String(this.longitude).trim() !== ''
                && Number.isFinite(Number(this.latitude))
                && Number.isFinite(Number(this.longitude));
        },

        get coordinatesLabel() {
            return this.hasCoordinates()
                ? `${this.latitude}, ${this.longitude}`
                : config.strings.noCoordinates;
        },

        get osmUrl() {
            if (! this.hasCoordinates()) {
                return '#';
            }

            const latitude = Number(this.latitude);
            const longitude = Number(this.longitude);

            return `https://www.openstreetmap.org/?mlat=${latitude}&mlon=${longitude}#map=16/${latitude}/${longitude}`;
        },

        selectCoordinates(latitude, longitude) {
            this.latitude = latitude.toFixed(7);
            this.longitude = longitude.toFixed(7);
            this.searchMessage = '';
            this.placeMarker(latitude, longitude, true);
        },

        placeMarker(latitude, longitude, shouldPan) {
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }

            const position = [Number(latitude), Number(longitude)];

            this.marker = L.marker(position, { draggable: true }).addTo(this.map);
            this.marker.on('dragend', (event) => {
                const draggedPosition = event.target.getLatLng();

                this.latitude = draggedPosition.lat.toFixed(7);
                this.longitude = draggedPosition.lng.toFixed(7);
            });

            if (shouldPan) {
                this.map.setView(position, Math.max(this.map.getZoom(), config.detailZoom));
            }
        },

        clearCoordinates() {
            this.latitude = null;
            this.longitude = null;
            this.searchMessage = '';

            if (this.marker) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }

            this.map.setView(config.center, config.zoom);
        },

        async search() {
            const query = this.query.trim();

            if (query.length < 3) {
                this.searchMessage = config.strings.enterMoreCharacters;
                return;
            }

            this.searching = true;
            this.searchMessage = '';
            this.results = [];

            try {
                const response = await fetch(`${config.searchUrl}?q=${encodeURIComponent(query)}`, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    this.searchMessage = config.strings.searchUnavailable;
                    return;
                }

                const payload = await response.json();
                this.results = Array.isArray(payload.results) ? payload.results : [];

                if (this.results.length === 0) {
                    this.searchMessage = config.strings.noResults;
                }
            } catch {
                this.searchMessage = config.strings.searchUnavailable;
            } finally {
                this.searching = false;
            }
        },

        chooseResult(result) {
            this.query = result.label;
            this.results = [];
            this.selectCoordinates(Number(result.latitude), Number(result.longitude));
        },

        destroy() {
            this.map?.remove();
        },
    }));

    Alpine.data('incidentLocationMap', (config) => ({
        map: null,

        init() {
            const cases = Array.isArray(config.cases) ? config.cases : [];
            const validCases = cases.filter((item) => Number.isFinite(Number(item.latitude))
                && Number.isFinite(Number(item.longitude)));

            const center = validCases.length === 1
                ? [Number(validCases[0].latitude), Number(validCases[0].longitude)]
                : config.center;

            this.map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView(center, config.zoom);
            tileLayer(config).addTo(this.map);

            validCases.forEach((item) => {
                const marker = L.marker([Number(item.latitude), Number(item.longitude)])
                    .addTo(this.map)
                    .bindPopup(this.popupContent(item));

                marker.on('popupopen', () => this.map.panTo(marker.getLatLng()));
            });

            if (validCases.length > 1) {
                this.map.fitBounds(
                    L.latLngBounds(validCases.map((item) => [Number(item.latitude), Number(item.longitude)])).pad(0.2),
                    { maxZoom: config.detailZoom },
                );
            }

            this.$nextTick(() => this.map.invalidateSize());
        },

        popupContent(item) {
            const container = document.createElement('div');
            const link = document.createElement('a');

            link.href = item.url;
            link.className = 'font-semibold text-brand hover:underline';
            link.textContent = item.title;
            container.append(link);

            if (item.location) {
                const location = document.createElement('p');
                location.className = 'mt-1 text-xs text-muted';
                location.textContent = item.location;
                container.append(location);
            }

            return container;
        },

        destroy() {
            this.map?.remove();
        },
    }));
}
