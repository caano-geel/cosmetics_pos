# InfinityFree Deployment Guide — CBPOS / ASH Herbal POS

## 1. Upload files

Upload the **entire project** into InfinityFree `htdocs` (web root).

**Do not upload:**
- `uploads/backups/*.sql` (local backup dumps)
- `initialize.local.php` (local only)
- `database/build_infinityfree_deploy.php` (optional builder)
- `database/run_new_modules_migration.php` (local migration runner)
- `database/fix_expenses_table.php`

**Must upload:**
- All PHP, `dist/`, `plugins/`, `admin/`, `classes/`, `uploads/` (images/media)
- `database/infinityfree_deploy.sql`

## 2. Database (phpMyAdmin)

1. Open vPanel → MySQL Databases
2. Database: `if0_42288113_cbpos_db`
3. Host: `sql305.infinityfree.com`
4. Import: `database/infinityfree_deploy.sql`

**Default logins after import:**
| User | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Cashier | `cashier1` | *(same as your local cashier password)* |

Change passwords immediately after first login.

## 3. Production database password

On the server, create `initialize.production.php` in the project root:

```php
<?php
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'YOUR_VPANEL_MYSQL_PASSWORD');
}
```

Copy from `initialize.production.php.example`. Use your **vPanel MySQL password** (same account password area in InfinityFree).

**Do not commit** `initialize.production.php` to public repos.

## 4. Environment detection

`initialize.php` auto-detects:

| Environment | When | Database |
|-------------|------|----------|
| **Local** | `localhost` / `127.0.0.1` | `cbpos_db` on localhost |
| **Production** | Live domain | InfinityFree MySQL |

**Base URL** is built dynamically from your domain and folder path — no manual edit needed for localhost or live site.

**Switch back to local:** keep developing on XAMPP as usual; no changes needed.

## 5. Folder permissions

These folders are created automatically if missing:

- `uploads/`
- `uploads/brands/`
- `uploads/backups/`
- `uploads/system/`
- `uploads/avatars/`

Ensure `uploads/` is writable (755 or 775).

## 6. Backup feature

Backups use **PHP SQL export** (no `mysqldump` / shell). Works on InfinityFree.

Backup files are stored in `uploads/backups/` and blocked from direct download via `.htaccess`.

## 7. Security (already configured)

- Production: `display_errors` off, errors logged
- `.htaccess` blocks directory listing
- Blocks direct HTTP access to `initialize.production.php`, `initialize.local.php`, `config.php`
- Upload folder blocks `.php` execution
- Backup SQL files denied from web access

## 8. Post-deploy test checklist

- [ ] Homepage loads
- [ ] Admin login (`admin` / `admin123`)
- [ ] Cashier login → lands on **POS** (`?page=pos`)
- [ ] Products page loads
- [ ] Inventory page loads
- [ ] POS sale completes
- [ ] Receipt prints
- [ ] Product/brand images load
- [ ] New image upload works
- [ ] Backup creates `.sql` file in admin
- [ ] No `localhost` or `C:\xampp` paths in browser source

## 9. Troubleshooting

**Blank page / DB error**
- Verify `initialize.production.php` exists with correct vPanel MySQL password
- Confirm database name: `if0_42288113_cbpos_db`

**Images missing**
- Upload the `uploads/` folder with logo, brands, product images from local

**404 on admin**
- Ensure files are in `htdocs` root (or update path if in subfolder)

**Cashier not redirected to POS**
- User must have `users.type = 2` (cashier / shop keeper)
