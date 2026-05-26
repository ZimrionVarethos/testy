import { api } from '../api.js';
import { money } from '../ui.js';

export async function homePage(app) {
  app.innerHTML = `<div class="loader">Memuat landing</div>`;

  const data = await api.landing();
  const slide = data.heroSlides?.[0] || '';
  const vehicles = data.vehicles || [];
  const testimonials = data.testimonials?.length ? data.testimonials : [
    { init: 'BR', name: 'Pelanggan Bening', role: 'Pelanggan', text: 'Booking cepat, driver rapi, dan mobil bersih.', stars: 5 },
  ];

  app.innerHTML = `
    <section class="hero">
      <img src="${slide}" alt="" class="hero-bg" fetchpriority="high" />
      <div class="hero-overlay"></div>
      <div class="hero-grid">
        <p class="eyebrow">Rental mobil premium Indonesia</p>
        <h1>Bening Rental</h1>
        <p class="lead">Armada rapi, driver profesional, booking online, dan tracking status dari satu sistem yang siap dipisah untuk web dan mobile.</p>
        <div class="hero-actions">
          <a class="btn solid invert" href="/vehicles" data-link>Lihat Armada</a>
          <a class="btn outline invert" href="/login" data-link>Masuk Akun</a>
        </div>
      </div>
    </section>

    <section class="stats-band">
      <div><strong>${data.stats?.total_vehicles || 0}</strong><span>Kendaraan</span></div>
      <div><strong>${data.stats?.total_bookings || 0}</strong><span>Trip Selesai</span></div>
      <div><strong>${data.stats?.happy_customers || 0}</strong><span>Pelanggan</span></div>
    </section>

    <section class="section">
      <div class="section-head">
        <p class="eyebrow dark">Armada Pilihan</p>
        <h2>Siap booking tanpa reload halaman.</h2>
      </div>
      <div class="vehicle-grid">
        ${vehicles.map((vehicle) => `
          <a class="vehicle-card" href="/vehicles/${vehicle.id}" data-link>
            <img src="${vehicle.images?.[0] || ''}" alt="${vehicle.name || 'Kendaraan'}" loading="lazy" decoding="async" />
            <div class="vehicle-meta">
              <span>${vehicle.type || 'Mobil'}</span>
              <span>${vehicle.capacity || '-'} Kursi</span>
            </div>
            <h3>${vehicle.name || `${vehicle.brand || ''} ${vehicle.model || ''}`}</h3>
            <p>${money(vehicle.price_per_day)} / hari</p>
          </a>
        `).join('')}
      </div>
    </section>

    <section class="section navy">
      <div class="section-head">
        <p class="eyebrow">Testimoni</p>
        <h2>Feedback pelanggan tetap ringkas dan mudah dipindah ke app.</h2>
      </div>
      <div class="testimonial-grid">
        ${testimonials.slice(0, 3).map((item) => `
          <article class="testimonial">
            <div class="stars">${'&#9733;'.repeat(Math.round(item.stars || 5))}</div>
            <p>${item.text}</p>
            <div class="author">
              ${item.avatar ? `<img src="${item.avatar}" alt="${item.name}" />` : `<span>${item.init || 'BR'}</span>`}
              <div><strong>${item.name}</strong><small>${item.role}</small></div>
            </div>
          </article>
        `).join('')}
      </div>
    </section>
  `;
}
