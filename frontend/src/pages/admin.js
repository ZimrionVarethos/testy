import { api } from '../api.js';
import { money, statusLabel } from '../ui.js';

function table(rows, columns) {
  return `
    <div class="table-shell">
      <div class="table-row ${columns.length === 5 ? 'five' : ''}">${columns.map((col) => `<span>${col.label}</span>`).join('')}</div>
      ${rows.map((row) => `
        <div class="table-row ${columns.length === 5 ? 'five' : ''}">
          ${columns.map((col) => `<span>${col.render(row)}</span>`).join('')}
        </div>
      `).join('') || `<div class="empty">Data kosong.</div>`}
    </div>
  `;
}

function csvEscape(value) {
  const text = String(value ?? '');
  return `"${text.replaceAll('"', '""')}"`;
}

function downloadCsv(filename, rows) {
  const csv = rows.map((row) => row.map(csvEscape).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export async function adminUsersPage(app) {
  app.innerHTML = `<div class="loader">Memuat pengguna</div>`;
  const payload = await api.users();
  const users = Array.isArray(payload) ? payload : payload.data || [];
  app.innerHTML = `
    <section class="page-head"><p class="eyebrow dark">Admin</p><h1>Pengguna.</h1></section>
    <section class="section compact">${table(users, [
      { label: 'Nama', render: (u) => u.name || '-' },
      { label: 'Email', render: (u) => u.email || '-' },
      { label: 'Phone', render: (u) => u.phone || '-' },
      { label: 'Status', render: (u) => u.is_active ? 'Aktif' : 'Nonaktif' },
      { label: 'Aksi', render: (u) => `<button class="mini-link as-button" data-toggle-user="${u.id}">Toggle</button>` },
    ])}</section>
  `;
  document.querySelectorAll('[data-toggle-user]').forEach((button) => {
    button.addEventListener('click', async () => {
      await api.toggleUser(button.dataset.toggleUser);
      await adminUsersPage(app);
    });
  });
}

export async function adminDriversPage(app) {
  app.innerHTML = `<div class="loader">Memuat driver</div>`;
  const payload = await api.drivers();
  const drivers = Array.isArray(payload) ? payload : payload.data || [];
  app.innerHTML = `
    <section class="page-head"><p class="eyebrow dark">Admin</p><h1>Driver.</h1></section>
    <section class="section compact">${table(drivers, [
      { label: 'Nama', render: (d) => d.name || '-' },
      { label: 'Email', render: (d) => d.email || '-' },
      { label: 'Phone', render: (d) => d.phone || '-' },
      { label: 'Status', render: (d) => d.is_active ? 'Aktif' : 'Nonaktif' },
      { label: 'Aksi', render: (d) => `<button class="mini-link as-button" data-toggle-driver="${d.id}">Toggle</button>` },
    ])}</section>
  `;
  document.querySelectorAll('[data-toggle-driver]').forEach((button) => {
    button.addEventListener('click', async () => {
      await api.toggleDriver(button.dataset.toggleDriver);
      await adminDriversPage(app);
    });
  });
}

export async function adminVehiclesPage(app) {
  app.innerHTML = `<div class="loader">Memuat kendaraan admin</div>`;
  const payload = await api.vehicles();
  const vehicles = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin</p>
      <h1>Kendaraan.</h1>
      <a class="btn solid" href="/admin/vehicles/new" data-link>Tambah Kendaraan</a>
    </section>
    <section class="section compact">${table(vehicles, [
      { label: 'Nama', render: (v) => v.name || '-' },
      { label: 'Plat', render: (v) => v.plate_number || '-' },
      { label: 'Status', render: (v) => statusLabel(v.status) },
      { label: 'Harga', render: (v) => money(v.price_per_day) },
      { label: 'Aksi', render: (v) => `<a class="mini-link" href="/admin/vehicles/${v.id}/edit" data-link>Edit</a>` },
    ])}</section>
  `;
}

function vehicleForm(vehicle = {}, assets = []) {
  const features = (vehicle.features || []).join(', ');
  return `
    <form class="auth-box booking-box" id="vehicleForm">
      <p class="eyebrow dark">Vehicle</p>
      <h1>${vehicle.id ? 'Edit kendaraan.' : 'Tambah kendaraan.'}</h1>
      <div class="form-grid">
        <label>Nama<input name="name" value="${vehicle.name || ''}" required maxlength="100" /></label>
        <label>Brand<input name="brand" value="${vehicle.brand || ''}" required maxlength="50" /></label>
        <label>Model<input name="model" value="${vehicle.model || ''}" required maxlength="50" /></label>
        <label>Tahun<input name="year" type="number" value="${vehicle.year || new Date().getFullYear()}" required min="2000" /></label>
        <label>Plat<input name="plate_number" value="${vehicle.plate_number || ''}" required maxlength="20" /></label>
        <label>Tipe
          <select name="type" required>
            ${['MPV', 'SUV', 'Van', 'Sedan', 'Minibus'].map((type) => `<option value="${type}" ${vehicle.type === type ? 'selected' : ''}>${type}</option>`).join('')}
          </select>
        </label>
        <label>Kapasitas<input name="capacity" type="number" value="${vehicle.capacity || 4}" required min="2" max="20" /></label>
        <label>Harga/Hari<input name="price_per_day" type="number" value="${vehicle.price_per_day || 100000}" required min="100000" /></label>
      </div>
      ${vehicle.id ? `
        <label>Status
          <select name="status">
            ${['available', 'rented', 'maintenance'].map((status) => `<option value="${status}" ${vehicle.status === status ? 'selected' : ''}>${statusLabel(status)}</option>`).join('')}
          </select>
        </label>
      ` : ''}
      <label>Fitur<input name="features_raw" value="${features}" placeholder="AC, Musik, Bagasi luas" /></label>
      <label>Asset Cloudinary
        <select name="asset_id">
          <option value="">${vehicle.images?.[0] ? 'Pertahankan gambar saat ini' : 'Tanpa gambar'}</option>
          ${assets.map((asset) => `<option value="${asset.id}">${asset.original_name || asset.public_id}</option>`).join('')}
        </select>
      </label>
      <input type="hidden" name="kept_image" value="${vehicle.images?.[0] || ''}" />
      <div class="form-grid">
        <label>Focal X<input name="focal_x" type="number" value="50" min="0" max="100" /></label>
        <label>Focal Y<input name="focal_y" type="number" value="50" min="0" max="100" /></label>
      </div>
      <button class="btn solid full" type="submit">Simpan</button>
      ${vehicle.id ? `<button class="btn ghost full" type="button" id="deleteVehicleBtn">Hapus Kendaraan</button>` : ''}
      <p class="form-note" id="vehicleNote">Upload gambar baru lewat halaman Assets, lalu pilih di form ini.</p>
    </form>
  `;
}

export async function adminVehicleFormPage(app, params = {}) {
  app.innerHTML = `<div class="loader">Menyiapkan form kendaraan</div>`;
  const [vehicle, assetsPayload] = await Promise.all([
    params.id ? api.vehicle(params.id) : Promise.resolve({}),
    api.adminAssets(),
  ]);
  const assets = Array.isArray(assetsPayload) ? assetsPayload : assetsPayload.data || [];

  app.innerHTML = `<section class="auth-screen wide">${vehicleForm(vehicle, assets)}</section>`;

  document.getElementById('vehicleForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const body = {
      name: form.get('name'),
      brand: form.get('brand'),
      model: form.get('model'),
      year: Number(form.get('year')),
      plate_number: form.get('plate_number'),
      type: form.get('type'),
      capacity: Number(form.get('capacity')),
      price_per_day: Number(form.get('price_per_day')),
      status: form.get('status') || 'available',
      features_raw: form.get('features_raw'),
      asset_id: form.get('asset_id'),
      kept_image: form.get('kept_image'),
      focal_x: Number(form.get('focal_x') || 50),
      focal_y: Number(form.get('focal_y') || 50),
    };

    try {
      if (params.id) await api.adminUpdateVehicle(params.id, body);
      else await api.adminCreateVehicle(body);
      window.history.pushState({}, '', '/admin/vehicles');
      await adminVehiclesPage(app);
    } catch (error) {
      document.getElementById('vehicleNote').textContent = error.message;
    }
  });

  document.getElementById('deleteVehicleBtn')?.addEventListener('click', async () => {
    try {
      await api.adminDeleteVehicle(params.id);
      window.history.pushState({}, '', '/admin/vehicles');
      await adminVehiclesPage(app);
    } catch (error) {
      document.getElementById('vehicleNote').textContent = error.message;
    }
  });
}

export async function adminBookingsPage(app) {
  app.innerHTML = `<div class="loader">Memuat booking admin</div>`;
  const payload = await api.bookings();
  const bookings = Array.isArray(payload) ? payload : payload.data || [];
  app.innerHTML = `
    <section class="page-head"><p class="eyebrow dark">Admin</p><h1>Semua booking.</h1></section>
    <section class="section compact">${table(bookings, [
      { label: 'Kode', render: (b) => b.booking_code || '-' },
      { label: 'Status', render: (b) => b.status_label || statusLabel(b.status) },
      { label: 'User', render: (b) => b.user?.name || '-' },
      { label: 'Kendaraan', render: (b) => b.vehicle?.name || '-' },
      { label: 'Aksi', render: (b) => `<a class="mini-link" href="/admin/bookings/${b.id}" data-link>Detail</a>` },
    ])}</section>
  `;
}

export async function adminBookingDetailPage(app, params) {
  app.innerHTML = `<div class="loader">Memuat detail booking</div>`;
  const booking = await api.booking(params.id);
  let drivers = [];

  if (booking.status === 'pending') {
    try {
      drivers = await api.availableDrivers(params.id);
    } catch {
      drivers = [];
    }
  }

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin Booking</p>
      <h1>${booking.booking_code || 'Booking'}</h1>
      <p>${booking.user?.name || '-'} · ${booking.vehicle?.name || '-'} · ${money(booking.total_price)}</p>
    </section>
    <section class="section compact">
      <div class="action-grid">
        <form class="auth-box" id="assignForm">
          <p class="eyebrow dark">Assign Driver</p>
          <h2>Driver tersedia.</h2>
          <label>Driver
            <select name="driver_id" ${drivers.length ? '' : 'disabled'}>
              ${drivers.map((driver) => `<option value="${driver.id}">${driver.name} - ${driver.phone || '-'}</option>`).join('')}
            </select>
          </label>
          <button class="btn solid full" type="submit" ${drivers.length ? '' : 'disabled'}>Assign Driver</button>
          <p class="form-note">${drivers.length ? 'Assign akan mengubah booking menjadi confirmed.' : 'Tidak ada driver tersedia atau booking tidak pending.'}</p>
        </form>
        <form class="auth-box" id="cancelForm">
          <p class="eyebrow dark">Cancel</p>
          <h2>Batalkan booking.</h2>
          <label>Alasan<textarea name="reason" required>Dibatalkan oleh admin.</textarea></label>
          <button class="btn ghost full" type="submit">Batalkan Booking</button>
          <p class="form-note" id="bookingActionNote"></p>
        </form>
      </div>
    </section>
  `;

  document.getElementById('assignForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const driverId = new FormData(event.currentTarget).get('driver_id');
    if (!driverId) return;
    await api.assignDriver(params.id, driverId);
    await adminBookingDetailPage(app, params);
  });

  document.getElementById('cancelForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const reason = new FormData(event.currentTarget).get('reason');
    try {
      await api.cancelBooking(params.id, reason);
      await adminBookingDetailPage(app, params);
    } catch (error) {
      document.getElementById('bookingActionNote').textContent = error.message;
    }
  });
}

export async function adminReportsPage(app) {
  app.innerHTML = `<div class="loader">Memuat laporan</div>`;
  const data = await api.reports();
  const stats = data.stats || {};
  const monthly = data.monthly_data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin</p>
      <h1>Laporan.</h1>
      <p>Ringkasan laporan dari endpoint API dashboard reports.</p>
      <button class="btn solid" id="exportReportBtn">Export CSV</button>
    </section>
    <section class="stats-band light">
      <div><strong>${stats.total_bookings || 0}</strong><span>Total Booking</span></div>
      <div><strong>${stats.completed_bookings || 0}</strong><span>Selesai</span></div>
      <div><strong>${money(stats.total_revenue || 0)}</strong><span>Total Revenue</span></div>
    </section>
    <section class="section compact">${table(monthly, [
      { label: 'Periode', render: (row) => row.label || '-' },
      { label: 'Booking', render: (row) => row.count || 0 },
      { label: 'Revenue', render: (row) => money(row.revenue) },
    ])}</section>
  `;

  document.getElementById('exportReportBtn').addEventListener('click', () => {
    downloadCsv('bening-rental-report.csv', [
      ['Periode', 'Booking Selesai', 'Revenue'],
      ...monthly.map((row) => [row.label || '-', row.count || 0, row.revenue || 0]),
      [],
      ['Total Booking', stats.total_bookings || 0, ''],
      ['Completed Booking', stats.completed_bookings || 0, ''],
      ['Total Revenue', stats.total_revenue || 0, ''],
    ]);
  });
}

export async function adminPlaceholderPage(app, title, description) {
  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin</p>
      <h1>${title}</h1>
      <p>${description}</p>
    </section>
    <section class="section compact">
      <div class="list-shell">
        <div class="empty">Halaman ini sudah punya route SPA, tapi perlu endpoint API khusus sebelum fitur penuh dipindahkan dari Blade.</div>
      </div>
    </section>
  `;
}

export async function adminMapsPage(app) {
  app.innerHTML = `<div class="loader">Memuat peta kendaraan</div>`;
  const data = await api.adminMaps();
  const vehicles = data.vehicles || [];
  const tracked = vehicles.filter((vehicle) => Number(vehicle.lat) && Number(vehicle.lon));
  const lats = tracked.map((vehicle) => Number(vehicle.lat));
  const lons = tracked.map((vehicle) => Number(vehicle.lon));
  const minLat = Math.min(...lats, -6.3);
  const maxLat = Math.max(...lats, -6.1);
  const minLon = Math.min(...lons, 106.7);
  const maxLon = Math.max(...lons, 106.95);
  const latRange = Math.max(maxLat - minLat, 0.01);
  const lonRange = Math.max(maxLon - minLon, 0.01);

  const markers = tracked.map((vehicle) => {
    const x = ((Number(vehicle.lon) - minLon) / lonRange) * 86 + 7;
    const y = (1 - ((Number(vehicle.lat) - minLat) / latRange)) * 78 + 11;
    return `
      <button class="map-marker ${vehicle.is_stale ? 'stale' : ''}" style="left:${x}%;top:${y}%;" data-map-id="${vehicle.id}" title="${vehicle.plate}">
        <span>${vehicle.plate || 'CAR'}</span>
      </button>
    `;
  }).join('');

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin</p>
      <h1>Peta kendaraan.</h1>
      <p>Total ${data.stats?.total || 0}, berjalan ${data.stats?.ongoing || 0}, tersedia ${data.stats?.available || 0}.</p>
    </section>
    <section class="map-layout">
      <div class="map-board">
        <div class="map-grid-bg"></div>
        ${markers || `<div class="map-empty">Belum ada kendaraan dengan koordinat aktif.</div>`}
      </div>
      <aside class="map-side" id="mapSide">
        <p class="eyebrow dark">Live Coordinate</p>
        <h2>${tracked.length} unit terlacak.</h2>
        <div class="map-list">
          ${vehicles.map((vehicle) => `
            <button class="map-row" data-map-id="${vehicle.id}">
              <span>${vehicle.plate || '-'}</span>
              <strong>${vehicle.label || '-'}</strong>
              <small>${vehicle.driver || '-'} / ${vehicle.location_updated_human || 'no gps'}</small>
            </button>
          `).join('')}
        </div>
      </aside>
    </section>
  `;

  const showVehicle = async (id) => {
    const detail = await api.adminMap(id);
    const vehicle = detail.vehicle || {};
    const booking = detail.active_booking;
    document.getElementById('mapSide').innerHTML = `
      <p class="eyebrow dark">Vehicle Detail</p>
      <h2>${vehicle.plate || '-'}</h2>
      <dl class="spec-grid">
        <div><dt>Unit</dt><dd>${vehicle.name || vehicle.model || '-'}</dd></div>
        <div><dt>Status</dt><dd>${statusLabel(vehicle.status)}</dd></div>
        <div><dt>Driver</dt><dd>${vehicle.driver?.name || '-'}</dd></div>
        <div><dt>GPS</dt><dd>${vehicle.last_lat && vehicle.last_lon ? `${vehicle.last_lat}, ${vehicle.last_lon}` : '-'}</dd></div>
      </dl>
      <div class="list-shell">
        <div class="empty">${booking ? `Booking ${booking.booking_code} / ${booking.status}` : 'Tidak ada booking aktif.'}</div>
      </div>
      <button class="btn ghost full" id="mapBackBtn">Kembali</button>
    `;
    document.getElementById('mapBackBtn').addEventListener('click', () => adminMapsPage(app));
  };

  document.querySelectorAll('[data-map-id]').forEach((button) => {
    button.addEventListener('click', () => showVehicle(button.dataset.mapId));
  });
}

export async function adminAssetsPage(app) {
  app.innerHTML = `<div class="loader">Memuat Cloudinary assets</div>`;
  const [assetsPayload, usagePayload] = await Promise.all([
    api.adminAssets(),
    api.adminAssetUsage().catch(() => null),
  ]);
  const assets = Array.isArray(assetsPayload) ? assetsPayload : assetsPayload.data || [];
  const usage = usagePayload || {};

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Admin</p>
      <h1>Cloudinary assets.</h1>
      <p>Storage ${usage.storage_pct ?? 0}% · Bandwidth ${usage.bandwidth_pct ?? 0}% · Objects ${usage.objects ?? '-'}</p>
    </section>
    <section class="section compact">
      <form class="upload-strip" id="assetUploadForm">
        <input type="file" name="files" accept="image/jpeg,image/png,image/jpg,image/webp" multiple required />
        <input name="subfolder" placeholder="subfolder, contoh vehicles" value="admin" />
        <button class="btn solid" type="submit">Upload</button>
      </form>
      <div class="asset-grid">
        ${assets.map((asset) => `
          <article class="asset-card">
            <img src="${asset.thumb_url || asset.url}" alt="${asset.original_name || 'Asset'}" loading="lazy" decoding="async" />
            <div>
              <strong>${asset.original_name || asset.public_id}</strong>
              <small>${asset.subfolder || '-'} · ${asset.human_size || ''}</small>
            </div>
            <button class="mini-link as-button" data-delete-asset="${asset.id}" ${asset.is_in_use ? 'disabled' : ''}>Hapus</button>
          </article>
        `).join('') || `<div class="empty">Belum ada asset.</div>`}
      </div>
    </section>
  `;

  document.getElementById('assetUploadForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    await api.uploadAdminAssets(form);
    await adminAssetsPage(app);
  });

  document.querySelectorAll('[data-delete-asset]').forEach((button) => {
    button.addEventListener('click', async () => {
      await api.deleteAdminAsset(button.dataset.deleteAsset);
      await adminAssetsPage(app);
    });
  });
}
