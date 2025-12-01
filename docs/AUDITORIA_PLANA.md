# Zabala Plataforma - Segurtasun Auditoria Plana

**Proiektuaren izena:** Zabala Enpresen Kudeaketa Plataforma  
**Auditatutako Sistema:** PHP Web Aplikazioa (LAMP Stack)  
**Data:** 2025-11-28  
**Bertsioa:** 1.0  
**Egilea:** Segurtasun Auditoria Taldea  

---

## 1. Auditoriaren Helburua eta Irismena

### 1.1 Helburu Orokorra
Zabala plataformaren segurtasun-maila ebaluatu eta OWASP Top 10 ahuleziak identifikatu, baita ASVS estandarrak betetzen dituen egiaztatu.

### 1.2 Irismena (Scope)
- **Sistemaren deskribapena**: PHP 8.0+ web aplikazioa MySQL datu-basearekin
- **URL**: http://localhost (Docker ingurune lokala)
- **Auditoria mota**: White-box pentesting (kode iturria eskuragarri)
- **Denbora-tartea**: 2025-11-20 - 2025-11-28 (8 egun)

**Auditatu beharreko osagaiak:**
- ✅ Web aplikazio front-end (PHP views)
- ✅ Backend logika (PHP ereduak)
- ✅ Datu-basea (MySQL 8.0)
- ✅ Autentifikazio eta saio-kudeaketa
- ✅ Sarrera-balioztapenak
- ✅ Docker deployment konfigurazioa
- ✅ HTTPS eta segurtasun-goiburuak

**Auditoriatik kanpo:**
- ❌ Sareko azpiegitura (ez da produkzioan)
- ❌ DNS/Domain konfigurazioa
- ❌ Social engineering

---

## 2. Auditoriaren Faseak

### Fase 1: Planifikazioa eta Rekonozioa (Planning & Reconnaissance)
**Iraupena:** 1 egun (2025-11-20)  
**Arduradunak:** Segurtasun Analista  

**Jarduerak:**
- [x] Aplikazioaren arkitektura aztertu
- [x] Kode iturria eskuratu (GitHub repository)
- [x] Erabilitako teknologiak identifikatu (PHP, MySQL, Docker)
- [x] Entry points mapeatu (index.php, signin.php, router.php)
- [x] Erabiltzaile-rolak identifikatu (admin, langilea)
- [x] Dokumentazioa irakurri (README.md, copilot-instructions.md)

**Emaitzak:**
- Arkitektura diagrama
- Entry point zerrenda
- Teknologia stack zerrenda

---

### Fase 2: Eskaneatu eta Enumeratu (Scanning & Enumeration)
**Iraupena:** 2 egun (2025-11-21 - 2025-11-22)  
**Arduradunak:** Pentester  

**Jarduerak:**
- [x] **Port scanning** - Nmap erabiliz portu irekiak identifikatu
- [x] **Service enumeration** - Zerbitzu bertsioak detektatu
- [x] **Web directory enumeration** - Dirb/Gobuster erabili
- [x] **Teknologia fingerprinting** - Wappalyzer, WhatWeb
- [x] **SSL/TLS analisia** - SSLScan, testssl.sh

**Tresnak:**
- `nmap -sV -sC -p- localhost`
- `nikto -h http://localhost`
- `dirb http://localhost /usr/share/wordlists/dirb/common.txt`
- `testssl.sh localhost:443`

**Emaitzak dokumentatuta:**
- Portu irekien zerrenda
- Zerbitzuen bertsio zerrenda
- Aurkitutako direktorioak
- SSL/TLS konfigurazio ebaluazioa

---

### Fase 3: Ahulezien Identifikazioa (Vulnerability Assessment)
**Iraupena:** 2 egun (2025-11-23 - 2025-11-24)  
**Arduradunak:** Segurtasun Analista + Pentester  

**Jarduerak:**
- [x] **OWASP Top 10 egiaztapena**:
  1. Injection (SQLi, Command Injection)
  2. Broken Authentication
  3. Sensitive Data Exposure
  4. XML External Entities (XXE)
  5. Broken Access Control
  6. Security Misconfiguration
  7. Cross-Site Scripting (XSS)
  8. Insecure Deserialization
  9. Using Components with Known Vulnerabilities
  10. Insufficient Logging & Monitoring

- [x] **Automated scanning**:
  - OWASP ZAP full scan
  - Burp Suite Pro passive + active scan
  - SQLMap SQL injection testing
  - XSStrike XSS detection

- [x] **Manual testing**:
  - Parametro manipulation
  - Session hijacking/fixation
  - CSRF token validation
  - File upload bypass
  - Path traversal
  - IDOR (Insecure Direct Object Reference)

**Tresnak:**
- OWASP ZAP 2.14+
- Burp Suite Pro
- SQLMap 1.7+
- XSStrike
- Wfuzz

**Emaitzak:**
- Ahulezien zerrenda CVSS score-arekin
- False positive/negative analisia
- Severity classification (Critical, High, Medium, Low, Informational)

---

### Fase 4: Esplotazioa (Exploitation)
**Iraupena:** 2 egun (2025-11-25 - 2025-11-26)  
**Arduradunak:** Pentester  

**Jarduerak:**
- [x] **SQL Injection exploitation**:
  - SQLMap erabiliz datu-basea ateratzea
  - `sqlmap -u "http://localhost/signin.php" --forms --dbs`
  - Taula eta eremu guztiak enumeratu
  - Pasahitz hash-ak atera

- [x] **XSS exploitation**:
  - Stored XSS payload injection
  - Cookie stealing POC
  - Session hijacking demonstration

- [x] **Brute-force attack**:
  - Hydra erabiliz login-era
  - `hydra -l admin@zabala.eus -P rockyou.txt http-post-form "/index.php:email=^USER^&password=^PASS^:F=incorrect"`
  - Rate limiting eraginkortasuna probatu

- [x] **Privilege Escalation**:
  - Rol normal batetik admin bihurtzea
  - IDOR exploitation (beste erabiltzaileen datuak atzitzea)
  - SQL Injection bidez rol aldatzea

- [x] **File upload bypass**:
  - MIME type bypass probak
  - PHP shell upload saiakerak
  - Double extension probak (.php.jpg)

**Emaitzak:**
- Exploitation pausu-pausuko gidak
- POC (Proof of Concept) script-ak
- Pantaila-argazki eta bideo ebidentziak
- Exfiltratutako datuen laginak (anonimizatuta)

---

### Fase 5: Post-Esplotazioa (Post-Exploitation)
**Iraupena:** 1 egun (2025-11-27)  
**Arduradunak:** Pentester  

**Jarduerak:**
- [x] **Persistence mechanisms**:
  - Backdoor sortzea (webshell)
  - New admin user creation via SQLi

- [x] **Privilege maintenance**:
  - Session persistence
  - Cookie manipulation

- [x] **Data exfiltration**:
  - Datu-base backup lortzea
  - Sensitive files download (config.php, konexioa.php)

- [x] **Lateral movement**:
  - Docker container escape probak
  - Host sistema atzitzeko saiakerak

**Emaitzak:**
- Post-exploitation teknika dokumentazioa
- Data exfiltration ebidentziak
- Impaktu analisia

---

### Fase 6: Txostena eta Gomendioak (Reporting)
**Iraupena:** 1 egun (2025-11-28)  
**Arduradunak:** Segurtasun Analista  

**Jarduerak:**
- [x] Executive Summary idatzi
- [x] Technical Report prestatu
- [x] Ahuleziak CVSS 3.1 bidez sailkatu
- [x] Remediation gomendioak proposatu
- [x] Risk matrix sortu
- [x] Remediation timeline proposatu (epe motza/ertaina/luzea)

**Entregatzekoak:**
- `PENTESTING_TXOSTENA.md` - Technical report
- `EXECUTIVE_SUMMARY.md` - Management report
- `REMEDIATION_PLAN.md` - Konponketa plana
- Ebidentzia fitxategiak (screenshots, POC scripts)

---

## 3. Arduradunak eta Rolak

| Rola | Ardura | Arduraduna |
|------|--------|------------|
| **Project Lead** | Auditoria koordinatu eta kudeatu | Segurtasun Kudeatzailea |
| **Pentester** | Exploitation eta testing | Hacking Etikoko Espezialistea |
| **Security Analyst** | Ahulezien analisia eta sailkapena | Segurtasun Analista |
| **Technical Writer** | Dokumentazio eta txostenak | Dokumentazio Espezialista |
| **Stakeholder** | Emaitzen berrikuspena | Proiektu Kudeatzailea |

---

## 4. Metodologia eta Estandarrak

### 4.1 Estandarrak
- ✅ **OWASP Top 10** (2021)
- ✅ **OWASP ASVS 4.0** (Application Security Verification Standard)
- ✅ **PTES** (Penetration Testing Execution Standard)
- ✅ **NIST SP 800-115** (Technical Guide to Information Security Testing)
- ✅ **CWE Top 25** (Common Weakness Enumeration)

### 4.2 Ahulezien Sailkapena
**CVSS 3.1 Score:**
- **0.0**: Informational
- **0.1-3.9**: Low
- **4.0-6.9**: Medium
- **7.0-8.9**: High
- **9.0-10.0**: Critical

---

## 5. Timeline eta Milestones

```
2025-11-20  ┃ Planifikazioa
2025-11-21  ┃ Scanning hasi
2025-11-22  ┃ Enumeration amaitu
2025-11-23  ┃ Ahulezien identifikazioa hasi
2025-11-24  ┃ Automated + Manual testing
2025-11-25  ┃ Exploitation hasi
2025-11-26  ┃ Exploitation amaitu
2025-11-27  ┃ Post-exploitation
2025-11-28  ┃ Txostena entregatu ✅
```

---

## 6. Arrisku Kudeaketa

### 6.1 Auditoria Arriskuak
| Arriskua | Probabilitatea | Impaktua | Mitigazio estrategia |
|----------|---------------|----------|---------------------|
| Datu galera | Baxua | Altua | Backup sortu aurretik |
| Zerbitzua erorita | Ertaina | Ertaina | Proba-ingurunean bakarrik |
| Falso positiboak | Altua | Baxua | Manual verification |
| Scope creep | Ertaina | Ertaina | Scope dokumentu argia |

### 6.2 Segurtasun Neurriak
- ✅ Auditoria **proba-ingurunean** bakarrik (Docker lokala)
- ✅ Produkzioan **ez da** exekutatuko
- ✅ Backup osoa egin aurretik
- ✅ Exfiltratutako datuak **anonimizatu**
- ✅ Auditoria ondoren **sistema garbitu**

---

## 7. Komunikazio Plana

### 7.1 Txosten Formatuak
- **Technical Report**: Pentesterrentzat (PENTESTING_TXOSTENA.md)
- **Executive Summary**: Kudeatzaileentzat (EXECUTIVE_SUMMARY.md)
- **Remediation Plan**: Garatzaileentzat (REMEDIATION_PLAN.md)

### 7.2 Aurkezpen Datak
- **Draft Report**: 2025-11-27
- **Final Report**: 2025-11-28
- **Stakeholder Presentation**: 2025-11-29 (proposatua)

---

## 8. Arrakasta Irizpideak

Auditoria arrakastatsua izango da baldin:
- ✅ Fase guztiak bete badira timeline-aren barnean
- ✅ Ahulezi kritiko guztiak identifikatu badira
- ✅ Exploitation POC guztiak dokumentatu badira
- ✅ CVSS score zuzen aplikatu bada
- ✅ Remediation gomendioak praktikoak badira
- ✅ Stakeholder-ek txostena onartu badute

---

## 9. Eranskina: Lege Oharrak

**Onarpena:**
Auditoria hau proiektuaren jabeen baimenarekin exekutatzen da. Aurkitutako ahuleziak bakarrik proiektu honetarako erabiltzen dira, **ez da** inongo lege haustea egingo.

**Konfidentzialtasuna:**
Auditoria emaitzak **konfidentzialak** dira eta bakarrik proiektu taldean partekatuko dira.

**Erantzukizuna:**
Pentester taldea **ez da** erantzukizuna hartuko sistemaren kalte batengatik baldin segurtasun neurri guztiak bete badira.

---

**Onartua:**  
Segurtasun Auditoria Taldea  
2025-11-28
