# Testing Guide - Zabala Platform

## Test Suite Konfigurazioa

### Aurrebaldintzak

1. **PHPUnit instalatu**:
```bash
composer install
```

2. **Test datu-basea sortu**:
```bash
# Docker-en
docker compose exec db mysql -u root -p"rootpass" < tests/zabala_test.sql

# Lokalean
mysql -u root -p < tests/zabala_test.sql
```

---

## Testak Exekutatu

### Test guztiak exekutatu
```bash
composer test
# edo
vendor/bin/phpunit --testdox
```

### Test suite zehatza exekutatu
```bash
# Unit tests bakarrik
vendor/bin/phpunit --testsuite Unit

# Integration tests bakarrik (etorkizunean)
vendor/bin/phpunit --testsuite Integration
```

### Test fitxategi zehatz bat exekutatu
```bash
vendor/bin/phpunit tests/Unit/UsuarioTest.php
vendor/bin/phpunit tests/Unit/SeguritateaTest.php
vendor/bin/phpunit tests/Unit/LangileaTest.php
```

### Code coverage reporta sortu
```bash
composer test:coverage
# Ondoren ireki: coverage/index.html
```

---

## Test Egitura

```
tests/
├── bootstrap.php              # Test environment setup
├── zabala_test.sql           # Test database schema
├── Unit/                     # Unit tests
│   ├── UsuarioTest.php       # Usuario model tests
│   ├── SeguritateaTest.php   # Seguritatea model tests
│   ├── LangileaTest.php      # Langilea model tests
│   └── ProduktuaTest.php     # Produktua model tests
└── Integration/              # Integration tests (etorkizunean)
```

---

## Test Kasuen Laburpena

### UsuarioTest.php (11 testak)
- ✅ `testUsuarioSortu()` - Usuario berria sortzea
- ✅ `testUsuarioGetters()` - Getter metodoak
- ✅ `testLortuEmailAgatik()` - Email bidez bilatu
- ✅ `testLortuEmailAgatikEzDago()` - Ez aurkitutako email
- ✅ `testLortuIdAgatik()` - ID bidez bilatu
- ✅ `testLortuEmailEdoNANegatik()` - Email edo NAN bidez bilatu
- ✅ `testAldatuPasahitza()` - Pasahitza aldatzea
- ✅ `testSetRol()` - Rol setter

### SeguritateaTest.php (13 testak)
- ✅ `testHasieratuSesioa()` - Saio hasieraketa
- ✅ `testGenerateCSRFToken()` - CSRF token sortzea
- ✅ `testVerifyCSRFTokenValid()` - Token baliozkoa egiaztatu
- ✅ `testVerifyCSRFTokenInvalid()` - Token baliogabea egiaztatu
- ✅ `testVerifyCSRFTokenExpired()` - Token iraungita egiaztatu
- ✅ `testBalioztaPasahitzaValid()` - Pasahitz baliozkoak
- ✅ `testBalioztaPasahitzaInvalid()` - Pasahitz baliogabeak
- ✅ `testEgiaztaLoginIntentoa()` - Login saiakerak mugatu
- ✅ `testZurituLoginIntentoak()` - Login saiakerak berrezarri
- ✅ `testEgiaztaRateLimit()` - Rate limiting generikoa
- ✅ `testEgiaztautentifikazioValid()` - Autentifikazio zuzena
- ✅ `testEgiaztautentifikazioInvalidPassword()` - Pasahitz okerra
- ✅ `testLogSeguritatea()` - Segurtasun logging

### LangileaTest.php (9 testak)
- ✅ `testLangileaConstructor()` - Langilea sortzea
- ✅ `testLangileaCreate()` - DB-an sortu
- ✅ `testLangileaAll()` - Langilea guztiak lortu
- ✅ `testLangileaFind()` - Langilea bat bilatu
- ✅ `testLangileaUpdate()` - Langilea eguneratu
- ✅ `testLangileaDelete()` - Langilea ezabatu
- ✅ `testLangileaSortu()` - Instantzia metodoa
- ✅ `testLangileaSetters()` - Setter metodoak

### ProduktuaTest.php (7 testak)
- ✅ `testProduktuaCreate()` - Produktua sortu
- ✅ `testProduktuaAll()` - Produktu guztiak lortu
- ✅ `testProduktuaFind()` - Produktua bilatu
- ✅ `testProduktuaUpdate()` - Produktua eguneratu
- ✅ `testProduktuaDelete()` - Produktua ezabatu
- ✅ `testProduktuaStockControl()` - Stock kontrola

**GUZTIRA: 40 unit testak**

---

## CI/CD Integration

GitHub Actions workflow-ak automatikoki exekutatzen ditu testak:

```yaml
# .github/workflows/test.yml
- name: Run PHPUnit
  run: vendor/bin/phpunit --testdox
```

---

## Troubleshooting

### Error: "Test database connection not available"

**Konponketa**:
```bash
# Egiaztatu MySQL martxan dagoela
docker compose ps

# Test datu-basea sortu
docker compose exec db mysql -u root -p"rootpass" -e "CREATE DATABASE IF NOT EXISTS zabala_test"
docker compose exec db mysql -u root -p"rootpass" zabala_test < tests/zabala_test.sql
```

### Error: "Class not found"

**Konponketa**:
```bash
# Composer autoloader berregeneratu
composer dump-autoload
```

### Error: "Permission denied" logs/coverage

**Konponketa**:
```bash
# Sortu beharrezko direktorioak
mkdir -p storage/logs
mkdir -p coverage
chmod -R 755 storage coverage
```

---

## Best Practices

1. **Test Isolation**: Kasu bakoitzak `setUp()` eta `tearDown()` erabiltzen ditu garbitzeko
2. **Meaningful Names**: Test izenak deskribatzaileak dira (`testUsuarioSortu`, ez `test1`)
3. **One Assertion per Test**: Test bakoitzak gauza bat probatzen du
4. **Database Cleanup**: `cleanTestDatabase()` test bakoitzaren ondoren
5. **Test Data Factories**: `seedTestUser()` eta antzeko helper funtzioak

---

## Etorkizuneko Hobekuntzak

- [ ] Integration tests gehitu
- [ ] Code coverage 90%+ lortu
- [ ] Performance tests
- [ ] End-to-end tests (Selenium/Cypress)
- [ ] Mutation testing
