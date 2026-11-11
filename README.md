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
| Database | MySQL 8 (SQLite for local quick-start and tests) |
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

Requirements: PHP 8.3+ with `pdo_mysql`, `pdo_sqlite`, `xml`, `gd`, `bcmath`; Composer 2; Node.js 20+.

```bash
git clone <repo-url> MedimatchBD
cd MedimatchBD

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # default local DB until MySQL is configured (step 2.1)
php artisan migrate

composer run dev                 # starts Laravel + Vite together
```

Then open http://localhost:8000.

Run tests:

```bash
php artisan test
```

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
- [ ] F4 Admin pages and responsive pass

### Frontend building blocks

| What | Where |
|---|---|
| Design tokens (colours, fonts, shadows) | `resources/css/app.css` |
| Layouts | `resources/views/components/layouts/` — use as `<x-layouts.public>`, `<x-layouts.app>`, `<x-layouts.admin>`, `<x-layouts.auth>` |
| Components (buttons, forms, cards, badges, modal…) | `resources/views/components/` — each file starts with a usage example |
| Public pages | `resources/views/pages/`, `resources/views/medicines/`, `resources/views/auth/` |
| Signed-in pages | `resources/views/account/`, controllers in `app/Http/Controllers/Account/` |
| Sample data (temporary, replaced in Part 2) | `app/Support/SampleListings.php`, `app/Support/SampleAccount.php` |
| Divisions, districts and option lists | `config/bangladesh.php`, `config/medimatch.php` |
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
