# Deploying Medimatch BD

> **Read this first.** Medimatch BD is an academic prototype. Running it as a real service for the public
> means handling medicines, prescriptions and personal data, which requires approval under Bangladesh law
> (including the Directorate General of Drug Administration, DGDA) and a data-protection review.
> Use these notes for a **demonstration or supervised pilot server**, not for an unapproved public service.

## What runs where

| Part | Technology | Listens on |
|---|---|---|
| Web application | Laravel 13 on PHP 8.5-FPM, behind Nginx | 443 (HTTPS) via Nginx |
| Database | MySQL 8 | localhost only |
| AI matching service | Python FastAPI + uvicorn (`ai-service/`) | **127.0.0.1:8001 only** |
| Scheduled jobs | Laravel scheduler (cron) | — |

Only Nginx faces the internet. The AI service and MySQL are reachable from the server itself only.

## 1. Server preparation (Ubuntu)

```bash
sudo apt install nginx mysql-server php8.5-fpm php8.5-mysql php8.5-xml php8.5-gd php8.5-bcmath \
                 php8.5-mbstring php8.5-curl php8.5-zip php8.5-intl php8.5-sqlite3 python3-venv git unzip
# Composer: https://getcomposer.org/download/   Node.js 20+: https://nodejs.org (only needed to build assets)
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable   # do NOT open 3306 or 8001
```

## 2. Get the code

```bash
sudo mkdir -p /var/www/medimatch && sudo chown $USER:www-data /var/www/medimatch
git clone <repo-url> /var/www/medimatch && cd /var/www/medimatch
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 3. Database

Create the database and a user that can only use it (see "Database setup" in `README.md`), with a long random password.

## 4. Laravel `.env` (production values)

```bash
cp .env.example .env && php artisan key:generate
```

Then set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false                       # never true in production: it shows secrets on error pages
APP_URL=https://medimatch.example.com
DB_PASSWORD=<the database password>
SESSION_SECURE_COOKIE=true            # cookies only over HTTPS
MEDIMATCH_DEV_CODES_ON_SCREEN=false   # ignored outside local/testing anyway; keep it false
MATCHING_SERVICE_URL=http://127.0.0.1:8001
MATCHING_SERVICE_KEY=<random value, the same as MEDIMATCH_AI_API_KEY below>
MAIL_MAILER=smtp                      # plus MAIL_HOST etc. Without a mail provider, emails only go to the log
```

Generate a key value with `php -r 'echo bin2hex(random_bytes(24));'`.

## 5. Prepare the application

```bash
php artisan migrate --force
php artisan db:seed --class=LocationSeeder --force
php artisan db:seed --class=MedicineCatalogueSeeder --force   # demonstration catalogue: replace with a pharmacist-checked one
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 750 storage                                     # prescriptions are in storage/app/private
```

**Never run `php artisan db:seed` without `--class` in production.** The default seeder creates demo accounts with
the password `password`. (`DevelopmentUserSeeder` and `DemoListingSeeder` refuse to run when `APP_ENV=production`.)

## 6. The first administrator

Administrator access cannot be given from the website. Register an account normally, verify it, then on the server:

```bash
php artisan app:make-admin admin@your-organisation.org
```

Reviewers and partners are then assigned from the admin panel (Users → user → Role).

## 7. AI matching service

```bash
cd /var/www/medimatch/ai-service
python3 -m venv .venv
.venv/bin/pip install torch==2.15.0 --index-url https://download.pytorch.org/whl/cpu
.venv/bin/pip install -r requirements.txt
.venv/bin/python -m app.download_models          # ~470 MB, once
cp .env.example .env                             # set MEDIMATCH_AI_API_KEY (same value as MATCHING_SERVICE_KEY)
sudo chown -R www-data:www-data models_cache .env

sudo cp ../docs/deploy/medimatch-ai.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now medimatch-ai
cd .. && php artisan ai:status                    # should end with "OK: ..."
```

The model files are about 470 MB and are loaded into memory at start-up; after starting, check the service's actual
memory use with `systemctl status medimatch-ai` and make sure the server has room to spare. If the service is stopped
or fails, searches automatically fall back to keyword search, and the admin dashboard shows "Not reachable".

## 8. Nginx and HTTPS

```bash
sudo cp docs/deploy/nginx-medimatch.conf /etc/nginx/sites-available/medimatch   # edit the domain and paths
sudo ln -s /etc/nginx/sites-available/medimatch /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d medimatch.example.com
```

## 9. Scheduled jobs

Open requests expire after the configured number of days (`requests:expire`, daily). Add to `sudo crontab -u www-data -e`:

```cron
* * * * * cd /var/www/medimatch && php artisan schedule:run >> /dev/null 2>&1
```

Notifications are sent straight away; no queue worker is needed.

## 10. Updating

```bash
cd /var/www/medimatch && php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan ai:export-catalogue && sudo systemctl restart medimatch-ai   # if the catalogue changed
php artisan up
```

## 11. Backups and data

- Back up the database (`mysqldump`) and `storage/app` (photos and **private prescriptions**) daily, encrypted, kept off the server.
- Prescriptions are personal health data. Decide a retention period and delete documents that are no longer needed.
- Audit logs cannot be changed from the application; keep database access to a minimum.

## Checklist before showing it to anyone

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, HTTPS working, HTTP redirects to HTTPS
- [ ] No demo accounts exist (`admin@medimatch.test` etc.)
- [ ] Ports 3306 and 8001 are not reachable from outside (`nmap <server>` from another machine)
- [ ] `php artisan ai:status` says OK
- [ ] The catalogue has been checked by a pharmacist, and the legal status of the service is clear
