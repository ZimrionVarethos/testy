# Bening Rental — REST API + Android Integration
## Dokumentasi Lengkap

---

## 📋 Analisis Fitur Website Asli

Berdasarkan analisis kode Blade, website Bening Rental memiliki fitur:

| Modul | Fitur |
|-------|-------|
| **Auth** | Login, Register, Logout, Forgot Password |
| **Kendaraan** | CRUD Kendaraan (Admin), foto dengan focal point, filter status/tipe |
| **Booking** | Buat booking, lihat daftar & detail, cancel, filter status |
| **Driver** | Daftar driver, detail + riwayat trip, toggle aktif/nonaktif |
| **Pembayaran** | Riwayat transaksi confirmed/ongoing/completed |
| **Dashboard** | Statistik ringkas, armada, pesanan pending |
| **Laporan** | Statistik lengkap, grafik booking per bulan |
| **Pengguna** | Daftar user, detail, toggle aktif/nonaktif |

---

## 🗂️ Struktur API Laravel

```
routes/
└── api.php                                  ← Semua route API

app/Http/
├── Controllers/Api/
│   ├── AuthController.php                   ← Login, Register, Logout, Me, Profile
│   ├── VehicleController.php                ← CRUD Kendaraan + foto
│   ├── BookingController.php                ← CRUD Booking + status flow
│   ├── DriverController.php                 ← Daftar & detail driver
│   ├── PaymentController.php                ← Riwayat pembayaran
│   ├── DashboardController.php              ← Dashboard & laporan
│   └── UserController.php                   ← Manajemen pengguna
│
├── Requests/Api/
│   ├── LoginRequest.php
│   ├── RegisterRequest.php
│   ├── StoreVehicleRequest.php
│   ├── StoreBookingRequest.php
│   └── UpdateProfileRequest.php
│
└── Middleware/
    └── CheckRole.php                        ← Guard: admin | driver | customer
```

---

## 🔗 Daftar Endpoint API

### Auth
| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| POST | `/api/v1/auth/register` | Registrasi | ✗ |
| POST | `/api/v1/auth/login` | Login | ✗ |
| POST | `/api/v1/auth/logout` | Logout | ✓ |
| GET  | `/api/v1/auth/me` | Profil saya | ✓ |
| PUT  | `/api/v1/auth/profile` | Update profil | ✓ |
| POST | `/api/v1/auth/forgot-password` | Reset password | ✗ |

### Kendaraan
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET    | `/api/v1/vehicles` | Daftar kendaraan (filter, paginate) | All |
| GET    | `/api/v1/vehicles/{id}` | Detail kendaraan | All |
| POST   | `/api/v1/vehicles` | Tambah kendaraan | Admin |
| PUT    | `/api/v1/vehicles/{id}` | Edit kendaraan | Admin |
| DELETE | `/api/v1/vehicles/{id}` | Hapus kendaraan | Admin |

### Booking
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET  | `/api/v1/bookings` | Daftar booking | All |
| POST | `/api/v1/bookings` | Buat booking | Customer |
| GET  | `/api/v1/bookings/{id}` | Detail booking | All |
| POST | `/api/v1/bookings/{id}/cancel` | Batalkan | Customer/Admin |
| POST | `/api/v1/bookings/{id}/accept` | Terima (driver) | Driver |
| POST | `/api/v1/bookings/{id}/confirm` | Konfirmasi | Admin |

### Admin
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/dashboard` | Statistik dashboard |
| GET | `/api/v1/reports` | Laporan lengkap |
| GET | `/api/v1/payments` | Riwayat pembayaran |
| GET | `/api/v1/users` | Daftar pengguna |
| POST | `/api/v1/users/{id}/toggle` | Toggle aktif user |
| GET | `/api/v1/drivers` | Daftar driver |
| POST | `/api/v1/drivers/{id}/toggle` | Toggle aktif driver |

---

## 📱 Struktur Project Android

```
app/src/main/java/com/beningrental/app/
├── api/
│   ├── ApiService.java          ← Interface Retrofit (semua endpoint)
│   └── RetrofitClient.java      ← Singleton Retrofit + OkHttp
│
├── model/
│   ├── Vehicle.java             ← Model kendaraan
│   ├── Booking.java             ← Model booking (nested: Location, User, Vehicle, Driver)
│   ├── User.java                ← Model pengguna
│   ├── request/
│   │   ├── LoginRequest.java
│   │   ├── RegisterRequest.java
│   │   └── BookingRequest.java
│   └── response/
│       ├── AuthResponse.java
│       ├── VehicleListResponse.java
│       ├── VehicleResponse.java
│       ├── BookingListResponse.java
│       ├── BookingResponse.java
│       └── DashboardResponse.java
│
├── adapter/
│   └── VehicleAdapter.java      ← RecyclerView adapter dengan ViewHolder
│
├── ui/
│   ├── auth/
│   │   └── LoginActivity.java   ← Login dengan error handling
│   ├── vehicle/
│   │   └── VehicleListActivity.java ← Daftar kendaraan + infinite scroll
│   └── booking/
│       └── CreateBookingActivity.java ← Form booking dengan date picker
│
└── utils/
    └── SessionManager.java      ← SharedPreferences: token & user
```

---

## ⚙️ Setup Laravel API

### 1. Install Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 2. Daftarkan Middleware di bootstrap/app.php
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
    $middleware->statefulApi();
})
```

### 3. Update User Model
```php
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;

class User extends Model implements AuthenticatableContract {
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

### 4. Konfigurasi CORS (config/cors.php)
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'],
'allowed_headers' => ['*'],
'allowed_methods' => ['*'],
```

---

## ⚙️ Setup Android

### 1. Tambah dependencies di `app/build.gradle`
Lihat file `app/build.gradle` yang disertakan.

### 2. AndroidManifest.xml — Izin Internet
```xml
<uses-permission android:name="android.permission.INTERNET" />

<!-- Untuk emulator (HTTP) -->
<application android:usesCleartextTraffic="true" ...>
```

### 3. Ganti BASE_URL di RetrofitClient.java
```java
// Emulator Android Studio
private static final String BASE_URL = "http://10.0.2.2:8000/api/v1/";

// Produksi
private static final String BASE_URL = "https://your-domain.com/api/v1/";
```

### 4. Cara Pakai ApiService
```java
// Inisialisasi
ApiService api = RetrofitClient.getApiService(context);
SessionManager session = new SessionManager(context);

// Contoh GET kendaraan
api.getVehicles(session.getBearerToken(), "available", null, null, null, 12, 1)
   .enqueue(new Callback<VehicleListResponse>() {
       @Override
       public void onResponse(Call<VehicleListResponse> call,
                              Response<VehicleListResponse> response) {
           if (response.isSuccessful() && response.body().isSuccess()) {
               List<Vehicle> vehicles = response.body().getData();
               // Update RecyclerView...
           }
       }
       @Override
       public void onFailure(Call<VehicleListResponse> call, Throwable t) {
           // Tampilkan error koneksi
       }
   });
```

---

## 🔐 Flow Status Booking

```
[Customer] Buat booking → status: pending
    ↓
[Driver]   Accept        → status: accepted
    ↓
[Admin]    Konfirmasi    → status: confirmed
    ↓
           Mulai         → status: ongoing  (update manual/otomatis)
    ↓
           Selesai       → status: completed
    
[Customer/Admin] Cancel  → status: cancelled (kecuali ongoing/completed)
```

---

## 🧪 Contoh Test dengan cURL

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'

# Ambil kendaraan tersedia
curl -X GET "http://localhost:8000/api/v1/vehicles?status=available" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Buat booking
curl -X POST http://localhost:8000/api/v1/bookings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id": "xxx",
    "start_date": "2025-07-01",
    "end_date": "2025-07-04",
    "pickup_address": "Jl. Sudirman No. 1, Jakarta"
  }'
```
