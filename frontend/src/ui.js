import { clearSession, getUser } from './api.js';
import { navigate } from './router.js';

export function shell(content) {
  const user = getUser();
  const adminLinks = user?.role === 'admin' ? `
    <a href="/admin/bookings" data-link>Admin Booking</a>
    <a href="/admin/users" data-link>User</a>
    <a href="/admin/drivers" data-link>Driver</a>
    <a href="/admin/reports" data-link>Report</a>
    <a href="/admin/assets" data-link>Assets</a>
  ` : '';
  return `
    <header class="topbar">
      <a class="brand" href="/" data-link>
        <span class="brand-mark">BR</span>
        <span>Bening Rental</span>
      </a>
      <nav class="nav">
        <a href="/" data-link>Home</a>
        <a href="/vehicles" data-link>Armada</a>
        <a href="/bookings" data-link>Booking</a>
        <a href="/payments" data-link>Payment</a>
        <a href="/chats" data-link>Chat</a>
        <a href="/tickets" data-link>Tiket</a>
        <a href="/notifications" data-link>Notif</a>
        <a href="/profile" data-link>Profil</a>
        <a href="/dashboard" data-link>Dashboard</a>
        ${adminLinks}
      </nav>
      <div class="top-actions">
        ${user ? `<button class="btn ghost" data-logout>Logout</button>` : `<a class="btn ghost" href="/login" data-link>Login</a>`}
        <a class="btn solid" href="/vehicles" data-link>Booking</a>
      </div>
    </header>
    <main>${content}</main>
  `;
}

export function wireShell() {
  const logout = document.querySelector('[data-logout]');
  if (!logout) return;
  logout.addEventListener('click', () => {
    clearSession();
    navigate('/');
  });
}

export function money(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0);
}

export function statusLabel(status) {
  const labels = {
    available: 'Tersedia',
    rented: 'Disewa',
    maintenance: 'Servis',
    pending: 'Pending',
    confirmed: 'Dikonfirmasi',
    ongoing: 'Berjalan',
    completed: 'Selesai',
  };

  return labels[status] || status || '-';
}
