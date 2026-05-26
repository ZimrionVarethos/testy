import './styles.css';
import { addRoute, bindRouter, renderRoute } from './router.js';
import { shell, wireShell } from './ui.js';
import { getUser } from './api.js';
import { dashboardPage } from './pages/dashboard.js';
import { homePage } from './pages/home.js';
import { loginPage, registerPage } from './pages/login.js';
import { vehiclesPage } from './pages/vehicles.js';
import { vehicleBookPage, vehicleDetailPage } from './pages/vehicle-detail.js';
import { bookingsPage, payBookingPage } from './pages/bookings.js';
import { notificationsPage } from './pages/notifications.js';
import { paymentsPage } from './pages/payments.js';
import { chatRoomPage, chatsPage } from './pages/chats.js';
import { ticketCreatePage, ticketDetailPage, ticketsPage } from './pages/tickets.js';
import { adminAssetsPage, adminBookingDetailPage, adminBookingsPage, adminDriversPage, adminMapsPage, adminReportsPage, adminUsersPage, adminVehicleFormPage, adminVehiclesPage } from './pages/admin.js';
import { profilePage } from './pages/profile.js';

function withShell(page, options = {}) {
  return async (app, params) => {
    const user = getUser();

    app.innerHTML = shell('<div id="page"></div>');
    const pageHost = document.getElementById('page');

    if (options.auth && !user) {
      pageHost.innerHTML = `
        <section class="page-head">
          <p class="eyebrow dark">Akses</p>
          <h1>Login dulu.</h1>
          <p>Halaman ini butuh akun aktif.</p>
          <a class="btn solid" href="/login" data-link>Login</a>
        </section>
      `;
      wireShell();
      return;
    }

    if (options.roles && !options.roles.includes(user?.role)) {
      pageHost.innerHTML = `
        <section class="page-head">
          <p class="eyebrow dark">Akses</p>
          <h1>Tidak punya akses.</h1>
          <p>Halaman ini hanya tersedia untuk role ${options.roles.join(', ')}.</p>
          <a class="btn solid" href="/dashboard" data-link>Dashboard</a>
        </section>
      `;
      wireShell();
      return;
    }

    await page(pageHost, params);
    wireShell();
  };
}

addRoute('/', withShell(homePage));
addRoute('/vehicles', withShell(vehiclesPage));
addRoute('/vehicles/:id', withShell(vehicleDetailPage));
addRoute('/vehicles/:id/book', withShell(vehicleBookPage, { auth: true, roles: ['pengguna', 'user'] }));
addRoute('/bookings', withShell(bookingsPage, { auth: true }));
addRoute('/bookings/:id/pay', withShell(payBookingPage, { auth: true }));
addRoute('/payments', withShell(paymentsPage, { auth: true }));
addRoute('/chats', withShell(chatsPage, { auth: true }));
addRoute('/chats/:id', withShell(chatRoomPage, { auth: true }));
addRoute('/tickets', withShell(ticketsPage, { auth: true }));
addRoute('/tickets/new', withShell(ticketCreatePage, { auth: true, roles: ['pengguna', 'user'] }));
addRoute('/tickets/:id', withShell(ticketDetailPage, { auth: true }));
addRoute('/notifications', withShell(notificationsPage, { auth: true }));
addRoute('/profile', withShell(profilePage, { auth: true }));
addRoute('/admin/bookings', withShell(adminBookingsPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/bookings/:id', withShell(adminBookingDetailPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/users', withShell(adminUsersPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/drivers', withShell(adminDriversPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/payments', withShell(paymentsPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/vehicles', withShell(adminVehiclesPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/vehicles/new', withShell(adminVehicleFormPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/vehicles/:id/edit', withShell(adminVehicleFormPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/tickets', withShell(ticketsPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/reports', withShell(adminReportsPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/maps', withShell(adminMapsPage, { auth: true, roles: ['admin'] }));
addRoute('/admin/assets', withShell(adminAssetsPage, { auth: true, roles: ['admin'] }));
addRoute('/login', withShell(loginPage));
addRoute('/register', withShell(registerPage));
addRoute('/dashboard', withShell(dashboardPage));

bindRouter();
renderRoute();
