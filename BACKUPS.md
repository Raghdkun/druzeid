# Database backups

The site backs up its **database and uploaded media** into a single ZIP archive.
Backups can be run from the dashboard (**النسخ الاحتياطي**, admins only) or on a
weekly schedule. This guide covers configuration and the Hostinger cron job that
drives the schedule.

## What a backup contains

Each archive is `backup-YYYY-MM-DD_HHMMSS.zip` and holds:

- `database.sql` — a full dump of the live database (schema + data).
- `media/…` — everything under the public media folder (`storage/app/public/media`).

The dump is produced in **pure PHP** (`ifsnop/mysqldump-php`) so it works on
Hostinger shared hosting with no `mysqldump` binary. No server shell tools are
required.

## Where backups live

Archives are written to a **private disk** at `storage/app/backups`. This path is
never web-served — an archive can only be reached through the admin-gated
download route (`/cms/backups/{name}/download`), never a guessable public URL.

> Make sure your web root points at `public/`, so `storage/` sits **outside** the
> document root. On Hostinger this is the default for a Laravel deployment.

## Configuration (`.env`)

| Variable | Default | Purpose |
|---|---|---|
| `BACKUP_ARCHIVE_PASSWORD` | *(empty)* | When set, every archive is **AES-256 encrypted** with this password. Required to open or restore it. |
| `BACKUP_KEEP` | `3` | How many archives to retain. Older ones are pruned after each successful backup. |
| `BACKUP_INCLUDE_MEDIA` | `true` | Include uploaded media in the archive. |

Set a strong password so the archives are encrypted at rest:

```bash
BACKUP_ARCHIVE_PASSWORD=change-me-to-a-long-random-passphrase
BACKUP_KEEP=3
```

> **Keep this password somewhere other than the database and other than the
> backups themselves** (a password manager). It lives in the environment on
> purpose, so an archive stays recoverable even if the database is lost. If you
> lose the password, the encrypted archives cannot be opened.

Run `php artisan config:cache` after editing `.env`.

Until a password is set, the dashboard shows a warning and archives are created
**unencrypted**.

## Running a backup manually

Dashboard: **النسخ الاحتياطي → إنشاء نسخة الآن**. Or from the CLI:

```bash
php artisan backup:run
```

The command prints the archive name and whether it was encrypted.

## Weekly schedule

The Laravel scheduler is configured (in `routes/console.php`) to run a backup
every **Sunday at 03:00**, keeping only the newest `BACKUP_KEEP` archives:

```php
Schedule::command('backup:run')->weeklyOn(0, '03:00')->withoutOverlapping();
```

The scheduler only fires if `schedule:run` is invoked every minute. On shared
hosting that means one cron job.

## Hostinger cron job

The scheduler needs a single cron entry that runs **every minute**; Laravel
decides internally when the weekly backup is actually due.

1. Log in to **hPanel**.
2. Go to **Advanced → Cron Jobs**.
3. Add a new cron job:
   - **Type / Command to run**: *Custom* (enter the command manually).
   - **Schedule**: every minute — `* * * * *`. In the "Common Settings"
     dropdown this is **"Every Minute"**; or enter the fields manually:
     minute `*`, hour `*`, day `*`, month `*`, weekday `*`.
   - **Command**:

     ```bash
     /usr/bin/php /home/uXXXXXXXX/domains/druze.id/public_html/artisan schedule:run >> /dev/null 2>&1
     ```

   Replace the PHP path and project path with your account's real values:
   - Find the PHP binary path in hPanel under **Advanced → PHP Configuration**
     (often `/usr/bin/php` or a versioned path like `/usr/bin/php8.4`).
   - The project path is the folder that contains `artisan` — on Hostinger this
     is usually `~/domains/<your-domain>/public_html` (or `.../private` if you
     deployed the app root outside `public_html`). Use the absolute path shown in
     the **File Manager**.

4. Save. That single job powers the weekly backup (and any future scheduled
   tasks).

### Verify the cron is working

From an SSH session (or hPanel's **Browser terminal**), run the scheduler once
and confirm it reports the backup as scheduled:

```bash
php artisan schedule:list
```

Then force a backup immediately to confirm the whole pipeline works end to end:

```bash
php artisan backup:run
```

Open the dashboard — the new archive should appear in **النسخ الاحتياطي**.

## Restoring from a backup

1. Download the archive from the dashboard (or copy it off `storage/app/backups`).
2. Unzip it with the password:

   ```bash
   unzip -P 'your-backup-password' backup-YYYY-MM-DD_HHMMSS.zip -d restore/
   ```

3. Import the database dump:

   ```bash
   mysql -u <user> -p <database> < restore/database.sql
   ```

   (Or import `database.sql` through phpMyAdmin in hPanel.)

4. Copy `restore/media/…` back into `storage/app/public/media`.
5. `php artisan optimize:clear` to flush caches.

## Security notes

- Archives are AES-256 encrypted when `BACKUP_ARCHIVE_PASSWORD` is set.
- The `backups` disk is private (`storage/app/backups`), never under the web root.
- Downloads are admin-only and streamed through the app; the filename is
  validated against a strict pattern, so path-traversal names are rejected.
- Retention prunes to `BACKUP_KEEP`, so storage never grows unbounded.
