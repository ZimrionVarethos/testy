# Contoh Response JSON — Bening Rental API

## 1. POST /api/v1/auth/login
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": "686abc123def456",
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "081234567890",
      "role": "customer",
      "is_active": true,
      "created_at": "2025-01-15T09:30:00+07:00"
    },
    "token": "1|abc123def456xyz789..."
  }
}
```

## 2. GET /api/v1/vehicles?status=available&per_page=12
```json
{
  "success": true,
  "data": [
    {
      "id": "686aaa111bbb222",
      "name": "Toyota Innova Reborn",
      "brand": "Toyota",
      "model": "Innova Reborn",
      "year": 2023,
      "plate_number": "B 1234 ABC",
      "type": "MPV",
      "capacity": 7,
      "price_per_day": 650000,
      "status": "available",
      "features": ["AC", "Musik", "GPS", "Kamera Mundur"],
      "rating_avg": 4.8,
      "images": ["http://yourdomain.com/storage/vehicles/img_50-50.jpg"],
      "created_at": "2025-02-01T10:00:00+07:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 12,
    "total": 28
  }
}
```

## 3. POST /api/v1/bookings (Buat Booking)
**Request Body:**
```json
{
  "vehicle_id": "686aaa111bbb222",
  "start_date": "2025-07-01",
  "end_date": "2025-07-04",
  "pickup_address": "Jl. Sudirman No. 1, Jakarta Pusat",
  "pickup_lat": -6.2088,
  "pickup_lng": 106.8456,
  "notes": "Mohon tepat waktu"
}
```
**Response 201:**
```json
{
  "success": true,
  "message": "Booking berhasil dibuat. Menunggu konfirmasi driver.",
  "data": {
    "id": "686ccc333ddd444",
    "booking_code": "BRN-A1B2C3D4",
    "status": "pending",
    "start_date": "2025-07-01 00:00:00",
    "end_date": "2025-07-04 00:00:00",
    "duration_days": 3,
    "total_price": 1950000,
    "notes": "Mohon tepat waktu",
    "pickup": {
      "address": "Jl. Sudirman No. 1, Jakarta Pusat",
      "lat": -6.2088,
      "lng": 106.8456
    },
    "dropoff": null,
    "user": {
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "081234567890"
    },
    "vehicle": {
      "name": "Toyota Innova Reborn",
      "plate_number": "B 1234 ABC",
      "price_per_day": 650000
    },
    "driver": null,
    "accepted_at": null,
    "confirmed_at": null,
    "cancelled_at": null,
    "created_at": "2025-06-10T14:22:00+07:00"
  }
}
```

## 4. GET /api/v1/bookings?status=pending
```json
{
  "success": true,
  "data": [
    {
      "id": "686ccc333ddd444",
      "booking_code": "BRN-A1B2C3D4",
      "status": "pending",
      "total_price": 1950000,
      "duration_days": 3,
      "vehicle": { "name": "Toyota Innova Reborn", "plate_number": "B 1234 ABC" },
      "user": { "name": "Budi Santoso" },
      "driver": null,
      "created_at": "2025-06-10T14:22:00+07:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

## 5. GET /api/v1/dashboard (Admin)
```json
{
  "success": true,
  "data": {
    "stats": {
      "total_bookings": 142,
      "pending_bookings": 5,
      "ongoing_bookings": 3,
      "monthly_revenue": 18750000
    },
    "vehicle_stats": {
      "available": 12,
      "rented": 3,
      "maintenance": 1
    },
    "recent_bookings": [
      {
        "id": "686ccc333ddd444",
        "booking_code": "BRN-A1B2C3D4",
        "status": "pending",
        "user_name": "Budi Santoso",
        "total_price": 1950000
      }
    ],
    "accepted_bookings": []
  }
}
```

## 6. Error Response — Validasi Gagal (422)
```json
{
  "success": false,
  "message": "Validasi gagal.",
  "errors": {
    "email": ["Email sudah terdaftar."],
    "password": ["Password minimal 8 karakter."]
  }
}
```

## 7. Error Response — Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

## 8. Error Response — Forbidden (403)
```json
{
  "success": false,
  "message": "Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini."
}
```
