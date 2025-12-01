# Zabala Platform - AI Coding Guidelines

Enterprise resource management system in PHP with security compliance (RA5/RA6/RA8 standards). **Critical**: All code uses **Euskara (Basque)** naming—variables, functions, DB columns, comments, UI text.

**Tech Stack**: PHP 8.0+, MySQL 8.0, Docker (Caddy reverse proxy), Hashids 4.1+  
**Request Flow**: `index.php` → `bootstrap.php` → `router.php` (Hashids-encoded URLs) → `views/*.php`  
**Deployment**: Docker Compose with 3 services (`web`: PHP-FPM, `caddy`: HTTPS proxy, `db`: MySQL 8.0)

## Core Architecture Patterns

### Bootstrap Initialization (`bootstrap.php`)
**ALWAYS** include at top of views/scripts. Sets up:
1. Session via `Seguritatea::hasieratuSesioa()` (secure cookie params)
2. Config constants (`BASE_URL`, `ASSETS_URL`, `CSRF_TOKEN_LIFETIME`, etc.)
3. Database connection → `$conn` (mysqli) + `$db_ok` flag (graceful degradation if DB fails)
4. Hashids instance → `$hashids` (or null if library missing)
5. Global helpers: `page_link()`, `encode_id()`, `redirect_to()`, `current_user()`

**Critical**: Check `$db_ok` before DB operations—never expose raw SQL errors to users.

### Hashids URL Routing (`router.php`)
URLs are obfuscated with Hashids (`/a1b2c3d4.php` → page ID 1 → `views/dashboard.php`).

**Page ID mappings** (line 18-27 in router.php):
```php
1 => 'dashboard', 2 => 'langileak', 3 => 'produktuak', 4 => 'salmentak',
5 => 'nire_salmentak', 6 => 'profile', 7 => 'salmenta_berria', 
8 => 'langilea_kudeaketa', 9 => 'home'
```

**Usage in views**:
```php
<a href="<?= page_link(3, 'produktuak') ?>">Produktuak</a>
```

**Graceful fallback**: If Hashids unavailable, returns `/produktuak.php` directly.

### View File Pattern
Every view file must follow:
```php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/usuario.php';
Seguritatea::egiaztaSesioa(); // Redirect to /index.php if not logged in
global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div>DB ez dago prest.</div>'; return; }

// Business logic here
$pageTitle = "Page Name";
$active = 'menu_item'; // Navbar highlight
require __DIR__ . '/partials/header.php';
?>
<!-- HTML content -->
<?php require __DIR__ . '/partials/footer.php'; ?>
```

**Note**: `header.php` auto-sets security headers (CSP, X-Frame-Options). Skip `Seguritatea::egiaztaSesioa()` only for public pages (e.g., `home.php`).

### Model Patterns (Two Styles Coexist)
**1. Static CRUD methods** (`Langilea`, `Produktua`, `Salmenta`):
```php
Langilea::all($conn);           // All employees
Langilea::find($conn, $id);     // Single employee
Langilea::create($conn, $data); // Insert
Langilea::update($conn, $id, $data);
Langilea::delete($conn, $id);
```

**2. OOP instance methods** (`Usuario`):
```php
$user = new Usuario($email, $password);
$user->sortu($conn); // Instance method for creation
Usuario::lortuIdAgatik($conn, $id); // Static getter
```

**Use either style**—both are valid. For new models, prefer static methods for simplicity unless complex object state is needed.

## Database Critical Rules

### Schema (`zabala.sql`)
- **`salmenta.prezioa_totala`**: MySQL GENERATED/STORED column (`kantitatea * prezioa_unitarioa`)—**NEVER insert/update directly**, MySQL auto-calculates
- **`usuario.user`**: Auto-generated from email (part before @) with numeric suffix if duplicate—not user-facing in registration
- **`usuario.nan`**: Spanish DNI/NIF format (8 digits + letter)—validated in `signin.php`

### Prepared Statements (MANDATORY - RA6 compliance)
**ALWAYS** use prepared statements—never string interpolation:
```php
$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();
```

## Security Implementation (RA5/RA6/RA8)

### CSRF Protection
```php
// In form (render once):
$csrf_token = Seguritatea::generateCSRFToken();
echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';

// On POST:
if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('CSRF token invalid');
}
```
Tokens expire after `CSRF_TOKEN_LIFETIME` (3600s). Regenerate after login.

### Rate Limiting
```php
// Login attempts (max 5):
if (!Seguritatea::egiaztaLoginIntentoa($email)) {
    die('Too many attempts');
}
// On success:
Seguritatea::zuritu_login_intentoak($email);

// Generic rate limiting (signup, etc.):
if (!Seguritatea::egiaztaRateLimit('signup', $email, 3)) {
    die('Rate limit exceeded');
}
```

### Password Validation (RA6)
```php
Seguritatea::balioztaPasahitza($password);
// Requirements: ≥8 chars, uppercase, lowercase, digit, special char
password_hash($password, PASSWORD_DEFAULT); // Uses ARGON2ID (PHP 8.0+)
```

### Security Audit Logging (RA8)
```php
Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", "user@example.com", $userId);
// Logs to seguritatea_loga table with IP, timestamp, event details

// Event types for CRUD operations:
// LANGILEA_CREATED, LANGILEA_UPDATED, LANGILEA_DELETED
// PRODUKTUA_CREATED, PRODUKTUA_UPDATED, PRODUKTUA_DELETED
// SALMENTA_CREATED, SALMENTA_DELETED
// USER_REGISTERED, BOT_SIGNUP_BLOCKED, SIGNUP_RATE_LIMIT
```

### File Upload Security (`model/fitxategia.php`)
```php
if (isset($_FILES['foto'])) {
    $result = Fitxategia::igoBalioztatuta($_FILES['foto'], 'storage/uploads/fotos');
    if ($result['success']) {
        $fotoPath = $result['path']; // Save to DB
    }
}
```
**Features**: MIME validation (JPEG/PNG/GIF/WebP), 5MB max, secure random filenames, directory traversal protection.

## Development Workflows

### Local Setup (Docker)
```powershell
docker compose up --build
# Auto-imports zabala.sql on first run
# Access: http://localhost (Caddy auto-redirects to HTTPS)
```

### Database Schema Changes
1. Edit `zabala.sql`
2. Apply manually:
```powershell
docker cp zabala.sql <container-name>:/tmp/zabala.sql
docker compose exec db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" zabala_db < /tmp/zabala.sql'
```

### Create Admin User
```powershell
docker compose exec web php scripts/seed_admin.php
# Or manually: UPDATE usuario SET rol='admin' WHERE email='user@example.com'
```

### Testing (PHPUnit)
```powershell
composer install
docker compose exec db mysql -u root -p"rootpass" < tests/zabala_test.sql
composer test           # Run all 40 unit tests
composer test:coverage  # Generate HTML coverage report
```

**Test files**: `tests/Unit/{UsuarioTest,SeguritateaTest,LangileaTest,ProduktuaTest}.php`  
**See**: `TESTING.md` for detailed guide.

### Debugging
- Logs: `storage/logs/security.log`, `storage/logs/error.log` (auto-created by bootstrap)
- Enable `display_errors` in `config/config.php` (OFF by default)
- Check `$db_ok` flag if queries fail silently
- View audit log: `SELECT * FROM seguritatea_loga ORDER BY created_at DESC LIMIT 50;`

## Euskara Naming Conventions

**Functions**: `sortu` (create), `lortu` (get), `aldatu` (update), `ezabatu` (delete), `egiazta` (verify)  
**Variables**: `izena` (name), `abizena` (surname), `bezeroa` (customer), `salmenta` (sale), `langilea` (employee)  
**Maintain Euskara terminology in all new code**—never use English equivalents (e.g., `izena`, not `name`).

## Common Pitfalls

1. **Missing bootstrap.php**: Include at top of every view/script
2. **Direct SQL queries**: Use prepared statements (RA6 compliance)
3. **Exposing hash IDs**: Use `encode_id()` in URLs/forms for privacy
4. **Skipping CSRF tokens**: All POST forms must verify tokens
5. **Hardcoded paths**: Use `BASE_URL`, `ASSETS_URL` from `config/config.php`
6. **English naming**: Maintain Euskara (`izena`, not `name`)
7. **Session before bootstrap**: Session initialized by bootstrap via `Seguritatea::hasieratuSesioa()`
8. **Updating `salmenta.prezioa_totala`**: Never insert/update—MySQL auto-calculates as GENERATED column

## Adding New Features

### New View Page
1. Create `views/orrialde_berria.php` with bootstrap include
2. Add entry in `router.php` match expression with new page ID
3. Update navbar in `views/partials/navbar.php` if needed
4. Use `page_link(newPageID, 'orrialde_berria')` in links

### New Model
```php
<?php
class NireEredua {
    public static function lortuGuztiak($conn): array {
        $stmt = $conn->prepare("SELECT * FROM nire_taula");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
```
Always accept `$conn` as first param, check if null, use prepared statements.

### AJAX Endpoints
- Return JSON: `header('Content-Type: application/json'); echo json_encode($data);`
- Verify CSRF token even for GET requests if state-changing
- Log security events with `Seguritatea::logSeguritatea()`

## Technical Debt & Cleanup

### ✅ Fixed (2025-11-28+)
- `/style/` folder removed (duplicated in `/public/assets/`)
- Path references updated to `/public/assets/style.css`
- Inconsistent config includes (bootstrap now loads config)
- Security headers (now in `header.php` + `.htaccess`)
- SQL injection in dashboard (prepared statements)
- HTTPS enforcement (`.htaccess` + Caddy)

### Recommended Refactors (Low Priority)
- Extract `Seguritatea::logSeguritatea()` calls into logging service
- Create `Response` helper class for JSON/redirect patterns
- Add database migration system (currently raw SQL imports)
- Implement autoloading for models (replace manual `require_once`)
- Define router page IDs as constants (avoid magic numbers 1-9)

---

**Documentation**: See `README.md` for installation, `TESTING.md` for test suite, `docs/PENTESTING_TXOSTENA.md` for security audit results.
