import { api } from '../api.js';

export async function notificationsPage(app) {
  app.innerHTML = `<div class="loader">Memuat notifikasi</div>`;
  const payload = await api.notifications();
  const items = Array.isArray(payload) ? payload : payload.data || [];

  app.innerHTML = `
    <section class="page-head">
      <p class="eyebrow dark">Notifikasi</p>
      <h1>Pusat update akun.</h1>
      <button class="btn solid" id="readAllBtn">Tandai Semua Dibaca</button>
    </section>
    <section class="section compact">
      <div class="list-shell">
        ${items.map((item) => `
          <article class="list-item ${item.is_read ? '' : 'unread'}">
            <div>
              <strong>${item.title || '-'}</strong>
              <p>${item.message || ''}</p>
              <small>${item.type || 'notification'} · ${item.created_at ? new Date(item.created_at).toLocaleString('id-ID') : '-'}</small>
            </div>
            ${item.is_read ? '' : `<button class="btn ghost" data-read="${item.id}">Dibaca</button>`}
          </article>
        `).join('') || `<div class="empty">Belum ada notifikasi.</div>`}
      </div>
    </section>
  `;

  document.getElementById('readAllBtn').addEventListener('click', async () => {
    await api.readAllNotifications();
    await notificationsPage(app);
  });

  document.querySelectorAll('[data-read]').forEach((button) => {
    button.addEventListener('click', async () => {
      await api.readNotification(button.dataset.read);
      await notificationsPage(app);
    });
  });
}
