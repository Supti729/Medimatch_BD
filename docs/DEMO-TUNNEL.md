# Sharing a live demo with Cloudflare Tunnel

This puts the copy of Medimatch BD running on your own computer on a public `https://….trycloudflare.com`
link, so a supervisor or examiner can open it from anywhere. It is free, needs no account and no bank card.

- The site is online **only while your computer and both windows below are running**.
- The link is **different every time** you start the tunnel.
- This is for demos only. It is not real hosting.

## One-time setup

1. Set the project up first (see the README, or the Windows steps).
2. Install cloudflared:
   - Windows (PowerShell): `winget install --id Cloudflare.cloudflared`, then open a new PowerShell window.
   - Linux: download `cloudflared-linux-amd64` from https://github.com/cloudflare/cloudflared/releases, make it executable.
3. Check it: `cloudflared --version`

## Before every demo

1. In `.env`, set `APP_DEBUG=false` so visitors never see error details, then run:
   ```
   php artisan config:clear
   npm run build
   ```
   Do **not** run `npm run dev` while tunnelling: visitors' browsers cannot reach your local Vite server.
2. The demo accounts use the password `password`, and anyone with the link can try it.
   Log in as `admin@medimatch.test` and `reviewer@medimatch.test` and change their passwords
   under **Settings → Security → Change password** (`/settings/security`), at least for the first demo on a machine.

## Start the demo

Window 1 (project folder):
```
php artisan serve
```

Window 2:
```
cloudflared tunnel --url http://localhost:8000
```

After a few seconds window 2 prints a box with a link like `https://some-random-words.trycloudflare.com`.
Share that link. Everything works through it: log in, search, listings, messages, admin pages.

Optional: for AI-assisted search, also start the AI service in a third window (see `ai-service/README.md`).
It is only reached from your own computer, so it does not need its own tunnel.

## Stop the demo

Press `Ctrl+C` in both windows. The link stops working immediately.

## Why it works

Laravel only trusts `X-Forwarded-*` headers from a proxy on the same machine (`bootstrap/app.php`).
cloudflared runs on your computer and says the visitor used https, so Laravel builds https links and secure
cookies. Headers sent by anyone else are ignored, so visitors cannot fake their IP address to get around rate limits.
