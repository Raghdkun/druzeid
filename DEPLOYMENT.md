# Production deployment checklist

Security review verdict: no Critical/High issues. The items below are the
deployment-hygiene and hardening steps that must be in place before launch.

## 1. Environment (`.env`)

| Variable | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | Enables the strong password policy and `DB::prohibitDestructiveCommands()`. |
| `APP_DEBUG` | `false` | Stops stack traces / environment detail leaking on errors. |
| `APP_URL` | `https://druze.id` | Correct canonical, sitemap and OG URLs. |
| `SESSION_SECURE_COOKIE` | `true` | Marks the session cookie `Secure` so it never travels over plain HTTP. |
| `SESSION_DOMAIN` | your domain | Scopes the cookie. |

Staging is treated as a real deployment: with `APP_ENV=staging` the strong
password policy (12+ chars, mixed case, digits, symbols) still applies; only
`local`/`testing` relax it. The breach-database (`uncompromised`) check runs on
`production` only.

Run `php artisan config:cache` after editing `.env` so changes take effect.

## 2. HTTPS / headers

- Terminate TLS at the edge. Over HTTPS the app adds `Strict-Transport-Security`
  and `upgrade-insecure-requests` automatically; over plain HTTP it does not (so
  local dev keeps working).
- The Content-Security-Policy is nonce-based (`script-src 'self' 'nonce-…'`, no
  `'unsafe-inline'`), set by `App\Http\Middleware\SecurityHeaders`.

## 3. Serving `/storage` media

Uploaded media is served directly by the web server, bypassing the app's CSP.
Add `X-Content-Type-Options: nosniff` for that path and never allow execution:

```nginx
location /storage/ {
    add_header X-Content-Type-Options "nosniff" always;
    # Static files only — no PHP handler in this location.
}
```

SVG upload is disabled in the CMS (it is script-carrying markup); only raster
images (jpeg/png/gif/webp) and video (mp4/webm) are accepted.

## 4. FFmpeg (video)

Video uploads are probed for dimensions/duration and get a poster frame via
ffmpeg/ffprobe. Install them and point the app at the binaries:

```
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe
```

If ffmpeg is absent, video still uploads — it just has no stored dimensions or
poster (the player falls back to the first frame). Ensure PHP
`upload_max_filesize` / `post_max_size` exceed `MEDIA_VIDEO_MAX_KB` (default
100 MB).

## 5. Storage & build

```
php artisan storage:link
php artisan migrate --force
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
