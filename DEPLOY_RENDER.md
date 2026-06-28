# Render Deployment — CBPOS (Docker)

Deploy this PHP/MySQL POS to [Render](https://render.com) using Docker.

## Prerequisites

- GitHub repository with this project
- **External MySQL database** (Render does not offer free MySQL). Options:
  - [PlanetScale](https://planetscale.com)
  - [Railway](https://railway.app)
  - [Aiven](https://aiven.io)
  - Any MySQL 5.7+ / MariaDB host

## 1. Import database

Import `database/infinityfree_deploy.sql` (or your full schema) into your MySQL database.

Default admin: `admin` / `admin123`

## 2. Push to GitHub

```bash
git init
git add .
git commit -m "Prepare CBPOS for Render Docker deployment"
git remote add origin https://github.com/YOUR_USER/cbpos.git
git push -u origin main
```

Secrets are excluded via `.gitignore` — do not commit `initialize.production.php` or `.env`.

## 3. Create Render web service

### Option A: Blueprint (`render.yaml`)

1. Render Dashboard → **New** → **Blueprint**
2. Connect your GitHub repo
3. Render reads `render.yaml` and creates the web service
4. Set environment variables when prompted:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_NAME`

### Option B: Manual

1. **New** → **Web Service**
2. Connect GitHub repo
3. **Runtime:** Docker
4. **Dockerfile path:** `./Dockerfile`
5. Add environment variables:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `DB_HOST` | your MySQL host |
| `DB_USER` | your MySQL user |
| `DB_PASSWORD` | your MySQL password |
| `DB_NAME` | your database name |

Optional: `APP_URL` = `https://your-service.onrender.com` (if auto URL detection fails)

## 4. How configuration works

| Environment | Detection | Database source |
|-------------|-----------|-----------------|
| **XAMPP local** | `localhost` host | `cbpos_db` / root |
| **InfinityFree** | non-local, no `DB_HOST` env | `initialize.production.php` + InfinityFree host |
| **Render Docker** | `DB_HOST` env var set | `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` |

Local and InfinityFree setups are unchanged when env vars are not set.

## 5. Docker details

- **Image:** `php:8.2-apache`
- **Extensions:** mysqli, pdo_mysql, gd
- **Document root:** `/var/www/html` (project root)
- **Port:** listens on Render `PORT` (via entrypoint)
- **Uploads:** `uploads/` created with `775` permissions on start

### Build locally (optional)

```bash
docker build -t cbpos .
docker run -p 8080:80 \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e DB_USER=root \
  -e DB_PASSWORD= \
  -e DB_NAME=cbpos_db \
  cbpos
```

## 6. Uploads on Render

Render free tier has **ephemeral disk** — uploaded files may be lost on redeploy. For production:

- Use external object storage (S3, etc.) for uploads, or
- Accept re-upload after deploys, or
- Use a Render paid plan with persistent disk

## 7. Post-deploy checklist

- [ ] Homepage loads (`/`)
- [ ] Admin login (`/admin/login.php`)
- [ ] Cashier → POS redirect works
- [ ] Products / inventory load
- [ ] POS sale completes
- [ ] Images load (upload `uploads/` folder or re-upload assets)
- [ ] Backup creates SQL file (PHP export — no shell required)

## 8. Files added for Render

| File | Purpose |
|------|---------|
| `Dockerfile` | PHP 8.2 + Apache image |
| `render.yaml` | Render Blueprint |
| `docker/entrypoint.sh` | PORT binding + writable uploads |
| `docker/apache/` | Apache vhost templates |
| `.dockerignore` | Smaller build context |
| `.env.example` | Env var reference |
