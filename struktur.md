# Struktur Proyek

Berikut adalah struktur file proyek, mengabaikan folder `node_modules`, `vendor`, `storage`, dan folder bawaan Laravel lainnya yang tidak penting.

```
d:\pryo\testy\
├── .env
├── .env.example
├── .editorconfig
├── .github\
│   └── copilot\
│       └── copilot-instructions.md
├── .gitignore
├── .vscode\
├── Dockerfile
├── README.md
├── artisan
├── boost.json
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── postcss.config.js
├── tailwind.config.js
├── vite.config.js
├── web.zip
├── app\
│   ├── Console\
│   │   └── Command\
│   ├── Http\
│   │   ├── Controllers\
│   │   │   ├── Admin\
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── DriverController.php
│   │   │   │   ├── MapsController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── StorageController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── VehicleController.php
│   │   │   ├── Api\
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── DriverController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── VehicleController.php
│   │   │   ├── Api.zip
│   │   │   ├── Auth\
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   ├── BookingController.php
│   │   │   ├── Controller.php
│   │   │   ├── DashboardController.php
│   │   │   ├── Driver\
│   │   │   │   └── BookingController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── Pengguna\
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── VehicleController.php
│   │   │   └── ProfileController.php
│   │   ├── Middleware\
│   │   └── Requests\
│   ├── Livewire\
│   │   ├── Actions\
│   │   ├── Forms\
│   │   └── (other files)
│   ├── Models\
│   │   ├── Booking.php
│   │   ├── Notification.php
│   │   ├── Payment.php
│   │   ├── PersonalAccessToken.php
│   │   ├── User.php
│   │   └── Vehicle.php
│   ├── Providers\
│   │   ├── AppServiceProvider.php
│   │   └── VoltServiceProvider.php
│   ├── Services\
│   │   └── BookingService.php
│   └── View\
│       └── Components\
├── bootstrap\
│   ├── app.php
│   ├── providers.php
│   ├── cache\
│   │   ├── packages.php
│   │   └── services.php
├── config\
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   └── session.php
├── database\
│   ├── .gitignore
│   ├── database.sqlite
│   ├── factories\
│   │   └── UserFactory.php
│   ├── migrations\
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_02_27_070635_add_role_role_to_user_tabl.php
│   │   └── 2026_03_04_130626_create_personal_access_tokens_table.php
│   └── seeders\
│       ├── Bookingseeder.php
│       ├── DatabaseSeeder.php
│       ├── Notificationseeder.php
│       ├── Userseeder.php
│       └── Vehicleseeder.php
├── public\
│   ├── .htaccess
│   ├── build\
│   │   └── manifest.json
│   │   └── assets\
│   ├── favicon.ico
│   ├── image\
│   ├── index.php
│   ├── robots.txt
│   └── storage
├── resources\
│   ├── css\
│   │   └── app.css
│   ├── js\
│   │   ├── app.js
│   │   ├── bootstrap.js
│   └── views\
│       ├── admin\
│       │   ├── bookings\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── dashboard.blade.php
│       │   ├── drivers\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── maps\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── payments\
│       │   │   └── index.blade.php
│       │   ├── reports\
│       │   │   └── index.blade.php
│       │   ├── storage\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── users\
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   └── vehicles\
│       │       ├── create.blade.php
│       │       ├── edit.blade.php
│       │       ├── index.blade.php
│       │       └── _form.blade.php
│       ├── auth\
│       │   ├── confirm-password.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── reset-password.blade.php
│       │   └── verify-email.blade.php
│       ├── components\
│       │   ├── action-message.blade.php
│       │   ├── application-logo.blade.php
│       │   ├── auth-session-status.blade.php
│       │   ├── danger-button.blade.php
│       │   ├── dropdown-link.blade.php
│       │   ├── dropdown.blade.php
│       │   ├── input-error.blade.php
│       │   ├── input-label.blade.php
│       │   ├── modal.blade.php
│       │   ├── nav-link.blade.php
│       │   ├── primary-button.blade.php
│       │   ├── responsive-nav-link.blade.php
│       │   ├── secondary-button.blade.php
│       │   ├── sidebar-link.blade.php
│       │   └── text-input.blade.php
│       ├── dashboard.blade.php
│       ├── driver\
│       │   ├── bookings\
│       │   │   ├── available.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   └── dashboard.blade.php
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
