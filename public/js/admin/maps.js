document.addEventListener('DOMContentLoaded', function () {
    const vehicles = window.MapData.vehicles;

    const statusCfg = {
        ongoing:     { color:'#16a34a', bg:'#f0fdf4', text:'#14532d', label:'Berjalan'    },
        available:   { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Tersedia'    },
        maintenance: { color:'#d97706', bg:'#fffbeb', text:'#78350f', label:'Maintenance' },
        rented:      { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Disewa'      },
    };

    const map = L.map('fleet-map', {
        center: [-7.2, 110.0], zoom: 7,
        minZoom: 5, maxZoom: 18,
        scrollWheelZoom: true,
        maxBounds: [[-11, 94], [6, 142]],
        maxBoundsViscosity: 0.9,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    function makeIcon(status, isStale) {
        const cfg   = statusCfg[status] || statusCfg.available;
        const color = isStale ? '#d97706' : cfg.color;

        const pulse = (status === 'ongoing' && !isStale) ? `
            <circle cx="22" cy="19" r="17" fill="none" stroke="${color}" stroke-width="1.5" opacity="0.35">
                <animate attributeName="r" values="17;27;17" dur="2.2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.35;0;0.35" dur="2.2s" repeatCount="indefinite"/>
            </circle>` : '';

        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
            ${pulse}
            <path d="M22 5 C13.716 5 7 11.716 7 20 C7 31 22 51 22 51 C22 51 37 31 37 20 C37 11.716 30.284 5 22 5 Z"
                  fill="${color}" stroke="white" stroke-width="1.5"/>
            <circle cx="22" cy="19" r="6.5" fill="white" fill-opacity="0.95"/>
        </svg>`;

        return L.divIcon({
            html: svg, className: '',
            iconSize: [44, 56], iconAnchor: [22, 51], popupAnchor: [0, -54],
        });
    }

    const markerMap = {};

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const cfg      = statusCfg[v.status] || statusCfg.available;
        const color    = v.is_stale ? '#d97706' : cfg.color;
        const bg       = v.is_stale ? '#fffbeb' : cfg.bg;
        const txtColor = v.is_stale ? '#78350f' : cfg.text;
        const lbl      = v.is_stale ? 'Lokasi Lama' : cfg.label;

        const staleRow  = v.is_stale && v.location_updated_human
            ? `<div class="mp-popup-stale">&#9888; Terakhir: ${v.location_updated_human}</div>` : '';
        const updateRow = v.location_updated_at && !v.is_stale
            ? `<div class="mp-popup-time">Update: ${v.location_updated_at}</div>` : '';

        const marker = L.marker([v.lat, v.lon], { icon: makeIcon(v.status, v.is_stale) })
            .bindPopup(`<div class="mp-popup">
                <div class="mp-popup-plate">${v.plate}</div>
                <div class="mp-popup-vehicle">${v.label}</div>
                <div class="mp-popup-driver">Driver: ${v.driver}</div>
                <span class="mp-popup-status" style="background:${bg};color:${txtColor}">
                    <span style="width:6px;height:6px;border-radius:50%;background:${color};display:inline-block"></span>
                    ${lbl}
                </span>
                ${updateRow}${staleRow}
            </div>`, { maxWidth: 220 })
            .addTo(map);

        markerMap[v.id] = marker;
    });

    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div', 'mp-legend');
        d.innerHTML = `
            <div class="mp-legend-title">Legenda</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#16a34a"></span>Berjalan</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#2563eb"></span>Tersedia / Disewa</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#d97706"></span>Maintenance / Lokasi Lama</div>`;
        return d;
    };
    legend.addTo(map);
    setTimeout(() => map.invalidateSize(), 300);

    window.focusVehicle = function (id) {
        document.querySelectorAll('.mp-item').forEach(el => el.classList.remove('active'));
        const item = document.querySelector(`.mp-item[data-id="${id}"]`);
        if (item) { item.classList.add('active'); item.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
        const marker = markerMap[id];
        if (marker) {
            map.flyTo(marker.getLatLng(), 15, { animate: true, duration: 0.8 });
            setTimeout(() => marker.openPopup(), 850);
        }
    };

    document.getElementById('vehicleSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.mp-item').forEach(el => {
            const plate = el.querySelector('.mp-item-plate')?.textContent.toLowerCase() || '';
            const label = el.querySelector('.mp-item-label')?.textContent.toLowerCase() || '';
            el.style.display = (plate.includes(q) || label.includes(q)) ? '' : 'none';
        });
    });
});
