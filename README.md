# Medimatch BD

A privacy-focused, location-based web application that helps people in Bangladesh find eligible medicines and connect with donors or authorized affordable-access programs, with AI-assisted matching between requests and listings.

> **Academic prototype.** Medimatch BD is a Software Engineering Capstone Project. It is **not** an authorized medicine distribution service. Medicine donation, redistribution, verification and delivery in a real deployment must follow applicable Bangladesh regulations.

## Team

| Member | Role |
|---|---|
| Supti Saha (Team Leader) | Requirements, UI/UX, frontend, documentation |
| Atiar Rahman Rahat | Laravel backend, MySQL database, authentication, listings, requests, search |
| Emamul Muttakin | AI semantic matching, verification, admin/reporting, security testing |

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Blade templates, Tailwind CSS v4, Vite |
| Backend | Laravel 13 (PHP 8.5) |
| Database | MySQL 8 (in-memory SQLite for automated tests) |
| AI service | Python, FastAPI, Sentence Transformers *(Part 3)* |

## Repository Layout

```
app/            Laravel application code (controllers, models, policies, services)
resources/      Blade views, CSS, JS
routes/         Web routes
database/       Migrations, seeders, factories
tests/          Automated tests
ai-service/     Python FastAPI matching service (added in Part 3)
```

## Getting Started (local development)

Requirements: PHP 8.3+ with `pdo_mysql`, `pdo_sqlite`, `xml`, `gd`, `bcmath`; Composer 2; Node.js 20+; MySQL 8.

```bash
git clone <repo-url> MedimatchBD
cd MedimatchBD

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Database setup (MySQL)

1. In `.env`, set `DB_PASSWORD` to a password of your choice (any long random string).
2. From the project folder, create the database and a user that can only access it. The command reads the password from `.env`, so the two always match:

```bash
cd MedimatchBD                                   # must run inside the project folder
PW=$(grep '^DB_PASSWORD=' .env | cut -d= -f2)
sudo mysql -e "
  CREATE DATABASE IF NOT EXISTS medimatch_bd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'medimatch'@'localhost' IDENTIFIED BY '$PW';
  ALTER USER 'medimatch'@'localhost' IDENTIFIED BY '$PW';
  GRANT ALL PRIVILEGES ON medimatch_bd.* TO 'medimatch'@'localhost';
  FLUSH PRIVILEGES;"
```

No output means it worked. Check the login (prints `1`):

```bash
mysql -u medimatch --password="$PW" -e "select 1" medimatch_bd
```

Then create the tables and load the Bangladesh locations and demo accounts:

```bash
php artisan migrate --seed
php artisan storage:link        # makes uploaded profile pictures viewable
composer run dev                # starts Laravel + Vite together
```

Open http://localhost:8000.

### Demo accounts (local development only)

`php artisan migrate --seed` creates these accounts. The password for all of them is `password`.
The seeder refuses to run in production.

| Log in with | Role |
|---|---|
| `admin@medimatch.test` | Administrator |
| `reviewer@medimatch.test` | Authorized reviewer (verification pages only) |
| `user@medimatch.test` | Regular user |
| `01700000001` | Regular user who signed up with a phone number |
| `donora@medimatch.test`, `donorb@…`, `donorc@…` | Demo donors with approved demo listings |

### Verification codes and password resets in development

No SMS provider is configured, and email is written to `storage/logs/laravel.log` instead of being sent.
With `MEDIMATCH_DEV_CODES_ON_SCREEN=true` in `.env`, verification codes and reset links are shown on screen,
clearly labelled as development codes. This setting is ignored outside the `local` and `testing` environments.

### Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so they do not touch your MySQL data.

## Development Roadmap

The project is built in three parts, each split into small commits.

- **Part 1 — Frontend:** design system, layouts, components, all public / user / admin pages (static data, clearly labelled).
- **Part 2 — Backend:** database, authentication (email or phone), roles, listings, requests, search, messaging, notifications, verification, admin.
- **Part 3 — AI integration:** Python semantic matching service, evaluation (Precision@5, Recall@5, MRR), Laravel integration.

### Progress

- [x] 1.1 Project setup (Laravel 13, Tailwind v4, Vite)
- [x] 1.2 Design system and tokens (preview at `/styleguide` in local development)
- [x] F1 Layouts (public, auth, user, admin) and shared UI components
- [x] F2 Public pages (home, browse, details, information, legal) and login/registration screens — browse runs on labelled sample data; forms are not connected until Part 2
- [x] F3 Signed-in user pages (dashboard, profile, listings, requests, saved, messages, notifications, history, verification, settings) — design previews on a labelled sample account, open at `/dashboard`
- [x] F4 Admin pages (dashboard with charts, users, medicines, listings, requests, verification, reports, transactions, partners, announcements, audit log, settings) and responsive pass — design previews on labelled sample data, open at `/admin`

**Part 1 (frontend) is complete.** Every page exists and is checked at 390px (phone) and 768px (tablet) with no sideways overflow, and every internal link resolves.
- [x] B1 MySQL, Bangladesh locations (8 divisions, 64 districts, 495 upazilas), registration and login with email or phone, development-mode verification codes, password reset and change, roles (user, partner, reviewer, admin) and protected routes
- [x] B2 Profiles (picture, area), privacy settings, public profiles, contact-detail changes with re-verification; medicine catalogue (generic + strength + form, brand names, restricted medicines); real listings with eligibility rules, photos and a controlled status flow
- [x] B3 Medicine requests (create, edit, cancel, automatic expiry), rule-based matching, real browse and search, reservations (request → accept/decline → handover), saved medicines, real dashboard, history and home-page counts
- [x] B4 Private messaging between donor and requester (opened by a reservation, participants only, read-only after the handover, no phone numbers or emails, 15-second auto-refresh) and in-app notifications for requests, answers, cancellations, completed handovers, new messages and new matches

Signed-in and admin pages require login (admin pages require the administrator role; reviewers may open the verification pages). Browse, search, listings, requests, reservations, saved medicines, history, the dashboard and profiles use real data. Messages and notifications are real. The prescription upload and most admin pages (B5) still show labelled sample data. New listings wait in "Pending review" until moderation is added in B5; for demos, `php artisan migrate --seed` creates approved demo listings from three "Demo donor" accounts.

### Backend building blocks

| What | Where |
|---|---|
| Account rules (who can log in, roles, verification) | `app/Models/User.php`, `app/Enums/Role.php` |
| Sign-up, log-in, verification, password reset | `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/` |
| Bangladeshi phone numbers (`+880…`, `01…`) | `app/Support/PhoneNumber.php` |
| Verification codes (hashed, expiring, limited attempts) | `app/Services/VerificationCodeService.php` |
| Route protection (`role:admin`, `active`, `verified.account`) | `app/Http/Middleware/`, `routes/web.php` |
| Bangladesh locations | `database/data/bangladesh-locations.json`, `database/seeders/LocationSeeder.php` |
| Medicine catalogue (demo data, must be checked against the DGDA list) | `app/Models/Medicine.php`, `database/seeders/MedicineCatalogueSeeder.php` |
| Listing status rules (allowed transitions) | `app/Enums/ListingStatus.php`, `app/Enums/ListingAction.php` |
| Listing eligibility rules (shelf life, restricted medicines, photo limit) | `app/Http/Requests/Account/ListingRequest.php`, `config/medimatch.php` (`listing_rules`) |
| Who may edit or change a listing | `app/Policies/MedicineListingPolicy.php` |
| Matching (same medicine, public, nearest first) | `app/Services/MatchingService.php` |
| Reservation rules (request, accept, decline, cancel, complete) | `app/Services/ReservationService.php` |
| Request expiry (daily) | `app/Console/Commands/ExpireRequests.php`, scheduled in `routes/console.php` |
| Messaging rules (who can write, what is blocked) | `app/Services/MessagingService.php`, `app/Support/ContactDetails.php` |
| Notifications (database only, no email or SMS) | `app/Notifications/`, sent from `ReservationService`, `MessagingService` and `app/Listeners/NotifyMatchingRequesters.php` |

### Frontend building blocks

| What | Where |
|---|---|
| Design tokens (colours, fonts, shadows) | `resources/css/app.css` |
| Layouts | `resources/views/components/layouts/` — use as `<x-layouts.public>`, `<x-layouts.app>`, `<x-layouts.admin>`, `<x-layouts.auth>` |
| Components (buttons, forms, cards, badges, modal…) | `resources/views/components/` — each file starts with a usage example |
| Public pages | `resources/views/pages/`, `resources/views/medicines/`, `resources/views/auth/` |
| Signed-in pages | `resources/views/account/`, controllers in `app/Http/Controllers/Account/` |
| Admin pages | `resources/views/admin/`, controller in `app/Http/Controllers/Admin/` |
| Charts (plain HTML/CSS, each with a table view) | `resources/views/components/chart/` |
| Sample data (temporary, replaced in Part 2) | `app/Support/SampleListings.php`, `app/Support/SampleAccount.php`, `app/Support/SampleAdmin.php` |
| Divisions and districts used by the browse filters (until B3), option lists | `config/bangladesh.php`, `config/medimatch.php` |
| Menu links | `config/navigation.php` — a link shows as "Soon" until its route exists |
| Interface behaviour (menus, dropdowns, modals) | `resources/js/app.js` |
| Icons | [Heroicons](https://heroicons.com) through `<x-icon name="bell" />` |

Live previews (local development only): `/styleguide`, `/styleguide/components`, and the layout previews linked from there.

## Contributing (team workflow)

Each member commits with their own GitHub identity:

```bash
git config user.name "Your Name"
git config user.email "your-github-email@example.com"
```

Use a feature branch per step (e.g. `feat/1.2-design-system`) and merge into `main` through a pull request.

## License

Academic project. All rights reserved by the project team unless stated otherwise.

Location data in `database/data/bangladesh-locations.json` is derived from [nuhil/bangladesh-geocode](https://github.com/nuhil/bangladesh-geocode) (MIT License, Copyright (c) 2014 Nuhil Mehdy).
