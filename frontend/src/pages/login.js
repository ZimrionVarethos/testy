import { api, saveSession } from '../api.js';
import { navigate } from '../router.js';

export async function loginPage(app) {
  app.innerHTML = `
    <section class="auth-screen">
      <form class="auth-box" id="loginForm">
        <p class="eyebrow dark">Login API</p>
        <h1>Masuk ke akun</h1>
        <label>Email<input name="email" type="email" autocomplete="email" required /></label>
        <label>Password<input name="password" type="password" autocomplete="current-password" required minlength="6" /></label>
        <button class="btn solid full" type="submit">Login</button>
        <p class="form-note" id="loginNote">Frontend ini memakai endpoint web khusus, jadi admin tetap bisa login tanpa mengubah endpoint mobile.</p>
        <p class="form-note">Belum punya akun? <a class="mini-link" href="/register" data-link>Daftar di sini</a>.</p>
      </form>
    </section>
  `;

  document.getElementById('loginForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const note = document.getElementById('loginNote');
    note.textContent = 'Memproses login...';

    try {
      const data = await api.login(form.get('email'), form.get('password'));
      saveSession(data);
      navigate('/dashboard');
    } catch (error) {
      note.textContent = error.message;
    }
  });
}

export async function registerPage(app) {
  app.innerHTML = `
    <section class="auth-screen">
      <form class="auth-box" id="registerForm">
        <p class="eyebrow dark">Register API</p>
        <h1>Buat akun.</h1>
        <label>Nama<input name="name" required maxlength="100" /></label>
        <label>Email<input name="email" type="email" autocomplete="email" required /></label>
        <label>No HP<input name="phone" required maxlength="20" /></label>
        <label>Password<input name="password" type="password" autocomplete="new-password" required minlength="8" /></label>
        <label>Konfirmasi Password<input name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" /></label>
        <button class="btn solid full" type="submit">Daftar</button>
        <p class="form-note" id="registerNote">Akun baru otomatis menjadi pengguna.</p>
      </form>
    </section>
  `;

  document.getElementById('registerForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const note = document.getElementById('registerNote');
    note.textContent = 'Membuat akun...';

    try {
      const data = await api.register({
        name: form.get('name'),
        email: form.get('email'),
        phone: form.get('phone'),
        password: form.get('password'),
        password_confirmation: form.get('password_confirmation'),
      });
      saveSession(data);
      navigate('/dashboard');
    } catch (error) {
      note.textContent = error.message;
    }
  });
}
