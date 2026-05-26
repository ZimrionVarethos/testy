import { api, getUser } from '../api.js';
import { navigate } from '../router.js';

export async function ticketsPage(app) {
  app.innerHTML = `<div class="loader">Memuat tiket</div>`;
  const user = getUser();
  const payload = user?.role === 'admin' ? await api.adminTickets() : await api.tickets();
  const tickets = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Tiket</p>
      <h1>Bantuan dan keluhan.</h1>
      ${user?.role === 'admin' ? '' : `<a class="btn solid" href="/tickets/new" data-link>Buka Tiket</a>`}
    </section>
    <section class="section compact">
      <div class="list-shell">
        ${tickets.map((ticket) => `
          <a class="list-item" href="/tickets/${ticket.id}" data-link>
            <div>
              <strong>${ticket.subject || '-'}</strong>
              <p>${ticket.booking_code || '-'} · ${ticket.status_label || ticket.status}</p>
              <small>${ticket.created_at ? new Date(ticket.created_at).toLocaleString('id-ID') : '-'}</small>
            </div>
          </a>
        `).join('') || `<div class="empty">Belum ada tiket.</div>`}
      </div>
    </section>
  `;
}

export async function ticketCreatePage(app) {
  app.innerHTML = `<div class="loader">Menyiapkan tiket</div>`;
  const payload = await api.bookings();
  const bookings = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="auth-screen wide">
      <form class="auth-box booking-box" id="ticketForm">
        <p class="eyebrow dark">Tiket Baru</p>
        <h1>Buka bantuan.</h1>
        <label>Booking
          <select name="booking_id" required>
            <option value="">Pilih booking</option>
            ${bookings.map((booking) => `<option value="${booking.id}">${booking.booking_code} - ${booking.vehicle?.name || '-'}</option>`).join('')}
          </select>
        </label>
        <label>Subjek<input name="subject" required maxlength="255" /></label>
        <label>Prioritas
          <select name="priority">
            <option value="normal">Normal</option>
            <option value="urgent">Urgent</option>
          </select>
        </label>
        <label>Pesan<textarea name="message" required maxlength="2000"></textarea></label>
        <button class="btn solid full" type="submit">Kirim Tiket</button>
        <p class="form-note" id="ticketNote"></p>
      </form>
    </section>
  `;

  document.getElementById('ticketForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      const ticket = await api.createTicket({
        booking_id: form.get('booking_id'),
        subject: form.get('subject'),
        priority: form.get('priority'),
        message: form.get('message'),
      });
      navigate(`/tickets/${ticket.id}`);
    } catch (error) {
      document.getElementById('ticketNote').textContent = error.message;
    }
  });
}

export async function ticketDetailPage(app, params) {
  app.innerHTML = `<div class="loader">Memuat detail tiket</div>`;
  const user = getUser();
  const ticket = user?.role === 'admin' ? await api.adminTicket(params.id) : await api.ticket(params.id);
  const messages = ticket.messages || [];

  app.innerHTML = `
    <section class="chat-screen">
      <div class="chat-head">
        <a class="btn ghost" href="/tickets" data-link>Kembali</a>
        <strong>${ticket.subject || 'Tiket'}</strong>
        ${user?.role === 'admin' ? `
          <select id="ticketStatus">
            ${['open', 'in_progress', 'resolved', 'closed'].map((status) => `<option value="${status}" ${ticket.status === status ? 'selected' : ''}>${status}</option>`).join('')}
          </select>
        ` : ''}
      </div>
      <div class="chat-thread">
        ${messages.map((message) => `
          <div class="bubble ${message.sender === 'admin' ? 'admin' : 'mine'}">
            <small>${message.sender_name || message.sender}</small>
            <p>${message.message}</p>
          </div>
        `).join('') || `<div class="empty">Belum ada pesan.</div>`}
      </div>
      <form class="chat-form" id="ticketReplyForm">
        <input name="message" autocomplete="off" required maxlength="2000" placeholder="Tulis balasan" />
        <button class="btn solid" type="submit">Kirim</button>
      </form>
    </section>
  `;

  document.getElementById('ticketReplyForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = new FormData(event.currentTarget).get('message');
    if (user?.role === 'admin') await api.adminReplyTicket(params.id, message);
    else await api.replyTicket(params.id, message);
    await ticketDetailPage(app, params);
  });

  document.getElementById('ticketStatus')?.addEventListener('change', async (event) => {
    await api.adminUpdateTicketStatus(params.id, event.target.value);
    await ticketDetailPage(app, params);
  });
}
