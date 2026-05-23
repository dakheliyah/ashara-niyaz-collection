# Ashara Niyaz Collection System

Laravel + Vue application for role-based donation and collection management, with ITS OneLogin handoff integration.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL (or compatible)

## First-Time Setup

1) Install dependencies:

```bash
composer install
npm install
```

2) Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3) Configure `.env`:

- `APP_URL`
- `DB_*` values
- `ITS_ENCRYPTION_KEY` (required for ITS token encryption/decryption)
- `ITS_ONELOGIN_HANDOFF_URL` (WordPress handoff endpoint, no `?token=`)
- `VITE_AUTH_RELAY_URL` (WordPress/relay login origin)

4) Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

The default `db:seed` runs:

- `DonationTypeSeeder`
- `CurrencySeeder`
- `RoleSeeder`
- `AdminSeeder`

You can run an individual seeder with:

```bash
php artisan db:seed --class=RoleSeeder
```

## Seeder Notes (Important for Login)

- `RoleSeeder` creates `admin` and `collector` roles.
- `AdminSeeder` provisions default users in `admins` table:
  - ITS `30361114` (admin)
  - ITS `20324216` (collector)

If users are not present in `admins`/`mumineen`, authentication may fail with `401 User not found`.

## Running the App

Start backend and frontend:

```bash
php artisan serve
npm run dev
```

Build frontend for production:

```bash
npm run build
```

## Auth Troubleshooting

If users are redirected back to relay/base URL repeatedly:

1) Confirm seeders/user provisioning has been done (`RoleSeeder`, `AdminSeeder`, or manual user creation).
2) Confirm cookies are being sent to backend (HttpOnly cookies will not appear in `document.cookie`).
3) Enable auth debug logs:

```env
ITS_AUTH_DEBUG=true
```

Then clear config cache:

```bash
php artisan config:clear
```

Check `storage/logs/laravel.log` for `ITS auth` entries.
