import { api, getUser } from '../api.js';
import { navigate } from '../router.js';
import { money, statusLabel } from '../ui.js';

export async function vehicleDetailPage(app, params) {
  app.innerHTML = `<div class="loader">Memuat detail kendaraan</div>`;
  const vehicle = await api.vehicle(params.id);
  const image = vehicle.images?.[0] || '';

  app.innerHTML = `
    <section class="detail-layout">
      <div class="detail-media">
        <img src="${image}" alt="${vehicle.name || 'Kendaraan'}" />
      </div>
      <div class="detail-panel">
        <p class="eyebrow dark">${vehicle.type || 'Armada'}</p>
        <h1>${vehicle.name || `${vehicle.brand || ''} ${vehicle.model || ''}`}</h1>
        <div class="detail-price">${money(vehicle.price_per_day)} / hari</div>
        <dl class="spec-grid">
          <div><dt>Status</dt><dd>${statusLabel(vehicle.status)}</dd></div>
          <div><dt>Kapasitas</dt><dd>${vehicle.capacity || '-'} kursi</dd></div>
          <div><dt>Tahun</dt><dd>${vehicle.year || '-'}</dd></div>
          <div><dt>Plat</dt><dd>${vehicle.plate_number || '-'}</dd></div>
        </dl>
        <div class="feature-list">
          ${(vehicle.features || []).map((item) => `<span>${item}</span>`).join('') || '<span>Driver profesional</span><span>Unit bersih</span>'}
        </div>
        <div class="detail-actions">
          <a class="btn ghost" href="/vehicles" data-link>Kembali</a>
          <a class="btn solid" href="/vehicles/${vehicle.id}/book" data-link>Booking Kendaraan</a>
        </div>
      </div>
    </section>
  `;

  if (!getUser()) {
    document.querySelector('.detail-actions .solid').addEventListener('click', (event) => {
      event.preventDefault();
      navigate('/login');
    });
  }
}

export async function vehicleBookPage(app, params) {
  app.innerHTML = `<div class="loader">Menyiapkan form booking</div>`;
  const vehicle = await api.vehicle(params.id);

  app.innerHTML = `
    <section class="auth-screen wide">
      <form class="auth-box booking-box" id="bookingForm">
        <p class="eyebrow dark">Booking</p>
        <h1>${vehicle.name || 'Kendaraan'}</h1>
        <div class="form-grid">
          <label>Tanggal Mulai<input name="start_date" type="date" required /></label>
          <label>Tanggal Selesai<input name="end_date" type="date" required /></label>
        </div>
        <label>Alamat Jemput<input name="pickup_address" type="text" required maxlength="255" placeholder="Masukkan alamat penjemputan" /></label>
        <label>Alamat Tujuan<input name="dropoff_address" type="text" maxlength="255" placeholder="Opsional" /></label>
        <label>Catatan<textarea name="notes" maxlength="500" placeholder="Opsional"></textarea></label>
        <button class="btn solid full" type="submit">Buat Booking</button>
        <p class="form-note" id="bookingNote">Setelah booking dibuat, lanjutkan pembayaran Midtrans Snap.</p>
      </form>
    </section>
  `;

  document.getElementById('bookingForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const note = document.getElementById('bookingNote');
    note.textContent = 'Membuat booking...';

    try {
      const booking = await api.createBooking({
        vehicle_id: vehicle.id,
        start_date: form.get('start_date'),
        end_date: form.get('end_date'),
        pickup_address: form.get('pickup_address'),
        dropoff_address: form.get('dropoff_address'),
        notes: form.get('notes'),
      });
      navigate(`/bookings/${booking.id}/pay`);
    } catch (error) {
      note.textContent = error.message;
    }
  });
}
