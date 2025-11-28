GARRANTZITSUA - INSTALAZIOA
Zabala.sql inportatzeko pausoak:

1. Edukiontzi-izena lortu:

Exekutatu: docker compose ps
Kontu egin DB edukiontziaren izenarekin (adib.: erronka-1_db-1).

2. Fitxategia edukiontzira kopiatu:

docker cp zabala.sql <edukiontzi-izena>:/tmp/zabala.sql (ordeztu <edukiontzi-izena>).

3. Edukiontziaren barruan inportatu:

docker compose exec db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" zabala_db < /tmp/zabala.sql'
Ingurune-aldagaia erabiltzen du pasahitzarentzat.

4. Egiaztatu:

docker compose exec db mysql -u root -p zabala_db (sartu pasahitza: password123)
Exekutatu: SHOW TABLES;

# ZABALA Enpresen Plataforma 🏭

Enpresen kudeaketa eta salmentaren sistema PHP-n garatuta.

## 📋 Aukerak

- ✅ Erabiltzaile kudeaketa (Login/Signin)
- ✅ Langileak kudeaketa
- ✅ Produktuak eta inbentarioa
- ✅ Salmentaken seguimena
- ✅ Dashboard estatistikoekin
- ✅ Segurtasun-neurrien RA5, RA6, RA8 betetzea

## 🔒 Segurtasun-neurrian

### RA5 - Segurtasun-maila zehaztapena
- Nazioarteko ASVS estandarrak jarraituak
- Pasahitzaren balioztapena bortitza
- CSRF token protekzioa
- Rate limiting (login saioak)

### RA6 - Web-aplikazioaren ahulezien detekzioa
- Erabiltzaileen sarrera balioztapena (SQLi aurka)
- Session kudeaketa segurua
- Password hashing (ARGON2ID)
- Email/NAN validazioa
- Benetakotasun egiaztapena

### RA8 - Softwarea hedatzeak
- Segurtasun-log sistemak
- Sesioen kontrol osoa
- Error logging
- HTTPS eta cookie seguruak

## 📦 Instalazioa

### Docker bidez (gomendatua)
1. `docker compose up --build`
2. Datu-basea automatikoki sortzen da eta `zabala.sql` inportatzen da.
3. Web-aplikazioa eskuragarri: `http://localhost`

### Eskuzko instalazioa (ez da gomendatzen)
1. Datu-basea sortzea
   ```bash
   mysql -u root -p < config/zabala.sql
   ```

2. Fitxategien baimenak
   ```bash
   chmod 755 storage/logs/
   chmod 755 storage/uploads/
   ```

3. Konfigurazioa
   Editatu `config/config.php` zure ezarpenarekin.

## 🚀 Erabilera

### Saioa hastea (Login)
- **URL**: `http://localhost/index.php`
- Emaila eta pasahitza sortzea **signin.php** bidez

### Aginte-panela (Dashboard)
Langileak, produktuak eta salmentak kudeatzea

### Administratzaile-baimena
Datu-basean `rol` eremua `admin` bihurtu:
```sql
UPDATE usuario SET rol='admin' WHERE email='zure@emaila.eus';
```

## 📁 Direktorioen egitura

```
ERRONKA-1/
├── .github/
│   ├── copilot-instructions.md  # AI coding guidelines
│   └── workflows/
├── config/
│   ├── konexioa.php            # Database connection
│   ├── config.php              # Application configuration
│   └── zabala.sql              # Database schema
├── model/
│   ├── usuario.php             # User model
│   ├── langilea.php            # Employee model
│   ├── produktua.php           # Product model
│   ├── salmenta.php            # Sales model
│   ├── seguritatea.php         # Security utilities
│   └── fitxategia.php          # File upload handler
├── views/
│   ├── dashboard.php           # Main dashboard
│   ├── langileak.php           # Employees view
│   ├── produktuak.php          # Products view
│   ├── salmentak.php           # Sales view
│   ├── nire_salmentak.php      # My sales view
│   ├── langilea_kudeaketa.php  # Employee management
│   ├── home.php                # Landing page
│   └── partials/
│       ├── header.php          # Common header
│       ├── navbar.php          # Navigation bar
│       └── footer.php          # Common footer
├── public/
│   └── assets/
│       ├── style.css           # Main stylesheet
│       └── img/                # Images
├── storage/
│   ├── logs/
│   │   ├── security.log        # Security audit log
│   │   └── error.log           # Error log
│   └── uploads/                # User uploaded files
├── tests/                      # PHPUnit test suite
│   ├── bootstrap.php           # Test environment setup
│   ├── zabala_test.sql        # Test database schema
│   └── Unit/                   # Unit tests
│       ├── UsuarioTest.php     # Usuario model tests
│       ├── SeguritateaTest.php # Security tests
│       ├── LangileaTest.php    # Langilea model tests
│       └── ProduktuaTest.php   # Produktua model tests
├── scripts/
│   └── seed_admin.php          # Admin user seeder
├── index.php                   # Login entry point
├── signin.php                  # User registration
├── logout.php                  # Session logout
├── router.php                  # Hashids URL router
├── bootstrap.php               # Application bootstrap
├── .htaccess                   # Apache security config
├── docker-compose.yml          # Docker orchestration
├── Dockerfile                  # Docker container config
└── README.md                   # Documentation
```

## 🔐 Segurtasun-gomendioak

1. **HTTPS erabili** produkzioan
2. **Pasahitz sendoa sortu** (gutxienez 12 karaktere, maiuskulak, minuskulak, zenbakiak eta ikur bereziak)
3. **Egunkari-fitxategiak aztertu** erregularki (`storage/logs/`)
4. **SQL Injection babesa**: Prestatutako kontsultak (prepared statements) erabiltzen ditugu
5. **XSS Babesa**: `htmlspecialchars()` funtzioa erabiltzen da
6. **CSRF Babesa**: Token bidezko babesa inplementatuta
7. **Rate Limiting**: Login eta erregistro saioak mugatuta
8. **Fitxategi igoerak**: MIME mota eta tamaina balioztapena

## 📝 Erabiltzaile-adibidea

```
Emaila: test@zabala.eus
Pasahitza: Test12345!@#
```

**Oharra**: Erabiltzaile hau ez da lehenetsia. Erregistratu `signin.php` bidez edo exekutatu:
```bash
docker compose exec web php scripts/seed_admin.php
```

## 🧪 Testak Exekutatu

### PHPUnit instalatu
```bash
composer install
```

### Test datu-basea prestatu
```bash
docker compose exec db mysql -u root -p"rootpass" < tests/zabala_test.sql
```

### Testak exekutatu
```bash
# Test guztiak
composer test

# Test zehatza
vendor/bin/phpunit tests/Unit/UsuarioTest.php

# Code coverage
composer test:coverage
```

**Test Suite**: 40+ unit testak  
📄 Dokumentazio osoa: `TESTING.md`

## ⚠️ Oharra

Produkziora pasa aurretik, egiaztatu:
- ✅ Datu-basearen babeskopia egina
- ✅ HTTPS gaituta
- ✅ `display_errors = Off` PHP konfigurazioan
- ✅ Ingurune-aldagaiak ondo konfiguratuta
- ✅ Segurtasun-goiburuak aktibatuta (`.htaccess`)

## 📞 Laguntza

Arazoren bat badago, egiaztatu egunkari-fitxategiak:
- `storage/logs/security.log` - Segurtasun-gertaerak
- `storage/logs/error.log` - Errore-mezuak

Datu-baseko audit-loga:
```sql
SELECT * FROM seguritatea_loga ORDER BY created_at DESC LIMIT 50;
```