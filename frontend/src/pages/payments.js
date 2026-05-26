import { api } from '../api.js';
import { money, statusLabel } from '../ui.js';

export async function paymentsPage(app) {
  app.innerHTML = `<div class="loader">Memuat pembayaran</div>`;
  const payload = await api.payments();
  const payments = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Payment</p>
      <h1>Riwayat pembayaran.</h1>
    </section>
    <section class="section compact">
      <div class="table-shell">
        <div class="table-row five"><span>Kode</span><span>Status</span><span>Metode</span><span>Total</span><span>Aksi</span></div>
        ${payments.map((payment) => `
          <div class="table-row five">
            <span>${payment.booking_code || '-'}</span>
            <span>${statusLabel(payment.status)}</span>
            <span>${payment.method || '-'}</span>
            <span>${money(payment.amount)}</span>
            <span>${payment.booking_id ? `<a class="mini-link" href="/bookings/${payment.booking_id}/pay" data-link>Detail</a>` : '-'}</span>
          </div>
        `).join('') || `<div class="empty">Belum ada pembayaran.</div>`}
      </div>
    </section>
  `;
}
