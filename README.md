# BugTracker

[![CI](https://github.com/GHYounesse/BugTracker/actions/workflows/ci.yml/badge.svg)](https://github.com/GHYounesse/BugTracker/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-6.0-000000)](composer.json)

A self-hosted bug/issue tracker built with Symfony. Report bugs, triage them through a status workflow, assign them to teammates, discuss them in threaded comments, and organize everything by project and category — with a partial REST API alongside the web UI.

## Screenshots

> Drop PNGs into `docs/screenshots/` with the filenames below and they'll show up here automatically — nothing else to change.

| Login | Dashboard |
|---|---|
| `docs/screenshots/login.png` | `docs/screenshots/dashboard.png` |

| Issue detail | Categories |
|---|---|
| `docs/screenshots/issue-detail.png` | `docs/screenshots/categories.png` |

## Features

- **Issue lifecycle** — create, view, and edit issues with status (`new` → `confirmed` → `assigned` → `processed` → `closed`), priority, severity, and file attachments
- **Threaded comments** on each issue
- **Projects & Categories** — full CRUD for both, used to organize issues (Projects use in-page modals since they're a single-field resource; Categories get dedicated pages)
- **Dashboard** — issues grouped by status at a glance
- **Authentication** — registration with password confirmation, login with brute-force throttling (5 attempts), CSRF-protected logout, role-based access control (`ROLE_USER` / `ROLE_ADMIN`)
- **Admin user management** — list, view, and delete accounts (admin-only)
- **Partial REST API** via [API Platform](https://api-platform.com/) for `Issue` and `Project` resources (`/api`)

## Tech stack

- **Backend**: PHP 8.2+, [Symfony 6](https://symfony.com/), Doctrine ORM, Doctrine Migrations
- **API**: API Platform
- **Frontend**: Twig, Bootstrap 5 (dark theme, no build step / no Node dependency)
- **Database**: PostgreSQL 15
- **Auth**: Symfony Security (custom form authenticator, login throttling, CSRF)

## Getting started

### Prerequisites

- PHP 8.2+ with the `ctype`, `iconv`, and `pdo_pgsql` extensions
- [Composer](https://getcomposer.org/)
- [Docker](https://www.docker.com/) (for PostgreSQL via `docker-compose`) — or a PostgreSQL 15 instance of your own

### Setup

```bash
git clone https://github.com/GHYounesse/BugTracker.git
cd BugTracker
composer install
cp .env.example .env
docker compose up -d
php bin/console doctrine:migrations:migrate --no-interaction
```

If your database password contains special characters (`$`, `@`, `%`, etc.), see the note at the bottom of this section before editing `DATABASE_URL` — it's a real gotcha with Symfony's `.env` handling.

### Run it

```bash
symfony server:start
# or, without the Symfony CLI:
php -S 127.0.0.1:8000 -t public
```

Visit `http://127.0.0.1:8000`, register an account, and log in.

### A note on special characters in `DATABASE_URL`

Symfony's `.env` loader treats `$` as the start of a variable reference, and `doctrine.yaml` resolves `%...%` patterns in the DSN as container parameters. If your DB password has either character, percent-encode it (`rawurlencode()`) **and then double every `%`** in the result before pasting it into `DATABASE_URL`, e.g. a password containing `$` encodes to `%24`, which becomes `%%24` in `.env`. Skipping the doubling step produces a cryptic "The parameter \"24\" must be defined" error.

## Project structure

```
src/
  Controller/     # AuthController, IssueController, CategoryController, ProjectController, UserController
  Entity/         # User, Issue, Comment, Project, Category
  Form/           # IssueType, CommentType, CategoryType, ProjectType, RegistrationFormType
  Repository/     # Doctrine repositories
  Security/       # LoginFormAuthenticator
templates/
  issue/, category/, project/, user/, login/, registration/
  partials/       # shared navbar
migrations/       # Doctrine migrations (schema history)
```

## Running checks locally

```bash
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
composer validate --strict
```

These are exactly what CI runs on every push — see [`.github/workflows/ci.yml`](.github/workflows/ci.yml). There's no automated test suite yet (see Roadmap).

## Roadmap

Things I know are missing and would tackle next, in rough priority order:

- [ ] PHPUnit test suite (currently zero automated tests)
- [ ] Static analysis (PHPStan) and a code style fixer
- [ ] Bump Symfony 6.0 → 6.4 LTS and PHP to a currently-supported version
- [ ] Style the remaining unstyled pages (user list/show, issue creation form)
- [ ] Live demo deployment

## License

MIT — see [LICENSE](LICENSE).
