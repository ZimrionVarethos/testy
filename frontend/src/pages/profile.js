import { api, getToken, getUser, saveSession } from '../api.js';

export async function profilePage(app) {
  const user = getUser();
  if (!user) {
    app.innerHTML = `
      <section class="page-head">
        <p class="eyebrow dark">Profil</p>
        <h1>Login dulu untuk mengubah profil.</h1>
        <a class="btn solid" href="/login" data-link>Login</a>
      </section>
    `;
    return;
  }

  app.innerHTML = `
    <section class="auth-screen">
      <form class="auth-box" id="profileForm">
        <p class="eyebrow dark">Profil</p>
        <h1>Akun saya.</h1>
        <label>Nama<input name="name" value="${user.name || ''}" required maxlength="100" /></label>
        <label>Email<input name="email" type="email" value="${user.email || ''}" required /></label>
        <label>No HP<input name="phone" value="${user.phone || ''}" maxlength="20" /></label>
        <label>Password Baru<input name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Kosongkan jika tidak diganti" /></label>
        <label>Konfirmasi Password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" /></label>
        <button class="btn solid full" type="submit">Simpan Profil</button>
        <p class="form-note" id="profileNote">Role: ${user.role}</p>
      </form>
    </section>
  `;

  document.getElementById('profileForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const body = {
      name: form.get('name'),
      email: form.get('email'),
      phone: form.get('phone'),
    };

    if (form.get('password')) {
      body.password = form.get('password');
      body.password_confirmation = form.get('password_confirmation');
    }

    try {
      const nextUser = await api.updateProfile(body);
      saveSession({ user: nextUser, token: getToken() });
      document.getElementById('profileNote').textContent = 'Profil berhasil diperbarui.';
    } catch (error) {
      document.getElementById('profileNote').textContent = error.message;
    }
  });
}
