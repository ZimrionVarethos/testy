import { api, getUser } from '../api.js';
import { money, statusLabel } from '../ui.js';

export async function dashboardPage(app) {
  const user = getUser();
  if (!user) {
    app.innerHTML = `
      <section class="page-head">
        <p class="eyebrow dark">Dashboard</p>
        <h1>Login dulu untuk membuka dashboard.</h1>
        <p>Frontend ini sudah siap memakai token Sanctum bearer dari API.</p>
        <a class="btn solid" href="/login" data-link>Login</a>
      </section>
    `;
    return;
  }

  app.innerHTML = `<div class="loader">Memuat dashboard ${user.role}</div>`;

  try {
    const data = user.role === 'admin'
      ? await api.dashboardAdmin()
      : user.role === 'driver'
        ? await api.dashboardDriver()
        : await api.dashboardPengguna();
    const bookings = data.active_bookings || [];
    const recentBookings = data.recent_bookings || [];
    const tableRows = bookings.length ? bookings : recentBookings;
    app.innerHTML = `
      <section class="page-head">
        <p class="eyebrow dark">Dashboard ${user.role}</p>
        <h1>Halo, ${user.name}</h1>
        <p>Data ini dari API, bukan render Blade.</p>
      </section>

      <section class="stats-band light">
        ${Object.entries(data.stats || {}).map(([key, value]) => `<div><strong>${value}</strong><span>${key.replaceAll('_', ' ')}</span></div>`).join('')}
      </section>

      <section class="section compact">
        <div class="table-shell">
          <div class="table-row head"><span>Kode</span><span>Status</span><span>Kendaraan</span><span>Total</span></div>
          ${tableRows.map((booking) => `
            <div class="table-row">
              <span>${booking.booking_code || '-'}</span>
              <span>${statusLabel(booking.status)}</span>
              <span>${booking.vehicle_name || booking.user_name || '-'}</span>
              <span>${money(booking.total_price)}</span>
            </div>
          `).join('') || `<div class="empty">Belum ada booking aktif.</div>`}
        </div>
      </section>
    `;
  } catch (error) {
    app.innerHTML = `
      <section class="page-head">
        <p class="eyebrow dark">Dashboard</p>
        <h1>Belum bisa memuat data.</h1>
        <p>${error.message}</p>
      </section>
    `;
  }
}
