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
- API Clients

## Realtime Flow

SMS status updates use this path:

`Laravel -> Redis pub/sub -> Node Socket.IO server -> browser`

Relevant processes:

- Laravel app
- Vite frontend
- Node socket server
- Laravel queue worker
- Python SMS sender on Windows

## Requirements

- PHP 8.1+
- Composer
- Node.js + npm
- Python 3
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

Set up the Windows modem sender:

```bash
php artisan sms:setup-modem
```

Generate the app key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
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

On Windows, SMS sending uses [scripts/sms/sms_send.py](c:\xampp\htdocs\uphmc-sms\scripts\sms\sms_send.py) via `pyserial` instead of PHP serial streams.

## SMS API

The project includes an admin-managed SMS API client module for external systems.

Admin flow:

1. Open the `API Clients` module.
2. Create an API client.
3. Copy the generated bearer token immediately.
4. Use that token in your external system.

Bearer token format:

```text
Authorization: Bearer CLIENTKEY.RAWSECRET
```

Required headers:

```text
Accept: application/json
Content-Type: application/json
```

Send SMS:

```bash
curl -X POST http://your-app.test/api/v1/sms/send \
  -H "Authorization: Bearer CLIENTKEY.RAWSECRET" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "body": "Hello from API",
    "recipients": [
      { "type": "number", "value": "09171234567" }
    ],
    "send_type": "immediate"
  }'
```

Supported recipient types:

- `number`
- `contact`
- `group`

Check SMS status:

```bash
curl -X GET http://your-app.test/api/v1/sms/123/status \
  -H "Authorization: Bearer CLIENTKEY.RAWSECRET" \
  -H "Accept: application/json"
```

Sample API response:

```json
{
  "status": 1,
  "message": "SMS queued successfully.",
  "data": {
    "message_id": 123,
    "source": "api",
    "scheduled": false,
    "recipients": 1
  }
}
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

This project is licensed under the MIT License. See [LICENSE](c:\xampp\htdocs\uphmc-sms\LICENSE).
