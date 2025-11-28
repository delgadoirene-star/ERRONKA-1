# Zabala Plataforma - IA Programazio Jarraibideak

## Proiektuaren Ikuspegi Orokorra
Zabala enpresarentzako PHP-n oinarritutako langileen kudeaketa eta salmenta-sistema, **RA5, RA6, RA8** segurtasun-estandarrak betetzen dituena (ASVS oinarritutako autentifikazioa, ahultasunen detekzioa, hedapen segurua).

**Hizkuntza**: Euskara (Batua) aldagai-izenetan, iruzkinetan, UI testuetan, datu-baseko zutabeetan.  
**Stack**: PHP 8.0+, MySQL 8.0, Docker (Caddy reverse proxy), Hashids liburutegia (4.1+)  
**Sarrera**: `index.php` → `bootstrap.php` → `router.php` (Hashids kodifikatutako URLak)  
**Deployment**: Docker Compose 3 zerbitzuekin: `web` (PHP-FPM), `caddy` (HTTP/2), `db` (MySQL 8.0)

## Arkitektura eta Eskaeren Fluxua

1. **Sarrera-puntua**: Eskaera guztiak `index.php` edo `router.php` fitxategietara iristen dira
2. **Bootstrap** (`bootstrap.php`): Saioa hasieratzen du `Seguritatea::hasieratuSesioa()` bidez, konfigurazioa kargatzen du, DB konexioa ezartzen du (`$conn`), `$hashids` instantzia globala konfiguratzen du
3. **Router** (`router.php`): Hashids URLak deskodifikatzen ditu (adib., `/xyz123.php` → `dashboard.php`), orrialde IDak bista-fitxategietara mapeatzen ditu `match()` espresioa erabiliz
4. **Bistak** (`views/*.php`): `bootstrap.php` inkluditzen dute, beharrezko ereduak kargatu, HTML errendatzen dute PHP integratu batekin
5. **Ereduak** (`model/*.php`): PHP klase garbiak metodo estatikoekin DB eragiketarako (Usuario, Langilea, Produktua, Salmenta, Seguritatea, Fitxategia)
6. **Baliabide Publikoak** (`public/assets/`): CSS, irudiak eta fitxategi estatikoak zuzenean zerbitzatuta
7. **Biltegiratzea** (`storage/`): Egunkari-fitxategiak eta erabiltzaileen igoerak (ez daude publikoki eskuragarri)

### Key Page ID Mappings (router.php)
```php
1 => 'dashboard', 2 => 'langileak', 3 => 'produktuak', 4 => 'salmentak',
5 => 'nire_salmentak', 6 => 'profile', 7 => 'salmenta_berria', 8 => 'langilea_kudeaketa', 9 => 'home'
```
Use `page_link(int $pageId, string $fallback)` helper to generate Hashids URLs.

**Router behavior**:
- `/` or empty path → `views/home.php`
- `/{hashid}.php` → Decodes to page ID, maps to view file
- Invalid/undecodable → Falls through to direct file lookup, then `404.php`
- Graceful degradation: If Hashids library missing, uses fallback filenames directly

### Hashids Integration
- **usuario**: id, izena, abizena, nan (DNI/NIF unique), email (unique), user (unique), password (ARGON2ID hash), rol (admin|langilea), aktibo, jaiotegun, iban
- **langilea**: id, usuario_id (FK CASCADE), departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto
- **produktua**: id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo
- **salmenta**: id, langile_id (FK), produktu_id (FK), kantitatea, prezioa_unitarioa, prezioa_totala (GENERATED/STORED), data_salmenta, bezeroa_izena/nif/telefonoa, oharra
- **seguritatea_loga**: id, event_type (indexed), event_scope, usuario_id (indexed), ip, created_at, detail (TEXT for RA8 audit logging)
- **Fallback**: If Hashids unavailable, functions return raw IDs (degrades gracefully)

**Ohiko ereduak:**
```php
// Esteketan/formularioetan (bistak):
<a href="<?= page_link(3, 'produktuak') ?>">Produktuak</a>

// Router deskodifikazioa (router.php):
$decoded = $hashids->decode($page);
$pageId = $decoded[0] ?? null;

// Entitate IDak kodifikatzea:
$encoded = encode_id($userId); // Hashids katea edo ID gordina itzultzen du
```

## Datu-basearen Eskema (zabala.sql)
- **usuario**: id, izena, abizena, nan (DNI/NIF unique), email (unique), user (unique, auto-generated from email), password (ARGON2ID hash), rol (admin|langilea), aktibo, jaiotegun, iban, created_at
- **langilea**: id, usuario_id (FK CASCADE), departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto, created_at
- **produktua**: id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo, created_at
- **salmenta**: id, langile_id (FK), produktu_id (FK), kantitatea, prezioa_unitarioa, prezioa_totala (GENERATED ALWAYS AS computed), data_salmenta, bezeroa_izena/nif/telefonoa, oharra
- **seguritatea_loga**: id, event_type (indexed), event_scope, usuario_id (indexed), ip, created_at, detail (TEXT for RA8 audit logging)

**Garrantzitsua**: 
- `salmenta.prezioa_totala` is GENERATED/STORED column—never insert/update directly, MySQL auto-calculates as `kantitatea * prezioa_unitarioa`
- `usuario.user` is auto-generated from email (part before @) with numeric suffix if duplicates exist—not user-facing in registration form

## Segurtasun Inplementazioa (RA5/RA6/RA8)

### Saio-kudeaketa (`Seguritatea` klasea, `model/seguritatea.php`-n)
- **Beti** deitu `Seguritatea::hasieratuSesioa()` `$_SESSION` atzitu aurretik (bootstrap-ek globalki egiten du hau)
- Saioaren time-out: 1800s (30min jarduerarik gabe), ID berregiten du login egiterakoan
- Cookie ezaugarriak: `httponly=1`, `samesite=Lax`, `secure` (HTTPS soilik)

### CSRF Babesa
```php
// Token sortu (formulario bat behin errendatzerakoan):
$csrf_token = Seguritatea::generateCSRFToken();
// POST egiterakoan egiaztatu:
if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) { /* baztertu */ }
```
Tokenak `CSRF_TOKEN_LIFETIME` (3600s) ondoren iraungitzen dira. Login ondoren berregeneratu.

### Pasahitz Baldintzak (RA6)
```php
Seguritatea::balioztaPasahitza($password); 
// Baldintzak: ≥8 karaktere, maiuskulak, minuskulak, digitua, karaktere berezia
password_hash($password, PASSWORD_DEFAULT); // ARGON2ID erabiltzen du (PHP 8.0+)
```

### Rate Limiting (Mugatze-tasa)
```php
Seguritatea::egiaztaLoginIntentoa($email); // Gehienez 5 saiakera
Seguritatea::zuritu_login_intentoak($email); // Arrakasta izanez gero berrezarri
```

### Segurtasun Erregistroa (RA8)
```php
Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", "user@example.com", $userId);
// seguritatea_loga taulan erregistratzen du IP, denbora-zigilua, gertaeraren xehetasunekin

// CRUD eragiketetarako gertaera mota berriak:
// LANGILEA_CREATED, LANGILEA_UPDATED, LANGILEA_DELETED
// PRODUKTUA_CREATED, PRODUKTUA_UPDATED, PRODUKTUA_DELETED
// SALMENTA_CREATED, SALMENTA_DELETED
// USER_REGISTERED, BOT_SIGNUP_BLOCKED, SIGNUP_RATE_LIMIT
```

### File Upload Security
```php
require_once __DIR__ . '/model/fitxategia.php';

// Handle file upload with validation
if (isset($_FILES['foto'])) {
    $result = Fitxategia::igoBalioztatuta($_FILES['foto'], 'storage/uploads/fotos');
    if ($result['success']) {
        $fotoPath = $result['path']; // Save to DB
    } else {
        $errorea = $result['error']; // Show error to user
    }
}

// Delete file
Fitxategia::ezabatu('/storage/uploads/fotos/filename.jpg');
```

**Security features:**
- MIME type validation (JPEG, PNG, GIF, WebP only)
- File size limit: 5MB max
- Secure random filename generation
- Directory traversal protection
- Script execution prevention in uploads directory

## Konbentzio Kritikoak

### Euskarazko Izendapena
- Funtzioak: `sortu` (create), `lortu` (get), `aldatu` (update), `ezabatu` (delete), `egiazta` (verify)
- Aldagaiak: `izena` (name), `abizena` (surname), `bezeroa` (customer), `salmenta` (sale), `langilea` (employee)
- Mantendu kode berrietan euskarazko terminologia

### Erroreen Kudeaketa
- **Inoiz ez** agertu DB errore gordinak erabiltzaileei—erregistratu `error_log()` bidez eta mezu generikoak erakutsi
- Egiaztatu `$db_ok` bandera globala DB eragiketak egin aurretik
- Degradazio graziosoa: `$conn` null bada, erakutsi "DB ez dago prest" alerta eta itzuli goiz

### Bista Eredua
```php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/usuario.php';
Seguritatea::egiaztaSesioa(); // Saioa hasi gabe badago, signin-era birbideratu
global $db_ok, $conn;
if (!$db_ok || !$conn) { /* erakutsi errorea, itzuli */ }
// ... negozio-logika ...
$pageTitle = "Orrialde Izena";
$active = 'menu_item';
require __DIR__ . '/partials/header.php';
?>
<!-- HTML edukia -->
<?php require __DIR__ . '/partials/footer.php'; ?>
```

**Garrantzitsua**: 
- `bootstrap.php` inkluditzeak `$conn`, `$db_ok`, `$hashids` global aldagaiak ezartzen ditu
- `header.php` automatikoki ezartzen ditu segurtasun-goiburuak (CSP, X-Frame-Options, etab.)
- `Seguritatea::egiaztaSesioa()` babesgabeko bistetarako deitu (public orrialdeetan ez)

### Prestatutako Kontsultak (Beti)
```php
$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
```
**Inoiz ez** erabili kate-interpolazioa SQL-rako (SQLi babesa RA6 arabera).

### Laguntzaile Funtzioak (bootstrap.php-n definituta)
- `page_link(int $id, string $fallback)`: Hashids URL sortu edo ordezko bidea
- `encode_id(int $id)`: Hashids katea edo ID gordina itzuli liburutegia falta bada
- `redirect_to(string $path)`: Header birbideratzea edo JS ordezko modua goiburuak bidali badira
- `current_user(mysqli $conn)`: Uneko erabiltzaile array lortu `Usuario::lortuIdAgatik()` bidez

## Garapenerako Lan-fluxua

### Tokiko Konfigurazioa (Docker)
```bash
docker compose up --build
# DB-k zabala.sql automatikoki inportatzen du lehen abiaraztean
# Atzitzea: http://localhost
```

### Datu-baseko Aldaketak
1. Editatu `zabala.sql` (eskemaren aldaketak)
2. Edukiontzia berreratu edo eskuz inportatu:
   ```bash
   docker cp zabala.sql <edukiontzia>:/tmp/zabala.sql
   docker compose exec db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" zabala_db < /tmp/zabala.sql'
   ```

### Admin Erabiltzailea Sortzea
Exekutatu `scripts/seed_admin.php` honela:
```bash
docker compose exec web php scripts/seed_admin.php
```
Edo eskuz eguneratu DB-a: `UPDATE usuario SET rol='admin' WHERE email='user@example.com'`

### Arazketa (Debugging)
- Egunkari-fitxategiak: `storage/logs/security.log`, `storage/logs/error.log` (bootstrap-ek automatikoki sortzen ditu)
- Gaitu display_errors `config/config.php`-n garapenerako (lehenespenez desgaituta)
- Egiaztatu `$db_ok` bandera DB kontsultak hutsik huts egiten badute

## Ohiko Akatsak

1. **bootstrap.php falta**: Beti inkluditu bista/script berrien goialdean
2. **SQL kontsulta zuzenak**: Erabili prestatutako kontsultak (RA6 betetzea)
3. **Hash IDak agerian uztea**: Erabili `encode_id()` URL/formularioetan pribatutasunarentzat
4. **CSRF tokenak saltatu**: POST formulario guztiek tokenak egiaztatu behar dituzte
5. **Bide gogortuta**: Erabili `BASE_URL`, `ASSETS_URL` konstanteak `config/config.php`-tik
6. **Ingelesezko izenak kode berrian**: Mantendu euskarazko izendapena (izena, ez name)
7. **Saioa bootstrap aurretik**: Saioa bootstrap-ek hasieratzen du `Seguritatea::hasieratuSesioa()` bidez

## Zaharkitutako Ereduak eta Garbiketarako Gomendioak

### ✅ Garbitutako Fitxategiak (2025-11-28)
- **`style/` karpeta**: EZABATUTA - Edukia `public/assets/` karpetan bikoiztuta zegoen
- **Bide-erreferentziak**: Eguneratuta `/style/` → `/public/assets/` guztietan
- **Bootstrap.php**: PHP Intelephense advertentziak konponduta tipo-komentrioekin

### Kentzeko Fitxategiak (Zor Teknikoa)
- **`*.bak` fitxategiak**: Oraingoz ez dago (lehenago garbituta)
- **`demo/` karpeta**: Oraingoz ez dago (lehenago garbituta)
- **`views/.htaccess`**: Aztertu ea beharrezkoa den (root-ean ere badago `.htaccess`)

### Kodearen Garbiketarako Aukerak
1. **~~Inconsistent config includes~~**: ✅ KONPONDUTA - Orain `bootstrap.php` kargatu ondoren `config.php` ez da behar
2. **Mixed error handling**: Estandarizatu try-catch blokeak vs inline egiaztapenak; erabili `$db_ok` bandera modu konsistentean
3. **~~Hardcoded database name~~**: ✅ KONPONDUTA - Orain `zabala_db` erabiltzen da guztienean
4. **~~Security headers~~**: ✅ KONPONDUTA - Orain `header.php` eta `.htaccess`-ean ezarrita
5. **Magic numbers**: Router orrialde IDak (1-9) konstanteak gisa definitu mantengarritasunerako
6. **Null coalescing chains**: Sinplifikatu habiaratutako `?? ''` ereduak lehen balioztapenarekin
7. **~~CSS path inconsistency~~**: ✅ KONPONDUTA - Orain `/public/assets/style.css` erabiltzen da leku guztietan

### Security Improvements Implemented
1. **SQL Injection Fixed**: `dashboard.php` now uses prepared statements for user-specific queries
2. **NAN Validation**: `signin.php` validates Spanish DNI/NIF format (8 digits + letter)
3. **Missing Auth Checks**: Added session validation to `produktuak.php`
4. **Security Headers**: CSP and security headers now set in `header.php` and `.htaccess`
5. **Database Connection**: Created `config/konexioa.php` with secure error handling
6. **Production Hardening**: Removed `display_errors` from `signin.php`
7. **Rate Limiting Enhanced**: Generic rate limiting added for signup and other actions (max 3-5 attempts per 15min)
8. **Honeypot Bot Detection**: Invisible field in signup form blocks automated bot registrations
9. **HTTPS Enforcement**: `.htaccess` forces HTTPS redirects and prevents HTTP access
10. **File Upload Security**: Created `model/fitxategia.php` with MIME validation, size limits (5MB), secure filename generation
11. **Comprehensive Audit Logging**: All CRUD operations (create/update/delete) now logged to `seguritatea_loga` table
12. **Demo Files Removed**: Deleted `demo/` folder and `.bak` files for production readiness

### Recommended Refactors (Low Priority)
- Extract `Seguritatea::logSeguritatea()` calls into a dedicated logging service class
- Create a `Response` helper class for consistent JSON/redirect responses
- Add database migration system (currently raw SQL imports)
- Implement autoloading for models (replace manual `require_once` chains)
- Add input sanitization helper for common patterns (trim + validation)
- Create DTO/Value Objects for complex data structures (Usuario, Langilea)

## Eginbide Berriak Gehitzea

### Bista Orrialde Berria
1. Sortu `views/orrialde_berria.php` bootstrap inkluzioarekin
2. Gehitu sarrera `router.php` match adierazpenean orrialde ID berriarekin
3. Eguneratu navbar `views/partials/navbar.php`-n behar izanez gero
4. Erabili `page_link(orrialdeBerriID, 'orrialde_berria')` esteketan

### Eredu Berria
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
Beti onartu `$conn` lehen parametro gisa, egiaztatu null den, erabili prestatutako kontsultak.

**Eredu eredu biak existitzen dira**:
1. **Klase-oinarritua** (`Usuario`): OOP metodoak objektuak kudeatzeko
2. **Metodo estatikoak** (`Langilea`, `Produktua`, `Salmenta`): Statikoak CRUD operazioetarako (all, find, create, update, delete)

Eredu berriek bi estiloak ere erabil ditzakete. CRUD operazio erraza metodo estatikoekin (`Langilea::all($conn)`), konplexuagoak objektu-instantziekin.

### AJAX Amaiera-puntuak
- Itzuli JSON `header('Content-Type: application/json'); echo json_encode($data);` bidez
- Egiaztatu CSRF tokena GET-entzat ere egoera aldatzen badu
- Erregistratu segurtasun-gertaerak `Seguritatea::logSeguritatea()` bidez

## Proba Kredentzialak
Ikusi `README.md` erabiltzaile-adibidea—normalean `signin.php` erregistro-fluxuaren bidez sortzen da. Lehenetsi admin-ik ez dago; eskuz sustatu behar da DB-an.
