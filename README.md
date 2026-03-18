# UPHMC-SMS

UPHMC-SMS is a Laravel + Inertia + Vue 3 application for account management, phonebook/group management, SMS sending, realtime SMS status updates, audit trails, and role-based access control.

## Stack

- Laravel 10
- Inertia.js
- Vue 3
- PrimeVue
- ApexCharts
- Socket.IO
- Redis
- PostgreSQL
- Laratrust

## Modules

- Dashboard
- Account
- Role
- Department
- Position
- Profile
- Phonebook
- Group
- SMS
- Audit Trail

## Realtime Flow

SMS status updates use this path:

`Laravel -> Redis pub/sub -> Node Socket.IO server -> browser`

Relevant processes:

- Laravel app
- Vite frontend
- Node socket server
- Laravel queue worker

## Requirements

- PHP 8.1+
- Composer
- Node.js + npm
- PostgreSQL
- Redis

## Installation

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the app key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Seed base data:

```bash
php artisan db:seed
```

## Local Development

Run the Laravel app:

```bash
php artisan serve
```

Run Vite:

```bash
npm run dev
```

Run the Socket.IO server:

```bash
npm run socket
```

Run the SMS queue worker:

```bash
php artisan queue:work --queue=sms --sleep=3 --tries=3
```

Recommended single-worker SMS variant:

```bash
php artisan queue:work --queue=sms --sleep=3 --tries=3 --max-jobs=50
```

## Environment Notes

Important values to review in `.env`:

- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `QUEUE_CONNECTION`
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_PREFIX`
- `SOCKET_HOST`, `SOCKET_PORT`
- `VITE_SOCKET_URL` or `VITE_SOCKET_PORT`

If you want queued jobs to run asynchronously, do not leave:

```env
QUEUE_CONNECTION=sync
```

Use `database` or `redis` instead.

## Seeding

Current seeders include:

- roles and permissions
- departments
- positions
- default accounts
- SMS gateways

Run a specific seeder:

```bash
php artisan db:seed --class=DepartmentsTableSeeder
php artisan db:seed --class=PositionsTableSeeder
```

## Common Commands

Rebuild frontend assets:

```bash
npm run build
```

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Check failed jobs:

```bash
php artisan queue:failed
```

Retry failed jobs:

```bash
php artisan queue:retry all
```

## Access Control

Access is controlled in two layers:

- frontend menu/page restriction in the admin layout
- backend route restriction through middleware and permissions

Laratrust is used for roles and permissions.

## License

This project is licensed under the MIT License. See [LICENSE](/Users/ferd/Sites/uphmc-sms/LICENSE).
