# Free hosting deployment guide

This project uses a local MySQL database, uploaded portfolio images, Laravel routes, and built Vite assets. The prepared deployment target is **InfinityFree with its bundled MySQL database**. This is the simplest fit for the current PHP/Laravel application, but it is a constrained demonstration host, not a production-grade service.

## Hosting choice and current limits

InfinityFree currently advertises PHP 8.4 on its free hosting servers and provides MySQL alongside the PHP site. Its documented Laravel procedure requires building dependencies locally and uploading the project; free accounts do not provide SSH or an Artisan shell. Its own Laravel guide describes an `.htaccess` rewrite into `/public` and recommends phpMyAdmin for database import. See the [PHP 8.4 announcement](https://forum.infinityfree.com/t/free-hosting-is-now-upgraded-to-php-8-4/120906) and [Laravel installation guide](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578).

Supported free subdomains can use provider HTTPS certificates, which is necessary for browser camera access. Provider details and eligibility can change; check the current [free-subdomain HTTPS conditions](https://forum.infinityfree.com/t/changes-to-ssl-for-free-subdomains/110169) before deployment. The provider does not allow remote MySQL connections from other hosts, so keep the Laravel app and its database on InfinityFree. See its [remote MySQL guidance](https://forum.infinityfree.com/t/issue-with-remote-mysql-database-connection/100347).

Free shared hosting has CPU, inode, upload, and execution limits. It does not provide command-line deployment, cron jobs, queues, or a persistent object-storage service. Uploaded files are kept in the account's web storage; download backups regularly. This can be enough for a small demo, but it is not a durability guarantee.

Render is not native PHP hosting: Laravel needs a Docker image there. Its free web service can sleep after inactivity, and its filesystem is ephemeral; the free Postgres database expires after its stated trial period. That makes it a poorer fit for a PHP app that stores profile images locally. See [Render free services](https://render.com/docs/free) and [Render PHP hosting](https://render.com/hosting/php). A separate MySQL-compatible free database adds another provider and remote-connection configuration. For this project, the simpler same-provider option is easier to maintain.

Free plans and account eligibility are not permanent guarantees. Review the provider's live terms before moving real data.

## Before you start

- Create an InfinityFree account and a free subdomain/site.
- Create a MySQL database in the hosting control panel. Record its database host, database name, username, and password privately.
- Confirm HTTPS is enabled for the assigned hostname.
- Back up your current local database and uploaded images before changing or exporting anything.
- Never upload `.env`, database backups, logs, or local credentials into a public folder.

## Prepare a release on your computer

Run these commands from the project folder on your development computer. The PHP and Composer versions should match the deployed host's supported versions.

```powershell
composer install --no-dev --no-interaction --optimize-autoloader
pnpm install
pnpm run build
php artisan migrate
```

`php artisan migrate` applies the new additive portfolio migrations to the database selected in your local `.env`. Back up that database first. It preserves existing people and assigns each one a stable public slug. Verify the resulting schema and public records locally before exporting. If you use a separate staging database, run the migrations there instead of against your only copy.

The commands above are local. InfinityFree does not provide a shell for running them on its server. Do not use `migrate:fresh` or drop tables on a database that contains records.

## Export and import MySQL

1. Open your local database in phpMyAdmin or MySQL Workbench.
2. Export the application database after migrations have completed. Include the schema, data, and the Laravel `migrations` table. Choose SQL format.
3. In the hosting panel, open phpMyAdmin for the new hosted database.
4. Select the empty hosted database and import the SQL export.
5. Confirm that `people`, `qr_codes`, `activity_logs`, `employment_histories`, `educations`, `skills`, `portfolio_projects`, `certifications`, and `migrations` exist.
6. Keep a private copy of the export outside the website directory. Repeat the export periodically as a backup.

Do not publish a route that runs migrations. If you change the schema later, make a new local backup, apply the migration locally to the staging/export database, verify it, then export/import using the provider's database tools.

## Build and upload the application

1. In the hosting file manager or FTP client, upload the Laravel project into the site's `htdocs` directory. Upload the project root including `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`, `public`, `artisan`, and the root `.htaccess`.
2. Upload the generated `public/build` directory from `npm run build`.
3. Do not upload `node_modules`, `.git`, local logs, or `.env` from your development machine.
4. Ensure the account's PHP version is 8.4 and that the `storage` directories are writable by the PHP process. The public disk contains portfolio photos, employer logos, project thumbnails, and certificate images.
5. The root `.htaccess` included here sends web requests into `/public` and maps `/storage/...` to Laravel's public file directory. Leave Laravel's `public/.htaccess` in place. The deployment layout follows the provider's current Laravel guide; recheck that guide if the account panel changes.

The provider may limit individual uploaded file size. If FTP rejects a large file, follow its current upload guidance; do not split or rename an incomplete PHP source file and assume it is usable.

## Configure the hosted environment

Create `.env` in the Laravel project root on the host. Do not place it under `public`. Use the database values shown in the hosting panel:

```dotenv
APP_NAME="QR Manager"
APP_ENV=production
APP_KEY=base64:GENERATE_A_UNIQUE_KEY_LOCALLY
APP_DEBUG=false
APP_URL=https://your-assigned-hostname

DB_CONNECTION=mysql
DB_HOST=the-hostname-from-the-panel
DB_PORT=3306
DB_DATABASE=the-database-name
DB_USERNAME=the-database-user
DB_PASSWORD=the-database-password

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
LOG_CHANNEL=single
LOG_LEVEL=warning
```

Generate an application key on your computer with `php artisan key:generate --show`; copy the resulting value into the host `.env`. Keep it private. `APP_URL` must be the exact HTTPS hostname people will use. New QR codes store absolute URLs based on that value. Set it before generating QR codes; if the hostname changes later, regenerate the QR codes.

The existing `.env.example` contains placeholders only. Do not commit production credentials or replace the ignored local `.env` with hosted values.

## Check the live deployment

After DNS/HTTPS is ready and the files/database are uploaded:

1. Visit `https://your-assigned-hostname/` and confirm the management dashboard loads.
2. Create a test person with a short bio and one portfolio item, then save and generate a QR code.
3. Open the person's public profile from the management record. Check the visible details and image URLs.
4. Download the PNG QR and scan it using a phone's native camera. Verify the result URL uses the hosted HTTPS hostname and opens that profile.
5. Open `/scanner` over HTTPS, grant camera permission, and test camera scanning and a PNG/JPG/WEBP upload.
6. Test the front camera and any external webcam available to you. Browser and device behavior varies.
7. Confirm a nonexistent or archived profile URL returns 404.
8. Check `APP_DEBUG=false`, test that `.env` and storage internals cannot be fetched, and remove test records.
9. Keep a private database export and a copy of uploaded storage files.

## Important administration limitation

The management pages intentionally have no login because that is the requested demo behavior. Anyone who discovers the site can add, edit, export, archive, or generate QR codes for records and can upload portfolio images. CSRF protection, validation, and throttling reduce some risks, but they do not replace authentication. Do not publish personal or sensitive records on an unauthenticated deployment. This demo has no private image storage service or access-control layer for uploaded portfolio items.

## Limitations to expect

- **Storage:** Uploaded files live in the hosting account and need separate backups. No object storage or replication is configured.
- **Database:** MySQL is available with the hosted PHP app; remote connections from another host are not supported. Export/import through phpMyAdmin is the migration procedure.
- **Server execution:** No SSH/Artisan, scheduler, cron, or queue worker. The app uses file sessions/cache and synchronous work.
- **Resources:** Shared CPU, storage, and request limits can affect larger sites. Keep image sizes modest and test upload limits on the account.
- **Availability:** Free hosting conditions, account eligibility, subdomains, and HTTPS terms may change. The project has not been deployed to a live account as part of this code change.
- **Security:** The unauthenticated management interface is unsuitable for confidential data or unrestricted public use.
- **Camera scanning:** HTTPS is required by modern browsers except on localhost; the public subdomain must load with a valid certificate.
