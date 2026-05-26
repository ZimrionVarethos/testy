import { api } from '../api.js';
import { money, statusLabel } from '../ui.js';

function loadSnapScript() {
  return new Promise((resolve, reject) => {
    if (window.snap) {
      resolve();
      return;
    }

    const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY || '';
    const src = import.meta.env.VITE_MIDTRANS_SNAP_URL || 'https://app.sandbox.midtrans.com/snap/snap.js';
    const script = document.createElement('script');
    script.src = src;
    script.dataset.clientKey = clientKey;
    script.onload = resolve;
    script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'));
    document.head.appendChild(script);
  });
}

export async function bookingsPage(app) {
  app.innerHTML = `<div class="loader">Memuat booking</div>`;
  const payload = await api.bookings();
  const bookings = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Booking</p>
      <h1>Riwayat dan pembayaran.</h1>
      <p>Semua data dari API. Halaman ini siap dipakai di frontend terpisah.</p>
    </section>
    <section class="section compact">
      <div class="table-shell">
        <div class="table-row head"><span>Kode</span><span>Status</span><span>Kendaraan</span><span>Total</span><span>Aksi</span></div>
        ${bookings.map((booking) => `
          <div class="table-row five">
            <span>${booking.booking_code || '-'}</span>
            <span>${booking.status_label || statusLabel(booking.status)}</span>
            <span>${booking.vehicle?.name || '-'}</span>
            <span>${money(booking.total_price)}</span>
            <span>${booking.status === 'pending' ? `<a class="mini-link" href="/bookings/${booking.id}/pay" data-link>Bayar</a>` : '-'}</span>
          </div>
        `).join('') || `<div class="empty">Belum ada booking.</div>`}
      </div>
    </section>
  `;
}

export async function payBookingPage(app, params) {
  app.innerHTML = `<div class="loader">Memuat pembayaran</div>`;
  const booking = await api.booking(params.id);

  app.innerHTML = `
    <section class="auth-screen">
      <div class="auth-box">
        <p class="eyebrow dark">Payment</p>
        <h1>${booking.booking_code || 'Booking'}</h1>
        <div class="pay-summary">
          <span>${booking.vehicle?.name || '-'}</span>
          <strong>${money(booking.total_price)}</strong>
          <small>${booking.status_label || statusLabel(booking.status)}</small>
        </div>
        <button class="btn solid full" id="payButton" ${booking.status !== 'pending' ? 'disabled' : ''}>Bayar dengan Midtrans</button>
        <a class="btn ghost full" href="/bookings" data-link>Kembali ke Booking</a>
        <p class="form-note" id="payNote">Pastikan domain frontend memakai HTTPS saat pembayaran production.</p>
      </div>
    </section>
  `;

  const button = document.getElementById('payButton');
  button?.addEventListener('click', async () => {
    const note = document.getElementById('payNote');
    note.textContent = 'Menyiapkan Midtrans Snap...';
    try {
      const snap = await api.createSnap(params.id);
      await loadSnapScript();
      window.snap.pay(snap.snap_token, {
        onSuccess: () => { note.textContent = 'Pembayaran berhasil. Status akan diperbarui otomatis.'; },
        onPending: () => { note.textContent = 'Pembayaran masih pending. Cek lagi beberapa saat.'; },
        onError: () => { note.textContent = 'Pembayaran gagal. Coba lagi atau hubungi admin.'; },
        onClose: () => { note.textContent = 'Popup pembayaran ditutup sebelum selesai.'; },
      });
    } catch (error) {
      note.textContent = error.message;
    }
  });
}
