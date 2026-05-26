import { api } from '../api.js';
import { money, statusLabel } from '../ui.js';

export async function vehiclesPage(app) {
  app.innerHTML = `<div class="loader">Memuat armada</div>`;
  const payload = await api.vehicles();
  const vehicles = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Armada</p>
      <h1>Daftar kendaraan dari API Laravel.</h1>
      <p>Halaman ini sudah frontend-only. Pindah halaman berikutnya tidak reload Blade.</p>
    </section>

    <section class="section compact">
      <div class="vehicle-grid">
        ${vehicles.map((vehicle) => `
          <a class="vehicle-card" href="/vehicles/${vehicle.id}" data-link>
            <img src="${vehicle.images?.[0] || ''}" alt="${vehicle.name || 'Kendaraan'}" loading="lazy" decoding="async" />
            <div class="vehicle-meta">
              <span>${statusLabel(vehicle.status)}</span>
              <span>${vehicle.capacity || '-'} Kursi</span>
            </div>
            <h3>${vehicle.name || `${vehicle.brand || ''} ${vehicle.model || ''}`}</h3>
            <p>${money(vehicle.price_per_day)} / hari</p>
          </a>
        `).join('')}
      </div>
    </section>
  `;
}
