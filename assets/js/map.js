
window.Cadê Meu Pet?Map = (function () {
    function toNumber(value) {
        if (value === null || value === undefined) return null;
        const n = Number(value);
        return Number.isFinite(n) ? n : null;
    }

    function getInputValue(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        return el.value;
    }

    function setInputValue(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = value;
    }

    function init(options) {
        const containerId = options?.containerId;
        const latInputId = options?.latInputId;
        const lngInputId = options?.lngInputId;
        const defaultLat = toNumber(options?.defaultLat) ?? -10.9472;
        const defaultLng = toNumber(options?.defaultLng) ?? -61.9327;
        const defaultZoom = toNumber(options?.defaultZoom) ?? 12;

        if (!containerId || !latInputId || !lngInputId) return null;

        const container = document.getElementById(containerId);
        if (!container) return null;

        if (typeof L === 'undefined') {
            console.error('Leaflet não carregado.');
            return null;
        }

        const existingLat = toNumber(getInputValue(latInputId));
        const existingLng = toNumber(getInputValue(lngInputId));

        const initialLat = existingLat ?? defaultLat;
        const initialLng = existingLng ?? defaultLng;

        const map = L.map(container, {
            center: [initialLat, initialLng],
            zoom: existingLat !== null && existingLng !== null ? 15 : defaultZoom,
            scrollWheelZoom: false,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;

        function updateMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    const pos = marker.getLatLng();
                    setInputValue(latInputId, pos.lat.toFixed(8));
                    setInputValue(lngInputId, pos.lng.toFixed(8));
                });
            }

            setInputValue(latInputId, lat.toFixed(8));
            setInputValue(lngInputId, lng.toFixed(8));
        }

        if (existingLat !== null && existingLng !== null) {
            updateMarker(existingLat, existingLng);
        }

        map.on('click', function (e) {
            updateMarker(e.latlng.lat, e.latlng.lng);
        });

        return {
            map,
            setPoint: function (lat, lng) {
                updateMarker(lat, lng);
                map.setView([lat, lng], 15);
            },
            fitToPoint: function () {
                const lat = toNumber(getInputValue(latInputId));
                const lng = toNumber(getInputValue(lngInputId));
                if (lat === null || lng === null) return;
                updateMarker(lat, lng);
                map.setView([lat, lng], 15);
            }
        };
    }

    return { init };
})();
