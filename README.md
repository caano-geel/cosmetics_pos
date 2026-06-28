# CBPOS — AL-Sunnah Herbal POS

PHP/MySQL point-of-sale and admin system.

## Deployment

| Target | Guide |
|--------|--------|
| **Render (Docker)** | [DEPLOY_RENDER.md](DEPLOY_RENDER.md) |
| **InfinityFree** | [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) |
| **Local XAMPP** | Import `database/cbpos_db.sql` or `database/infinityfree_deploy.sql`, open `http://localhost/cbpos/` |

## Configuration

- **Local:** auto-detected (`localhost` → `cbpos_db`)
- **InfinityFree:** `initialize.production.php` (see `.example` file)
- **Render:** environment variables `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `APP_ENV=production`

Do not commit secrets. See `.env.example` and `.gitignore`.
