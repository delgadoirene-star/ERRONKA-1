# XABALA Enpresen Plataforma 🏭

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

### 1. Datu-basea sortzea

```bash
mysql -u root -p < config/xabala.sql
```

### 2. Fitxategien baimenak

```bash
chmod 755 logs/
chmod 755 uploads/
```

### 3. Konfigurazioa

Editatu `config/config.php` zure ezarpenarekin.

## 🚀 Erabilea

### Login
- **URL**: `http://localhost/ariketak/ERRONKA-1%20(IGAI)/ERRONKA-1/index.php`
- Email eta pasahitza sortzea **signin.php** bidez

### Dashboard
Langileak, produktuak eta salmentak kudeatzea

### Admin baimena
Datu-basean `rol` eremua `admin` bihurtzea

## 📁 Direktorioen egitura

```
ERRONKA-1/
├── config/
│   ├── konexioa.php
│   ├── config.php
│   └── xabala.sql
├── model/
│   ├── usuario.php
│   ├── langilea.php
│   ├── produktua.php
│   ├── salmentaka.php
│   └── seguritatea.php
├── views/
│   ├── dashboard.php
│   ├── langileak.php
│   ├── produktuak.php
│   └── salmentak.php
├── style/
│   └── style.css
├── logs/
│   ├── security.log (sortuta automatikoki)
│   └── error.log (sortuta automatikoki)
├── assets/
│   └── img/
│       └── xabala-logo.png (opsionala)
├── index.php (Login)
├── signin.php (Erregistroa)
├── logout.php (Saioa itxi)
├── .htaccess (Segurtasuna)
└── README.md (Dokumentazioa)
```

## 🔐 Segurtasun-gomendioak

1. **HTTPS erabili**
2. **Pasahitza sendoa sortu** (min. 12 charaktere)
3. **Loguak kontrol egin** regularly
4. **SQL Injekzioa**: Prepared statements erabiltzen ari gara
5. **XSS Protekzioa**: `htmlspecialchars()` erabilita
6. **CSRF Protekzioa**: Tokenak bertan behera

## 📝 Erabiltzaile adibidea

```
Email: test@xabala.eus
Pasahitza: Test12345!@#
```

## ⚠️ Oharra

Datu basenaren kopia egin aurretik produkzioan jarri!

## 📞 Support

Arazo bat egonez gero, log fitxategiak kontsultatu:
- `logs/security.log`
- `logs/error.log`