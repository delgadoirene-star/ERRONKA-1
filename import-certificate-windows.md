# Auto-sinatutako Zertifikatua Windows-en Inportatu

## Metodo 1: PowerShell bidez (Erraza)

Ireki PowerShell **administratzaile gisa** eta exekutatu:

```powershell
# Inportatu zertifikatua "Trusted Root Certification Authorities" biltegian
$cert = New-Object System.Security.Cryptography.X509Certificates.X509Certificate2("C:\Users\idz60\ERRONKA-1\certs\zabala.crt")
$store = New-Object System.Security.Cryptography.X509Certificates.X509Store("Root", "CurrentUser")
$store.Open("ReadWrite")
$store.Add($cert)
$store.Close()

Write-Host "✅ Zertifikatua inportatuta! Nabigatzailea berrabiarazi." -ForegroundColor Green
```

## Metodo 2: GUI bidez (Ikustekoa)

1. **Ireki zertifikatua**:
   - Klikatu `C:\Users\idz60\ERRONKA-1\certs\zabala.crt`-n (eskuin botoiarekin)
   - Aukeratu "Install Certificate..."

2. **Store Location**:
   - Aukeratu "Current User" edo "Local Machine" (administratzaile behar du)
   - Klikatu "Next"

3. **Certificate Store**:
   - Aukeratu "Place all certificates in the following store"
   - Klikatu "Browse..."
   - Aukeratu "**Trusted Root Certification Authorities**"
   - Klikatu "OK" → "Next" → "Finish"

4. **Segurtasun abisua**:
   - Agertuko da: "Do you want to install this certificate?"
   - Klikatu "**Yes**"

5. **Berrabiarazi nabigatzailea** (Chrome, Edge, Firefox)

## Metodo 3: certmgr.msc bidez (Administratzaile)

1. Sakatu `Win + R` → idatzi `certmgr.msc` → Enter
2. Joan: "Trusted Root Certification Authorities" → "Certificates"
3. Eskuin-klikatu → "All Tasks" → "Import..."
4. Bilatu `C:\Users\idz60\ERRONKA-1\certs\zabala.crt`
5. Inportatu eta berrabiarazi nabigatzailea

## ✅ Ondoren

Ireki nabigatzailea eta joan **https://localhost** edo **https://zabala.local**

**Ez da gehiago abisu mezurik** - Giltz berdea! 🔒✅

## ⚠️ Oharra

Auto-sinatutako zertifikatuak **365 egunetan** iraungitzen dira. Ondoren, berriak sortu eta berriro inportatu beharko dituzu.

mkcert erabiliz, prozesu hau automatikoa da.
