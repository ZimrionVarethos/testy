const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1').replace(/\/$/, '');

const TOKEN_KEY = 'bening_token';
const USER_KEY = 'bening_user';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function getUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
  } catch {
    return null;
  }
}

export function saveSession(data) {
  localStorage.setItem(TOKEN_KEY, data.token);
  localStorage.setItem(USER_KEY, JSON.stringify(data.user));
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

async function request(path, options = {}) {
  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  const token = getToken();
  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
    body: options.body && !(options.body instanceof FormData) ? JSON.stringify(options.body) : options.body,
  });

  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload.success === false) {
    throw new Error(payload.message || 'Request gagal.');
  }

  return payload.data ?? payload;
}

export async function cachedGet(key, path, ttlMs = 60000) {
  const cached = sessionStorage.getItem(key);
  if (cached) {
    const parsed = JSON.parse(cached);
    if (Date.now() - parsed.time < ttlMs) return parsed.data;
  }

  const data = await request(path);
  sessionStorage.setItem(key, JSON.stringify({ time: Date.now(), data }));
  return data;
}

export const api = {
  landing: () => cachedGet('api:landing', '/landing', 120000),
  vehicles: () => cachedGet('api:vehicles', '/vehicles?per_page=12', 60000),
  vehicle: (id) => request(`/vehicles/${id}`),
  register: (body) => request('/auth/register', { method: 'POST', body }),
  login: (email, password) => request('/web/auth/login', { method: 'POST', body: { email, password } }),
  me: () => request('/web/auth/me'),
  logout: () => request('/web/auth/logout', { method: 'POST' }),
  updateProfile: (body) => request('/auth/profile', { method: 'PUT', body }),
  dashboardAdmin: () => request('/dashboard/'),
  dashboardPengguna: () => request('/dashboard/pengguna'),
  dashboardDriver: () => request('/dashboard/driver'),
  bookings: () => request('/bookings?per_page=30'),
  booking: (id) => request(`/bookings/${id}`),
  createBooking: (body) => request('/bookings', { method: 'POST', body }),
  createSnap: (id) => request(`/bookings/${id}/snap`, { method: 'POST' }),
  paymentStatus: (id) => request(`/bookings/${id}/payment-status`),
  payments: () => request('/payments?per_page=30'),
  payment: (id) => request(`/payments/${id}`),
  chats: (filter = 'active') => request(`/chats?filter=${encodeURIComponent(filter)}`),
  messages: (bookingId) => request(`/bookings/${bookingId}/messages`),
  sendMessage: (bookingId, message) => request(`/bookings/${bookingId}/messages`, { method: 'POST', body: { message } }),
  tickets: () => request('/tickets'),
  ticket: (id) => request(`/tickets/${id}`),
  createTicket: (body) => request('/tickets', { method: 'POST', body }),
  replyTicket: (id, message) => request(`/tickets/${id}/reply`, { method: 'POST', body: { message } }),
  notifications: () => request('/notifications'),
  readNotification: (id) => request(`/notifications/${id}/read`, { method: 'POST' }),
  readAllNotifications: () => request('/notifications/read-all', { method: 'POST' }),
  users: () => request('/users?per_page=30'),
  user: (id) => request(`/users/${id}`),
  toggleUser: (id) => request(`/users/${id}/toggle`, { method: 'POST' }),
  drivers: () => request('/drivers?per_page=30'),
  driver: (id) => request(`/drivers/${id}`),
  toggleDriver: (id) => request(`/drivers/${id}/toggle`, { method: 'POST' }),
  adminTickets: () => request('/admin/tickets?per_page=30'),
  adminTicket: (id) => request(`/admin/tickets/${id}`),
  adminReplyTicket: (id, message) => request(`/admin/tickets/${id}/reply`, { method: 'POST', body: { message } }),
  adminUpdateTicketStatus: (id, status) => request(`/admin/tickets/${id}/status`, { method: 'PUT', body: { status } }),
  reports: () => request('/dashboard/reports'),
  adminMaps: () => request('/admin/maps'),
  adminMap: (id) => request(`/admin/maps/${id}`),
  adminAssets: () => request('/admin/assets'),
  adminAssetUsage: () => request('/admin/assets/usage'),
  uploadAdminAssets: (formData) => request('/admin/assets', { method: 'POST', body: formData }),
  deleteAdminAsset: (id) => request(`/admin/assets/${id}`, { method: 'DELETE' }),
  availableDrivers: (bookingId) => request(`/bookings/${bookingId}/available-drivers`),
  assignDriver: (bookingId, driverId) => request(`/bookings/${bookingId}/assign-driver`, { method: 'POST', body: { driver_id: driverId } }),
  cancelBooking: (bookingId, reason) => request(`/bookings/${bookingId}/cancel`, { method: 'POST', body: { reason } }),
  adminCreateVehicle: (body) => request('/admin/vehicles', { method: 'POST', body }),
  adminUpdateVehicle: (id, body) => request(`/admin/vehicles/${id}`, { method: 'PUT', body }),
  adminDeleteVehicle: (id) => request(`/admin/vehicles/${id}`, { method: 'DELETE' }),
};
