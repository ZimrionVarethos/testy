## Konteks Proyek

Kamu membantu membangun aplikasi mobile (Flutter/React Native/Kotlin/Swift) untuk platform **DriveEase** — layanan car rental dengan driver.

**Backend:** Laravel 12 + MongoDB  
**Auth:** Laravel Sanctum (Bearer Token)  
**Base URL:** `https://<YOUR_DOMAIN>/api/v1`  
**Content-Type default:** `application/json`  
**Upload foto:** `multipart/form-data`

---

## Autentikasi

Semua endpoint bertanda 🔒 membutuhkan header:

```
Authorization: Bearer <token>
```

Token didapat dari response `login` atau `register`.

---

## Endpoints Auth & Profil

### Register

```
POST /api/v1/auth/register
Body (JSON):
  name     : string (required)
  email    : string (required)
  phone    : string (required)
  password : string (min 8, required)
  password_confirmation : string (required)

Response 201:
  { success: true, data: { user: UserObject, token: string } }
```

### Login

```
POST /api/v1/auth/login
Body (JSON):
  email    : string
  password : string

Response 200:
  { success: true, data: { user: UserObject, token: string } }

Catatan: role "admin" diblokir di mobile, response 403.
```

### Logout 🔒

```
POST /api/v1/auth/logout

Response 200:
  { success: true, message: "Logout berhasil." }
```

### Get Profile 🔒

```
GET /api/v1/auth/me

Response 200:
  { success: true, data: UserObject }
```

### Update Profil 🔒

```
PUT /api/v1/auth/profile
Body (JSON):
  name     : string (optional, max 100)
  phone    : string (optional, max 20)
  password : string (optional, min 8)
  password_confirmation : string (wajib jika password diisi)

Response 200:
  { success: true, message: "...", data: UserObject }
```

### Upload Foto Profil 🔒

```
POST /api/v1/auth/avatar
Content-Type: multipart/form-data
Body:
  avatar : File (image/jpeg | image/png | image/webp, max 2MB)

Response 200:
  { success: true, message: "Foto profil berhasil diperbarui.", data: UserObject }
```

---

## UserObject (struktur response user)

```json
{
    "id": "683abc...", // MongoDB ObjectId sebagai string
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone": "08123456789",
    "role": "pengguna", // "pengguna" | "driver"
    "is_active": true,
    "avatar": "https://res.cloudinary.com/...", // null jika belum upload
    "created_at": "2026-01-15T10:00:00+00:00"
}
```

---

## Endpoints Lain (ringkas)

### Kendaraan

```
GET  /api/v1/vehicles          🔒  — daftar kendaraan tersedia
GET  /api/v1/vehicles/{id}     🔒  — detail kendaraan
```

### Booking

```
GET  /api/v1/bookings          🔒  — daftar booking milik user
POST /api/v1/bookings          🔒  — buat booking baru
GET  /api/v1/bookings/{id}     🔒  — detail booking
POST /api/v1/bookings/{id}/cancel  🔒  — batalkan booking
```

### Payment

```
POST /api/v1/bookings/{id}/snap       🔒  — dapat Midtrans Snap Token
GET  /api/v1/bookings/{id}/payment-status  🔒  — cek status pembayaran
```

### Chat (per booking)

```
GET  /api/v1/bookings/{id}/messages   🔒  — riwayat pesan
POST /api/v1/bookings/{id}/messages   🔒  — kirim pesan
```

### Notifikasi

```
GET  /api/v1/notifications            🔒  — daftar notifikasi
POST /api/v1/notifications/read-all   🔒  — tandai semua dibaca
POST /api/v1/notifications/{id}/read  🔒  — tandai satu dibaca
```

### FCM Token (push notif)

```
POST   /api/v1/fcm/token   🔒  Body: { token: string }
DELETE /api/v1/fcm/token   🔒
```

### Driver (khusus role driver)

```
POST /api/v1/drivers/location  🔒  Body: { lat: float, lon: float }
POST /api/v1/bookings/{id}/pickup  🔒  — konfirmasi jemput penumpang
```

---

## Format Error

Semua error mengikuti format:

```json
{
    "success": false,
    "message": "Pesan error",
    "errors": { "field": ["detail error"] } // hanya pada validasi 422
}
```

HTTP status: `401` (unauthenticated), `403` (forbidden), `404` (not found), `422` (validation), `500` (server error)

---

## Contoh Kode (Flutter/Dart)

### Upload foto profil

```dart
Future<void> uploadAvatar(File imageFile, String token) async {
  final uri = Uri.parse('$baseUrl/api/v1/auth/avatar');
  final request = http.MultipartRequest('POST', uri)
    ..headers['Authorization'] = 'Bearer $token'
    ..files.add(await http.MultipartFile.fromPath('avatar', imageFile.path));

  final response = await request.send();
  final body = await response.stream.bytesToString();
  final json = jsonDecode(body);

  if (json['success'] == true) {
    final avatarUrl = json['data']['avatar'];
    // simpan ke state / shared preferences
  }
}
```

### Update profil

```dart
Future<void> updateProfile(String token, {String? name, String? phone}) async {
  final response = await http.put(
    Uri.parse('$baseUrl/api/v1/auth/profile'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      if (name != null) 'name': name,
      if (phone != null) 'phone': phone,
    }),
  );
  // handle response...
}
```

---

## Catatan Penting

- Simpan token Sanctum di **secure storage** (bukan SharedPreferences plain text).
- Avatar null berarti belum upload — tampilkan inisial nama sebagai fallback.
- Foto profil dikompres otomatis ke 200×200 px di server (Cloudinary).
- Role `pengguna` untuk pelanggan, `driver` untuk pengemudi — tampilkan UI berbeda sesuai role.
- Setelah logout, hapus token dari storage lokal.
