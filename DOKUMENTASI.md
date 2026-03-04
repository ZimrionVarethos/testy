# 📚 Dokumentasi Project — Bening Rental
> Laravel 12 + MongoDB Atlas + Livewire Volt

---

## 🗂️ Daftar Isi
1. [Stack & Dependency](#stack)
2. [Struktur Folder](#struktur)
3. [Konfigurasi](#konfigurasi)
4. [Models](#models)
5. [Alur Sistem & Status](#alur)
6. [Controllers](#controllers)
7. [Routes](#routes)
8. [Views & Blade](#views)
9. [Seeder & Data Awal](#seeder)
10. [Scheduler](#scheduler)
11. [Troubleshooting](#troubleshooting)

---

## 1. Stack & Dependency {#stack}

| Komponen | Versi / Keterangan |
|---|---|
| Laravel | 12.x |
| PHP | 8.3 |
| Database | MongoDB Atlas (cloud) |
| MongoDB Driver | `mongodb/laravel-mongodb` |
| Auth/Frontend | Livewire Volt + Breeze |
| CSS | Tailwind CSS |
| JS Alpine | Alpine.js (sidebar toggle) |
| Payment | Midtrans (belum terintegrasi penuh) |

### Install dependency MongoDB
```bash
composer require mongodb/laravel-mongodb
```

---

## 2. Struktur Folder {#struktur}

```
app/
├── Console/Commands/
│   └── UpdateBookingStatus.php        # Scheduler auto-update status booking
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php    # Gerbang dashboard semua role
│   │   ├── NotificationController.php
│   │   ├── Admin/
│   │   │   ├── BookingController.php
│   │   │   ├── VehicleController.php
│   │   │   ├── DriverController.php
│   │   │   ├── UserController.php
│   │   │   ├── PaymentController.php
│   │   │   └── ReportController.php
│   │   ├── Pengguna/
│   │   │   ├── BookingController.php
│   │   │   ├── VehicleController.php
│   │   │   └── PaymentController.php
│   │   └── Driver/
│   │       └── BookingController.php
│   └── Middleware/
│       └── RoleMiddleware.php         # Cek role user
├── Models/
│   ├── User.php
│   ├── Vehicle.php
│   ├── Booking.php
│   ├── Payment.php
│   └── Notification.php
└── Services/
    └── BookingService.php             # Business logic utama

database/seeders/
├── DatabaseSeeder.php
├── UserSeeder.php
├── VehicleSeeder.php
├── BookingSeeder.php
└── NotificationSeeder.php

resources/views/
├── layouts/
│   ├── app.blade.php                  # Layout utama + sidebar state
│   └── navigation.blade.php          # Sidebar per role
├── dashboard.blade.php               # Gerbang @include per role
├── admin/
│   ├── dashboard.blade.php
│   ├── bookings/{index,show}
│   ├── vehicles/{index,create,edit,_form}
│   ├── drivers/index
│   ├── users/index
│   ├── payments/index
│   └── reports/index
├── pengguna/
│   ├── dashboard.blade.php
│   ├── bookings/{index,show}
│   ├── vehicles/{index,book}
│   └── payments/index
├── driver/
│   ├── dashboard.blade.php
│   └── bookings/{available,index,show}
├── notifications/index
└── profile/
    ├── edit.blade.php
    └── partials/
        ├── update-profile-information-form.blade.php
        ├── update-password-form.blade.php
        └── delete-user-form.blade.php

routes/
├── web.php
├── auth.php                           # Auto-generate Breeze
└── console.php                        # Schedule booking:update-status
```

---

## 3. Konfigurasi {#konfigurasi}

### `.env`
```env
DB_CONNECTION=mongodb
DB_URI=mongodb+srv://rental:rental123@cluster0.gbhj3z7.mongodb.net/rental?retryWrites=true&w=majority
DB_DATABASE=rental
```

### `config/database.php`
```php
'mongodb' => [
    'driver'   => 'mongodb',
    'dsn'      => env('DB_URI'),
    'database' => env('DB_DATABASE', 'rental'),
],
```

### `bootstrap/app.php` — Daftarkan middleware
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

---

## 4. Models {#models}

### ⚠️ Aturan Penting Model MongoDB
Semua model WAJIB:
- `use MongoDB\Laravel\Eloquent\Model;` (bukan Eloquent default)
- `protected $connection = 'mongodb';`
- `protected $collection = 'nama_collection';`

Khusus `User.php`:
- `use MongoDB\Laravel\Auth\User as Authenticatable;` ← **bukan** `Illuminate\Foundation\Auth\User`
- Jangan pakai `HasFactory` — tidak kompatibel dengan MongoDB

### Collections di MongoDB

| Collection | Model | Keterangan |
|---|---|---|
| `users` | `User` | Admin, pengguna, driver (dibedakan field `role`) |
| `vehicles` | `Vehicle` | Data armada kendaraan |
| `bookings` | `Booking` | Inti transaksi pemesanan |
| `payments` | `Payment` | Histori pembayaran Midtrans |
| `notifications` | `Notification` | Notifikasi per user |

### User — Field Penting
```
name, email, password, role (admin|pengguna|driver),
phone, is_active, email_verified_at,
driver_profile: { license_number, license_expiry, is_available,
                  current_location: {lat, lng},
                  rating_avg, total_trips }
```
> `driver_profile` hanya ada di user dengan role `driver`.
> `phone` hanya ada di driver — pengguna tidak punya (registrasi cuma email).

### Vehicle — Field Penting
```
name, brand, model, year, plate_number, type, capacity,
price_per_day, status (available|rented|maintenance),
features[], images[], rating_avg, total_bookings
```

### Booking — Field Penting
```
booking_code, status, user{}, vehicle{}, driver{},
pickup{address,lat,lng}, dropoff{address,lat,lng},
start_date, end_date, duration_days, total_price, notes,
accepted_at, confirmed_at, started_at, completed_at,
cancelled_at, cancel_reason
```
> `user`, `vehicle`, `driver` adalah **embedded snapshot** — data dicopy saat booking dibuat agar histori tetap akurat walau data asli berubah.

---

## 5. Alur Sistem & Status {#alur}

### Status Flow Booking
```
[User buat booking]
      ↓ status: PENDING
      ↓ notif broadcast → semua driver available

[Driver ambil pesanan]
      ↓ status: ACCEPTED
      ↓ driver.is_available = false
      ↓ notif → admin + user

[Admin konfirmasi]
      ↓ status: CONFIRMED
      ↓ vehicle.status MASIH "available" ← sengaja, belum rented
      ↓ notif → user + driver

[start_date tiba — otomatis via SCHEDULER]
      ↓ status: ONGOING
      ↓ vehicle.status → "rented" ← BARU BERUBAH DI SINI
      ↓ notif → user

[end_date tiba — otomatis via SCHEDULER]
      ↓ status: COMPLETED
      ↓ vehicle.status → "available"
      ↓ driver.is_available = true
      ↓ notif → user
```

### ⚠️ Bug Lama yang Sudah Diperbaiki
> **Problem:** Vehicle langsung jadi "rented" saat booking dibuat, padahal harusnya saat mobil sudah dibawa pergi (start_date).
>
> **Fix:** `vehicle.status = rented` hanya diset di `BookingService::startBooking()` yang dipanggil oleh scheduler saat `start_date <= now()`.

### Pengecekan Konflik Tanggal
Sistem tidak cek `vehicle.status` untuk validasi ketersediaan, melainkan cek overlap booking aktif:
```php
Booking::where('vehicle.vehicle_id', $vehicleId)
    ->whereIn('status', ['accepted', 'confirmed', 'ongoing'])
    ->where(/* overlap tanggal */)
    ->exists();
```

---

## 6. Controllers {#controllers}

### DashboardController
Satu controller, tiga dashboard — pakai `match($role)` untuk redirect ke method yang tepat.
- `adminDashboard()` — stats, recent bookings, accepted bookings
- `penggunaDashboard()` — active bookings, notifikasi
- `driverDashboard()` — available bookings, my active bookings

### BookingService (Services layer)
Business logic booking dipusatkan di sini, bukan di controller:

| Method | Dipanggil dari | Keterangan |
|---|---|---|
| `createBooking()` | Pengguna\VehicleController | Buat booking + notif driver |
| `driverAcceptBooking()` | Driver\BookingController | Driver ambil pesanan |
| `adminConfirmBooking()` | Admin\BookingController | Admin konfirmasi |
| `startBooking()` | Scheduler | Auto: confirmed → ongoing |
| `completeBooking()` | Scheduler | Auto: ongoing → completed |
| `cancelBooking()` | Admin & Pengguna | Cancel pesanan |

---

## 7. Routes {#routes}

### Prefix & Middleware
| Prefix | Middleware | Role |
|---|---|---|
| `/dashboard` | `auth, verified` | semua |
| `/admin/*` | `auth, verified, role:admin` | admin |
| `/bookings`, `/vehicles`, `/payments` | `auth, verified, role:pengguna,user` | pengguna |
| `/driver/*` | `auth, verified, role:driver` | driver |
| `/notifications` | `auth, verified` | semua |
| `/profile` | `auth` | semua |

### Route Profile
Jika muncul error `route [profile.edit] not defined`, tambahkan di `routes/web.php`:
```php
// Jika pakai Livewire Volt:
use Livewire\Volt\Volt;
Volt::route('profile/edit', 'profile.edit')->name('profile.edit');

// Atau jika pakai ProfileController biasa:
Route::middleware('auth')->group(function () {
    Route::get('/profile',   [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
});
```

---

## 8. Views & Blade {#views}

### Layout Sidebar (`layouts/app.blade.php`)
- Alpine.js state: `x-data="{ sidebarOpen: true }"`
- Sidebar fixed position, konten geser dengan `:style="sidebarOpen ? 'margin-left: 16rem' : 'margin-left: 0'"`
- Tombol hamburger ada di semua ukuran layar (bukan hanya mobile)
- Mobile: sidebar overlay di atas konten (tidak geser)
- Desktop: sidebar mendorong konten

### Sidebar (`layouts/navigation.blade.php`)
- Menu berbeda per `$role` (admin / pengguna / driver)
- Menggunakan inline style untuk transform agar tidak bergantung Tailwind JIT:
```html
:style="sidebarOpen ? 'transform: translateX(0)' : 'transform: translateX(-100%)'"
style="background-color: #111827; ..."
```

### Komponen `x-sidebar-link`
Menerima props: `href`, `active` (boolean), `icon` (string nama icon).

### Dashboard Gerbang (`dashboard.blade.php`)
```blade
@if($role === 'admin')    @includeIf('admin.dashboard')
@elseif($role === 'pengguna') @includeIf('pengguna.dashboard')
@elseif($role === 'driver')   @includeIf('driver.dashboard')
```

---

## 9. Seeder & Data Awal {#seeder}

```bash
# Jalankan semua seeder
php artisan db:seed

# Reset total + seed ulang
php artisan migrate:fresh --seed
# (untuk MongoDB tidak ada migrate, jadi collection otomatis dibuat saat insert)

# Untuk MongoDB tanpa migrate, cukup:
php artisan db:seed
```

### Akun Default

| Email | Password | Role |
|---|---|---|
| mochfarelaz@gmail.com | admin123 | admin |
| budi@example.com | password123 | pengguna |
| siti@example.com | password123 | pengguna |
| rina@example.com | password123 | pengguna |
| andi.driver@example.com | password123 | driver |
| rizky.driver@example.com | password123 | driver |
| doni.driver@example.com | password123 | driver |

### Data Seeder yang Dibuat
- **7 user** (1 admin, 3 pengguna, 3 driver)
- **6 kendaraan** (berbagai tipe, termasuk 1 rented & 1 maintenance)
- **6 booking** (satu per status: pending, accepted, confirmed, ongoing, completed, cancelled)
- **10 notifikasi** (tersebar ke semua role)

---

## 10. Scheduler {#scheduler}

### Command
```bash
php artisan booking:update-status
```
Menjalankan dua hal:
1. Booking `confirmed` yang `start_date <= now()` → ubah ke `ongoing`, vehicle → `rented`
2. Booking `ongoing` yang `end_date <= now()` → ubah ke `completed`, vehicle → `available`

### Setup Cron di Server
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Konfigurasi di `routes/console.php`
```php
Schedule::command('booking:update-status')->everyFiveMinutes();
```

---

## 11. Troubleshooting {#troubleshooting}

### ❌ `Call to a member function prepare() on null`
**Penyebab:** Model `User` masih extend dari `Illuminate\Foundation\Auth\User` (SQL).
**Fix:**
```php
// app/Models/User.php
use MongoDB\Laravel\Auth\User as Authenticatable; // ← ganti ini
```

---

### ❌ `Class "App\Models\XXX" not found`
**Penyebab:** Model belum dibuat.
**Fix:** Buat file model di `app/Models/NamaModel.php` dengan:
```php
use MongoDB\Laravel\Eloquent\Model;
class NamaModel extends Model {
    protected $connection = 'mongodb';
    protected $collection = 'nama_collection';
}
```

---

### ❌ `Route [profile.edit] not defined`
**Penyebab:** Route profile belum didaftarkan di `web.php`.
**Fix:** Tambahkan route profile (lihat [bagian Routes](#routes)).

---

### ❌ Sidebar tidak terlihat / menimpa konten
**Penyebab:** Tailwind JIT tidak generate class `-translate-x-full` atau `bg-gray-900`.
**Fix:** Gunakan inline style di `navigation.blade.php`:
```html
:style="sidebarOpen ? 'transform: translateX(0)' : 'transform: translateX(-100%)'"
style="background-color: #111827;"
```
Dan di `app.blade.php`:
```html
:style="sidebarOpen ? 'margin-left: 16rem' : 'margin-left: 0'"
```

---

### ❌ `Route [xxx] not defined` dari navigation
**Penyebab:** Route belum terdaftar di `web.php` tapi sudah dipanggil di sidebar.
**Cek:** `php artisan route:list | grep nama-route`
**Sementara:** Hapus/comment link di navigation sampai page dibuat.

---

### ❌ Vehicle langsung `rented` saat booking dibuat
**Penyebab:** Bug lama — status di-set saat booking created/confirmed.
**Fix yang benar:** Status `rented` hanya diset di `BookingService::startBooking()` (dipanggil scheduler saat `start_date` tiba).

---

### ❌ `mongodb` connection not found
**Penyebab:** `config/database.php` belum ada koneksi mongodb, atau `.env` salah.
**Cek:**
```bash
php artisan tinker
>>> DB::connection('mongodb')->listCollections()
```
**Fix:** Pastikan `DB_URI` di `.env` benar dan `config/database.php` punya entry `mongodb`.

---

### ❌ Seeder error: duplicate key / data sudah ada
**Fix:**
```bash
# Drop semua collection dulu via MongoDB Atlas UI atau tinker:
php artisan tinker
>>> DB::connection('mongodb')->dropDatabase('rental')
# Lalu seed ulang:
php artisan db:seed
```

---

### ❌ `HasFactory` error di MongoDB
**Penyebab:** Laravel factory default tidak kompatibel MongoDB.
**Fix:** Hapus `use HasFactory` dari semua model MongoDB.

---

## 📝 Catatan Pengembangan Selanjutnya

- [ ] Integrasi Midtrans untuk pembayaran otomatis
- [ ] Model `Payment` siap, tinggal connect ke webhook Midtrans
- [ ] Tracking real-time driver (pertimbangkan Laravel Reverb / Pusher)
- [ ] Upload foto kendaraan (Laravel Storage + S3/local)
- [ ] Halaman review & rating setelah booking selesai
- [ ] Admin: halaman detail driver & detail user
- [ ] Pengguna: halaman detail vehicle sebelum booking
- [ ] Export laporan ke PDF/Excel
- [ ] Email notifikasi (Laravel Mail + Queue)
