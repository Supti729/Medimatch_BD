# Security and privacy review (B6)

Reviewed on 2026-11-25 by the project team, covering the Laravel application and the AI matching service
as of push B6. This is a self-review for an academic prototype, not an independent audit or penetration test.

## How it was checked

| Area | Method | Result |
|---|---|---|
| Secrets in git history | Searched all commits for `.env` files and real values of passwords and keys | None found; `.env` files were never committed |
| Cross-site scripting | Searched all Blade views for unescaped output (`{!! !!}`); tests post HTML and `<script>` in searches, messages and report details | None; all output is escaped |
| SQL injection | Reviewed every raw SQL fragment (`whereRaw`, `selectRaw`, `orderByRaw`, `DB::raw`) | All are fixed strings or use bound parameters; LIKE wildcards in user text are escaped |
| CSRF | Checked every `POST` form for `@csrf`; state changes use POST/PUT/PATCH/DELETE | All protected (one GET exception, see accepted risks) |
| Mass assignment | Every model uses `$fillable`/`$guarded`; owner, role, status and review fields are never fillable | Tests post `role=admin`, `status=...`, `user_id=...` and confirm they are ignored |
| Access control | Every signed-in and admin controller method checked for a policy, participant check or "own records only" query; role middleware on admin routes | Tests cover guests, other users, reviewers and admins for each area |
| Rate limiting | Log-in, registration, password reset, verification codes, listings, requests, profile updates, messages, reservations, reports, prescription uploads, announcements | All limited (listing, request and profile limits added in B6); a test checks every content-creating route |
| File uploads | Types and sizes validated; public images re-encoded; prescriptions on the private disk, served only through an audited controller | See fixes below |
| Dependencies | `composer audit`, `npm audit`, `pip-audit` | See fixes below |
| AI service | API key compared in constant time; input size limits; listens on 127.0.0.1 only; never adds listings | Tested |

## Problems found and fixed in B6

| # | Problem | Risk | Fix | Test |
|---|---|---|---|---|
| 1 | Phone photos uploaded as listing photos or profile pictures kept their **EXIF metadata, which can include the GPS position where the photo was taken** (often the donor's home). | Reveals home location, breaking the "area only" promise | Public images are re-encoded as new JPEGs (all metadata removed) and scaled to at most 1600 px (`App\Support\ImageSanitizer`) | `ImagePrivacyTest` |
| 2 | Very large images could be accepted and decoded. | Memory exhaustion ("image bomb") | Images over 8000 px on a side are refused | `ImagePrivacyTest` |
| 3 | Laravel 13 had automatically created `GET/PUT /storage/{path}` routes for the **private** disk (signed URLs only). Unused, but a web entry point to where prescriptions are stored. | Unneeded attack surface on sensitive files | `'serve' => false` on the private disk | `SecurityHeadersTest` |
| 4 | A listing of a **suspended** donor could still be requested by posting its id directly (search already hid it). | Bypass of suspension | Requests require the listing to pass the same public-visibility rules as search | `ReservationTest` |
| 5 | No security headers when not behind the example Nginx config. | Clickjacking, MIME sniffing | `SecurityHeaders` middleware: X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, a safe Content-Security-Policy | `SecurityHeadersTest` |
| 6 | Prescription documents were served without a sandbox. | A crafted PDF/image opened by a reviewer could try to run content | `Content-Security-Policy: sandbox; default-src 'none'` on document responses | `SecurityHeadersTest` |
| 7 | `setuptools 78.1.0` in the AI service environment had published advisories (PYSEC-2025-49, PYSEC-2026-3447, GHSA-xmj3-9gf7-h52w). | Known vulnerable package | Upgraded to 84.2.1; `setuptools>=84.1.0` in `requirements.txt` | `pip-audit`: no known vulnerabilities |
| 8 | The AI service's interactive API docs (`/docs`) were always on. | Information exposure on a server | `MEDIMATCH_AI_DOCS_ENABLED`, off in the example systemd unit | `tests/test_api.py` |

## Fixed earlier, found by tests during development

- **B2:** owners could have approved their own listings through the "reactivate" action. Owner actions now list the exact statuses they may start from.
- **A2:** the embedding model alone ranked Comet 500 mg (metformin) in the top 5 for "Napa 500 mg tablet". Strict identity rules now remove other medicines, strengths and forms before any AI ranking.

## Risks accepted on purpose (prototype)

| Risk | Why it is accepted | What a real service should do |
|---|---|---|
| Registration says "an account with this email already exists". | Clear feedback matters for first-time users; registration is rate-limited. Log-in and password reset do **not** reveal accounts. | Send a "you already have an account" email instead. |
| Opening a notification (`GET /notifications/{id}/open`) marks it as read. | The id is a random UUID that only its owner sees; the only effect is "read". | Mark as read with a POST from the page. |
| The password-reset link contains the email or phone number in the URL. | Needed to find phone-only accounts; the site loads no third-party content and sends a strict referrer policy. | Look the account up from the token alone. |
| Listing photos of not-yet-approved listings are reachable if someone knows the 40-character random file name. | File names cannot be guessed; photos must not show personal data (users are told so). | Serve photos through a controller that checks visibility. |
| The Content-Security-Policy does not restrict scripts. | A strict script policy needs every page tested against it; not done in this review. | Add `script-src 'self'` and remove inline handlers. |
| No real SMS or email provider. | Prototype. Codes are shown on screen only in local development. | Configure providers; never enable on-screen codes. |
| PyTorch could not be checked by `pip-audit` (CPU build comes from the PyTorch package index). | 2.15.0 is the current release. | Track PyTorch security notices. |

## Not covered by this review

- Independent penetration testing or a third-party audit.
- Load and denial-of-service testing.
- Legal compliance (DGDA rules, data protection, retention of medical documents): needed before any real use.
