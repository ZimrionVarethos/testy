Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = 'rgba(17,24,39,0.35)';

const dbBookingData = window.DashboardData.bookingTrend;
const dbRevenueData = window.DashboardData.revenueData;

(function(){
    const ctx  = document.getElementById('dbBookingChart').getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,180);
    grad.addColorStop(0,'rgba(17,24,39,0.08)');
    grad.addColorStop(1,'rgba(17,24,39,0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dbBookingData.map(d=>d.date),
            datasets: [{ data: dbBookingData.map(d=>d.total), borderColor:'rgb(17,24,39)', backgroundColor:grad, borderWidth:2, fill:true, tension:0.4, pointBackgroundColor:'rgb(17,24,39)', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:3.5, pointHoverRadius:5 }]
        },
        options: { responsive:true, maintainAspectRatio:true, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgb(17,24,39)', titleFont:{size:11}, bodyFont:{size:11}, padding:10, cornerRadius:8, callbacks:{label:c=>`  ${c.raw} pesanan`} } }, scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10}}}, x:{grid:{display:false},ticks:{font:{size:10}}} } }
    });
})();

(function(){
    const ctx = document.getElementById('dbRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: { labels:dbRevenueData.map(d=>d.month), datasets:[{ data:dbRevenueData.map(d=>d.revenue), backgroundColor:'rgba(17,24,39,0.1)', hoverBackgroundColor:'rgba(17,24,39,0.7)', borderRadius:5, borderSkipped:false }] },
        options: { responsive:true, maintainAspectRatio:true, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgb(17,24,39)', bodyFont:{family:"'DM Mono', monospace",size:11}, padding:10, cornerRadius:8, callbacks:{label:c=>`  Rp ${c.raw.toLocaleString('id-ID')}`} } }, scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},callback:v=>'Rp'+(v/1000000).toFixed(0)+'jt'}}, x:{grid:{display:false},ticks:{font:{size:10}}} } }
    });
})();

(function(){
    const vs  = window.DashboardData.vehicleStats;
    const ctx = document.getElementById('dbFleetDonut').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: { labels:['Tersedia','Disewa','Maintenance'], datasets:[{ data:[vs.available,vs.rented,vs.maintenance], backgroundColor:['#16a34a','#2563eb','#d97706'], borderWidth:0, hoverOffset:4 }] },
        options: { responsive:false, cutout:'72%', plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:12},padding:10,cornerRadius:8} } }
    });
})();

document.addEventListener('DOMContentLoaded', function() {
    const vehicles = window.DashboardData.vehicleLocations;

    const statusCfg = {
        ongoing:     { color:'#16a34a', bg:'#f0fdf4', text:'#14532d', label:'Berjalan',    badgeClass:'badge-ongoing'   },
        available:   { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Tersedia',    badgeClass:'badge-confirmed'  },
        rented:      { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Disewa',      badgeClass:'badge-confirmed'  },
        maintenance: { color:'#d97706', bg:'#fffbeb', text:'#78350f', label:'Maintenance', badgeClass:'badge-pending'    },
    };

    const map = L.map('db-map', {
        center:[-2.5,118], zoom:5, minZoom:4, maxZoom:14,
        zoomControl:true, scrollWheelZoom:false,
        maxBounds:[[-15,90],[10,145]], maxBoundsViscosity:0.9,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>', maxZoom:19 }).addTo(map);

    function makeIcon(status) {
        const c = statusCfg[status] || statusCfg.available;
        const pulse = status === 'ongoing' ? `
            <circle cx="22" cy="19" r="17" fill="none" stroke="${c.color}" stroke-width="1.5" opacity="0.35">
                <animate attributeName="r" values="17;27;17" dur="2.2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.35;0;0.35" dur="2.2s" repeatCount="indefinite"/>
            </circle>` : '';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
            ${pulse}
            <path d="M22 5 C13.716 5 7 11.716 7 20 C7 31 22 51 22 51 C22 51 37 31 37 20 C37 11.716 30.284 5 22 5 Z"
                  fill="${c.color}" stroke="white" stroke-width="1.5"/>
            <circle cx="22" cy="19" r="6.5" fill="white" fill-opacity="0.95"/>
        </svg>`;
        return L.divIcon({ html:svg, className:'', iconSize:[44,56], iconAnchor:[22,51], popupAnchor:[0,-54] });
    }

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const c = statusCfg[v.status] || statusCfg.available;
        const updatedNote = v.location_updated_at
            ? `<div class="db-map-popup-time">Update: ${v.location_updated_at}</div>` : '';
        L.marker([v.lat, v.lon], { icon: makeIcon(v.status) })
            .bindPopup(`<div class="db-map-popup">
                <div class="db-map-popup-plate">${v.plate}</div>
                <div class="db-map-popup-driver">Driver: ${v.driver || '-'}</div>
                <span class="db-badge ${c.badgeClass}">${c.label}</span>
                ${updatedNote}
            </div>`, { maxWidth:200, className:'' })
            .addTo(map);
    });

    const legend = L.control({ position:'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div','db-map-legend');
        d.innerHTML = `<div class="db-map-legend-title">Legenda</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#16a34a"></span>Berjalan</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#2563eb"></span>Tersedia / Disewa</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#d97706"></span>Maintenance</div>`;
        return d;
    };
    legend.addTo(map);
    setTimeout(() => map.invalidateSize(), 400);
});
