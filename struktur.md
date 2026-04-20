# Struktur Folder Proyek

Dokumentasi struktur folder utama proyek Laravel.

## app\Http

Berisi Controller, Middleware, dan Form Request untuk handling HTTP request.

```
app\Http\
├── Controllers\
│   ├── Admin\
│   │   ├── BookingController.php
│   │   ├── DriverController.php
│   │   ├── MapsController.php
│   │   ├── PaymentController.php
│   │   ├── ReportController.php
│   │   ├── StorageController.php
│   │   ├── UserController.php
│   │   └── VehicleController.php
│   ├── Api\
│   │   ├── AuthController.php
│   │   ├── BookingController.php
│   │   ├── DashboardController.php
│   │   ├── DriverController.php
│   │   ├── PaymentController.php
│   │   ├── UserController.php
│   │   └── VehicleController.php
│   ├── Auth\
│   │   ├── AuthenticatedSessionController.php
│   │   ├── ConfirmablePasswordController.php
│   │   ├── EmailVerificationNotificationController.php
│   │   ├── EmailVerificationPromptController.php
│   │   ├── NewPasswordController.php
│   │   ├── PasswordController.php
│   │   ├── PasswordResetLinkController.php
│   │   ├── RegisteredUserController.php
│   │   └── VerifyEmailController.php
│   ├── Driver\
│   │   └── BookingController.php
│   ├── Pengguna\
│   │   ├── BookingController.php
│   │   ├── PaymentController.php
│   │   └── VehicleController.php
│   ├── BookingController.php
│   ├── Controller.php
│   ├── DashboardController.php
│   ├── NotificationController.php
│   ├── ProfileController.php
│   └── WelcomeController.php
├── Middleware\
│   ├── CheckRole.php
│   └── RoleMiddleware.php
└── Requests\
    ├── Api\
    ├── Auth\
    └── ProfileUpdateRequest.php
```

## app\Models

Berisi Eloquent Model untuk database entity.

```
app\Models\
├── Booking.php
├── LandingSetting.php
├── Notification.php
├── Payment.php
├── PersonalAccessToken.php
├── User.php
└── Vehicle.php
```

## resources

Berisi aset frontend (CSS, JavaScript) dan view template Blade.

```
resources\
├── css\
│   └── app.css
├── js\
│   ├── app.js
│   └── bootstrap.js
└── views\
    ├── admin\
    │   ├── bookings\
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── dashboard.blade.php
    │   ├── drivers\
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── maps\
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── payments\
    │   │   └── index.blade.php
    │   ├── reports\
    │   │   └── index.blade.php
    │   ├── storage\
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   ├── users\
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   └── vehicles\
    │       ├── create.blade.php
    │       ├── edit.blade.php
    │       ├── index.blade.php
    │       └── _form.blade.php
    ├── auth\
    │   ├── confirm-password.blade.php
    │   ├── forgot-password.blade.php
    │   ├── login.blade.php
    │   ├── register.blade.php
    │   ├── reset-password.blade.php
    │   └── verify-email.blade.php
    ├── components\
    │   ├── action-message.blade.php
    │   ├── application-logo.blade.php
    │   ├── auth-session-status.blade.php
    │   ├── danger-button.blade.php
    │   ├── dropdown-link.blade.php
    │   ├── dropdown.blade.php
    │   ├── input-error.blade.php
    │   ├── input-label.blade.php
    │   ├── modal.blade.php
    │   ├── nav-link.blade.php
    │   ├── primary-button.blade.php
    │   ├── responsive-nav-link.blade.php
    │   ├── secondary-button.blade.php
    │   ├── sidebar-link.blade.php
    │   └── text-input.blade.php
    ├── dashboard.blade.php
    ├── driver\
    │   ├── bookings\
    │   │   ├── available.blade.php
    │   │   ├── index.blade.php
    │   │   └── show.blade.php
    │   └── dashboard.blade.php
    ├── layouts\
    ├── livewire\
    ├── notifications\
    ├── pengguna\
    ├── profile\
    ├── profile.blade.php
    ├── welcome.blade.php
    └── dashboard.blade.php
│       ├── layouts\
│       │   ├── app.blade.php
│       │   ├── guest.blade.php
│       │   ├── navex.blade.php
│       │   └── navigation.blade.php
│       ├── livewire\
│       │   ├── layout\
│       │   ├── pages\
│       │   ├── profile\
│       │   └── welcome\
│       ├── notifications\
│       │   └── index.blade.php
│       ├── pengguna\
│       │   ├── bookings\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── dashboard.blade.php
│       │   ├── payments\
│       │   │   └── index.blade.php
│       │   └── vehicles\
│       │       ├── book.blade.php
│       │       └── index.blade.php
│       ├── profile\
│       │   ├── edit.blade.php
│       │   └── partials\
│       │       ├── delete-user-form.blade.php
│       │       ├── update-password-form.blade.php
│       │       └── update-profile-information-form.blade.php
│       ├── profile.blade.php
│       └── welcome.blade.php
├── routes\
│   ├── api.php
│   ├── auth.php
│   ├── console.php
│   └── web.php
└── tests\
    ├── Feature\
    ├── Pest.php
    ├── TestCase.php
    └── Unit\
```
