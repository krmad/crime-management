# Crime Management Operations

A role-based internal workspace for recording crime cases, linking suspects and victims, managing evidence custody, and producing operational reports.

## Overview

Crime Management Operations is a Laravel web application for authorized personnel. It provides a role-scoped case workflow from report creation through investigation, judgment, closure, evidence handling, and reporting.

### Key features

- **Dashboard** with role-scoped case statistics and recent activity.
- **Case management** with hierarchical classification, assignment, investigation notes, judgment recording, and closure.
- **People management** for suspects and victims, including case links, relationship status, and notes.
- **Evidence management** with private file upload/download, reference numbers, item details, and a custody log for transfers, releases, and disposal.
- **Reports** with date, status, and classification filters, summary counts, outcomes, investigator workload, and filtered CSV export.
- **User and officer administration** with account activation/deactivation, role assignment, and officer profiles.
- **Account management** through Laravel Breeze: login, password reset, email verification, password confirmation, and profile updates.
- **Consistent branding and dark mode** using a shared inline shield-and-checkmark logo, a configurable application name, and a saved light/dark preference.

There is no public registration route; users are created by an administrator.

## Technology stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ (tested with PHP 8.4) |
| Authentication | Laravel Breeze |
| Frontend | Blade, Tailwind CSS 4, Alpine.js 3, Vite 8 |
| Testing | Pest 5, PHPUnit |
| Formatting | Laravel Pint |
| Default database | SQLite |
| Development tooling | Laravel Boost, Laravel Pail |

The application is configured for SQLite by default, but Laravel's MySQL, MariaDB, PostgreSQL, and SQL Server connections remain available in `config/database.php`.

## Requirements

- PHP 8.3 or newer with the SQLite PDO extension
- Composer 2
- Node.js 20.19+ or 22.12+ (required by Vite 8), with npm
- Git

The checked-in test suite uses an in-memory SQLite database configured in `phpunit.xml`, so tests do not require a separate database server.

## macOS installation

Install the runtime dependencies with Homebrew:

```bash
brew install php@8.4 composer node
```

Clone the repository and install the application:

```bash
git clone <repository-url> crime-management
cd crime-management
composer setup
php artisan db:seed
composer run dev
```

`composer setup` installs PHP and npm dependencies, copies `.env.example` to `.env` when needed, generates the application key, runs migrations, and builds frontend assets. `php artisan db:seed` creates the crime categories and a local developer user.

If you prefer to run each service manually:

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
composer install
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

In another terminal, run Vite for frontend development:

```bash
npm run dev
```

The default local URL is `http://localhost:8000`.

## Environment setup

Key settings in `.env`:

- `APP_NAME` and `APP_BRAND_NAME`: set to `Crime Management Operations` in `.env.example`; the visible branding uses `APP_BRAND_NAME` with a full-name fallback.
- `APP_URL`: defaults to `http://localhost:8000`.
- `DB_CONNECTION`: defaults to `sqlite`; the SQLite file defaults to `database/database.sqlite`.
- `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION`: default to `database`, so migrations are required before login.
- `FILESYSTEM_DISK`: defaults to `local`; evidence files are stored privately and downloaded through authorized routes.
- `MAIL_MAILER`: defaults to `log` for local development.

To use MySQL instead of SQLite, create the database and update the `DB_*` values in `.env` before running migrations:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crime_management
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Then run:

```bash
php artisan migrate --seed
```

## Roles and permissions

Roles are defined in `app/Enums/UserRole.php` and enforced through Gates in `app/Providers/AppServiceProvider.php` and policies in `app/Policies/`.

| Capability | Administrator | Investigator | Police officer |
| --- | :---: | :---: | :---: |
| View all cases | Yes | No | No |
| View assigned/created cases | Yes | Assigned only | Created only |
| Open new cases | Yes | No | Yes |
| Save investigation notes | Yes | Yes | No |
| Assign investigators | Yes | No | No |
| Record judgment | Yes | No | No |
| Close cases | Yes | No | No |
| Link/manage people and evidence on open cases | Yes | Assigned cases | Created cases |
| Release/dispose evidence | Yes | No | No |
| Create standalone suspects/victims | Yes | No | No |
| Manage users and officer profiles | Yes | No | No |

Inactive users cannot log in. Administrators cannot deactivate or demote the last active administrator.

The seeder creates `test@example.com` with password `password` and the default `police_officer` role. It does not create an administrator. After seeding, promote at least one user before using administrator-only features:

```bash
php artisan tinker --execute 'App\Models\User::where("email", "test@example.com")->update(["role" => "administrator"]);'
```

## Running checks

Run the complete test suite:

```bash
composer test
```

Equivalent direct commands:

```bash
php artisan test
vendor/bin/pest
```

Run a focused test file:

```bash
php artisan test --compact tests/Feature/ApplicationBrandingAndThemeTest.php
```

Format and check PHP code:

```bash
vendor/bin/pint
vendor/bin/pint --test
```

Build production assets:

```bash
npm run build
```

If a page reports a missing Vite manifest, run `npm run build` or start `npm run dev`.

## Project structure

- `app/Enums/`: role and domain status enums.
- `app/Http/Controllers/`: application and authentication controllers.
- `app/Http/Requests/`: validation and authorization rules.
- `app/Policies/`: case, evidence, suspect, and victim authorization.
- `resources/views/components/`: reusable UI components, logo, theme toggle, and status badges.
- `routes/web.php`: all application routes.
- `tests/Feature/`: Pest feature coverage for workflows, permissions, validation, branding, and theme behavior.

## License

This project is licensed under the MIT license, as declared in `composer.json`.
