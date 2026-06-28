# Render Deployment with `.env.render`

Use a file-based configuration for Render instead of typing database variables in the Render dashboard.

## Configuration flow

```
initialize.local.php   (optional — local overrides only)
        ↓
      .env file        (production — loaded by inc/load_env.php)
        ↓
initialize.production.php (InfinityFree fallback — password or full DB config)
        ↓
localhost / XAMPP      (when host is localhost and nothing above matched)
        ↓
InfinityFree defaults  (legacy host/user/db when no .env on InfinityFree)
```

| Deployment | How DB credentials are loaded |
|------------|------------------------------|
| **XAMPP local** | No `.env` → `localhost` / `root` / `cbpos_db` |
| **Render (Docker)** | `.env.render` copied to `.env` at container start |
| **InfinityFree** | No `.env` → `initialize.production.php` (password) + built-in host/db |
| **Manual production** | Create `.env` from `.env.example` |

## Exact contents of `.env.render`

This file is in the project root and is included in the Docker image:

```env
APP_ENV=production

DB_HOST=sql305.infinityfree.com
DB_NAME=if0_42288113_cbpos_db
DB_USER=if0_42288113
DB_PASSWORD=if0_42288113
```

> **Security:** `.env` is gitignored. `.env.render` is committed for Render convenience. For a public GitHub repo, rotate the database password after deploy or use a private repository.

## How the application reads `.env`

1. `config.php` loads `initialize.php`
2. `initialize.php` calls `app_load_dotenv()` from `inc/load_env.php`
3. `app_load_dotenv()` parses the project root `.env` file
4. Values are exposed via `app_env('DB_HOST')`, etc., and used to define `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`

On Render, the Docker entrypoint (`docker/entrypoint.sh`) runs before Apache:

```bash
if [ -f .env.render ] && [ ! -f .env ]; then
    cp .env.render .env
fi
```

So you do **not** need to set `DB_HOST`, `DB_USER`, etc. in the Render dashboard.

## Render setup steps

### 1. Commit and push

Ensure `.env.render` is in your repository (it is not in `.gitignore`).

```bash
git add .env.render render.yaml Dockerfile docker/
git commit -m "Configure Render deployment via .env.render"
git push
```

### 2. Create the Render service

**Option A — Blueprint**

1. Render Dashboard → **New** → **Blueprint**
2. Connect your GitHub repo
3. Render reads `render.yaml` and creates the Docker web service
4. No database env vars needed in the dashboard

**Option B — Manual**

1. **New** → **Web Service** → connect repo
2. **Runtime:** Docker
3. **Dockerfile path:** `./Dockerfile`
4. Deploy

### 3. Where to “upload” `.env.render`

Render does not have a separate env-file upload UI. The file is deployed automatically:

| Method | What to do |
|--------|------------|
| **Recommended** | Keep `.env.render` in the repo root → pushed to GitHub → included in Docker build |
| **Update credentials** | Edit `.env.render` locally → `git push` → Render redeploys |
| **Override at runtime** | Add a `.env` file in the repo (gitignored locally; use Render Shell to create `/var/www/html/.env` if needed — takes priority over auto-copy) |

### 4. Import database

Import `database/infinityfree_deploy.sql` into `if0_42288113_cbpos_db` via InfinityFree phpMyAdmin (or your MySQL host).

### 5. Verify

- Open your Render URL
- Admin login: `/admin/login.php` (`admin` / `admin123` after fresh import)
- Cashier should land on POS after login

## Switching between environments

### Localhost (XAMPP)

- Do **not** create a `.env` file in the project root (or delete it if present)
- Optional: `initialize.local.php` for custom local settings
- Open `http://localhost/cbpos/`

### InfinityFree

- Do **not** use `.env` on the server (or delete it)
- Create `initialize.production.php` with your vPanel MySQL password (see `initialize.production.php.example`)
- Upload files via FTP to `htdocs`
- See `DEPLOY_INFINITYFREE.md`

### Render

- Use `.env.render` in the repo (auto-activated as `.env` in Docker)
- Push to GitHub; Render builds and deploys the Docker image
- App connects to InfinityFree MySQL using credentials from `.env`

## Files reference

| File | Purpose |
|------|---------|
| `.env.example` | Template with placeholder password |
| `.env.render` | Production credentials for Render (copied to `.env` on start) |
| `.env` | Runtime file (gitignored; created on Render from `.env.render`) |
| `inc/load_env.php` | Parses `.env` without Composer |
| `docker/entrypoint.sh` | Copies `.env.render` → `.env` on container start |
| `render.yaml` | Render Blueprint (no dashboard DB vars) |

## Troubleshooting

**Database connection error on Render**

- Confirm `.env.render` is in the repo and not excluded by `.dockerignore`
- Check InfinityFree MySQL allows remote connections (InfinityFree may block external hosts — if blocked, use a MySQL provider that allows Render’s IPs)
- View Render logs for `Database connection error`

**Local XAMPP broken after adding `.env`**

- Remove or rename `.env` in the project root — local dev should not use production `.env`

**Wrong environment**

- Set `APP_ENV=local` in `initialize.local.php` to force local mode
