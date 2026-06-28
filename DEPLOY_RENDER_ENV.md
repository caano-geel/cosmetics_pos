# Render Deployment with `.env.render` (Aiven MySQL)

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
| **XAMPP local** | No `.env` → `localhost:3306` / `root` / `cbpos_db` (no SSL) |
| **Render (Docker)** | `.env.render` → `.env` at container start → **Aiven MySQL** |
| **InfinityFree** | No `.env` → `initialize.production.php` + built-in host/db (port 3306) |
| **Manual production** | Create `.env` from `.env.example` |

## Aiven MySQL setup

### 1. Create Aiven service

1. Sign in at [Aiven](https://aiven.io)
2. Create a **MySQL** service (e.g. `cosmetics-pos`)
3. Note the connection details from **Overview** → **Connection information**

### 2. Download CA certificate

Aiven requires SSL for remote connections.

1. In the Aiven console, download **CA certificate** (`ca.pem`)
2. Save it in the project as:

```
certs/aiven-ca.pem
```

This file is public (not a secret) and should be committed with your repo for Render.

### 3. Import database schema

Import `database/infinityfree_deploy.sql` into your Aiven database (`defaultdb`) using:

- Aiven console **Query editor**, or
- MySQL client:

```bash
mysql -h mysql-2970c59f-cosmetics-pos-d042.h.aivencloud.com \
  -P 18825 -u avnadmin -p --ssl-mode=REQUIRED defaultdb \
  < database/infinityfree_deploy.sql
```

Default admin after import: `admin` / `admin123`

### 4. Configure `.env.render` (no password in git)

Committed `.env.render` (password left empty):

```env
APP_ENV=production
DB_HOST=mysql-2970c59f-cosmetics-pos-d042.h.aivencloud.com
DB_PORT=18825
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASSWORD=
DB_SSL=true
DB_SSL_CA=certs/aiven-ca.pem
```

Set the real password in **Render Dashboard → Environment → `DB_PASSWORD`**. It overrides the empty file value at runtime.

> `DB_PORT=18825` is required — port `3306` will cause connection timeouts on Aiven.

## How the application reads `.env`

1. `config.php` loads `initialize.php`
2. `initialize.php` loads `.env.render` then `.env` via `app_load_dotenv()`
3. Render `DB_PASSWORD` env var overrides file values
4. `DB_PORT` defaults to `3306` if missing; Aiven requires `18825`
5. `classes/DBConnection.php` connects with port on every path:

```php
new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
// or mysqli_real_connect(..., DB_PORT, ..., MYSQLI_CLIENT_SSL) when DB_SSL=true
```

On Render, `docker/entrypoint.sh` copies `.env.render` → `.env` before Apache starts.

## Render setup steps

### 1. Prepare files locally

```bash
# Add Aiven CA cert
# Save ca.pem as certs/aiven-ca.pem

# Edit password in .env.render
# Then commit:
git add .env.render certs/aiven-ca.pem render.yaml Dockerfile docker/
git commit -m "Configure Render with Aiven MySQL"
git push
```

### 2. Create Render service

**Option A — Blueprint**

1. Render Dashboard → **New** → **Blueprint**
2. Connect GitHub repo
3. Render deploys using `render.yaml` (Docker, no dashboard DB vars)

**Option B — Manual**

1. **New** → **Web Service** → connect repo
2. **Runtime:** Docker
3. **Dockerfile path:** `./Dockerfile`

### 3. Verify deployment

- Open your Render URL
- Admin: `/admin/login.php`
- Cashier should redirect to POS after login
- Check Render logs if you see `Database connection error`

## Switching between environments

### Localhost (XAMPP)

- **Do not** keep a `.env` file in the project root
- Uses `localhost`, port `3306`, no SSL
- Open `http://localhost/cbpos/`

### InfinityFree

- **Do not** use `.env` on the server
- Uses `initialize.production.php` for password + built-in InfinityFree host
- Port `3306`, no SSL
- See `DEPLOY_INFINITYFREE.md`

### Render + Aiven

- `.env.render` in repo → copied to `.env` in Docker
- Connects to Aiven on port `18825` with SSL
- Requires `certs/aiven-ca.pem` in the image

## Files reference

| File | Purpose |
|------|---------|
| `.env.render` | Aiven credentials for Render |
| `.env.example` | Template with placeholders |
| `certs/aiven-ca.pem` | Aiven CA certificate (download from console) |
| `certs/README.md` | CA download instructions |
| `inc/load_env.php` | Parses `.env`, SSL helpers |
| `classes/DBConnection.php` | mysqli + optional SSL |
| `docker/entrypoint.sh` | Copies `.env.render` → `.env` |

## Troubleshooting

**Database connection error on Render**

- Confirm `DB_PASSWORD` in `.env.render` is correct
- Confirm `certs/aiven-ca.pem` exists in the repo and Docker image
- Confirm Aiven service is running and allows connections from Render
- Check Render logs for SSL or access errors

**SSL certificate error**

- Re-download `ca.pem` from Aiven and replace `certs/aiven-ca.pem`
- Ensure `DB_SSL_CA=certs/aiven-ca.pem` in `.env.render`

**Local XAMPP broken**

- Delete `.env` from project root if present

**Wrong database on InfinityFree**

- Remove `.env` from InfinityFree `htdocs` if uploaded by mistake
