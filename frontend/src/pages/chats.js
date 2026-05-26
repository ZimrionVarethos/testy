import { api, getUser } from '../api.js';

export async function chatsPage(app) {
  app.innerHTML = `<div class="loader">Memuat chat</div>`;
  const chats = await api.chats();

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Chat</p>
      <h1>Ruang chat booking.</h1>
    </section>
    <section class="section compact">
      <div class="list-shell">
        ${(chats || []).map((chat) => `
          <a class="list-item" href="/chats/${chat.booking_id}" data-link>
            <div>
              <strong>${chat.vehicle_name || chat.booking_code || '-'}</strong>
              <p>${chat.last_message || 'Belum ada pesan.'}</p>
              <small>${chat.partner_name || '-'} · ${chat.status || '-'}</small>
            </div>
            ${chat.unread_count ? `<span class="badge">${chat.unread_count}</span>` : ''}
          </a>
        `).join('') || `<div class="empty">Belum ada chat aktif.</div>`}
      </div>
    </section>
  `;
}

export async function chatRoomPage(app, params) {
  app.innerHTML = `<div class="loader">Membuka chat</div>`;
  const user = getUser();
  const messages = await api.messages(params.id);

  app.innerHTML = `
    <section class="chat-screen">
      <div class="chat-head">
        <a class="btn ghost" href="/chats" data-link>Kembali</a>
        <strong>Booking ${params.id.slice(-6)}</strong>
      </div>
      <div class="chat-thread" id="chatThread">
        ${(messages || []).map((message) => `
          <div class="bubble ${message.sender_id === user?.id ? 'mine' : ''}">
            <small>${message.sender_name || message.sender_role}</small>
            <p>${message.message}</p>
          </div>
        `).join('') || `<div class="empty">Belum ada pesan.</div>`}
      </div>
      <form class="chat-form" id="chatForm">
        <input name="message" autocomplete="off" required maxlength="1000" placeholder="Tulis pesan" />
        <button class="btn solid" type="submit">Kirim</button>
      </form>
    </section>
  `;

  document.getElementById('chatForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    await api.sendMessage(params.id, form.get('message'));
    await chatRoomPage(app, params);
  });
}
