# Zabala - Zertifikatua Windows Konfiantza-biltegian Inportatu
# Exekutatu PowerShell ADMINISTRATZAILE gisa

Write-Host "🔒 Zabala - SSL Zertifikatua Inportatzen..." -ForegroundColor Cyan

$certPath = "C:\Users\idz60\ERRONKA-1\certs\zabala.crt"

# Egiaztatu zertifikatua existitzen den
if (-not (Test-Path $certPath)) {
    Write-Host "❌ Errorea: zabala.crt ez da aurkitu!" -ForegroundColor Red
    Write-Host "   Lehenik exekutatu: .\setup-ssl.ps1" -ForegroundColor Yellow
    exit 1
}

Write-Host "📜 Zertifikatua aurkituta: $certPath" -ForegroundColor Green

# Kargatu zertifikatua
try {
    $cert = New-Object System.Security.Cryptography.X509Certificates.X509Certificate2($certPath)
    Write-Host "✅ Zertifikatua kargatu da" -ForegroundColor Green
    Write-Host "   Nork emana: $($cert.Issuer)" -ForegroundColor Gray
    Write-Host "   Nori emana: $($cert.Subject)" -ForegroundColor Gray
    Write-Host "   Iraungipena: $($cert.NotAfter)" -ForegroundColor Gray
} catch {
    Write-Host "❌ Errorea zertifikatua kargatzean: $_" -ForegroundColor Red
    exit 1
}

# Ireki Trusted Root Certification Authorities biltegia
Write-Host "`n🏪 Irekitzen Trusted Root Certification Authorities biltegia..." -ForegroundColor Yellow

try {
    $store = New-Object System.Security.Cryptography.X509Certificates.X509Store("Root", "CurrentUser")
    $store.Open("ReadWrite")
    
    # Egiaztatu ea dagoeneko inportatuta dagoen
    $existing = $store.Certificates | Where-Object { $_.Thumbprint -eq $cert.Thumbprint }
    
    if ($existing) {
        Write-Host "⚠️ Zertifikatua dagoeneko inportatuta dago!" -ForegroundColor Yellow
        Write-Host "   Thumbprint: $($cert.Thumbprint)" -ForegroundColor Gray
    } else {
        # Gehitu zertifikatua
        $store.Add($cert)
        Write-Host "✅ Zertifikatua inportatuta!" -ForegroundColor Green
        Write-Host "   Thumbprint: $($cert.Thumbprint)" -ForegroundColor Gray
    }
    
    $store.Close()
} catch {
    Write-Host "❌ Errorea zertifikatua inportatzean: $_" -ForegroundColor Red
    Write-Host "   Ziurtatu PowerShell ADMINISTRATZAILE gisa exekutatzen ari zarela!" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n✅ BUKATU!" -ForegroundColor Green
Write-Host "`n📍 Hurrengo pausoak:" -ForegroundColor Cyan
Write-Host "   1. Itxi GUZTIAK nabigatzailea instantziak (Chrome, Edge, Firefox)" -ForegroundColor White
Write-Host "   2. Ireki berriro eta joan: https://localhost edo https://zabala.local" -ForegroundColor White
Write-Host "   3. Ikusi giltz berdea URL barran! 🔒✅" -ForegroundColor White
Write-Host "`n⚠️ OHARRA: Firefox-ek bere zertifikatu-biltegia du." -ForegroundColor Yellow
Write-Host "   Firefox-en, joan: about:preferences#privacy → View Certificates" -ForegroundColor Yellow
Write-Host "   → Authorities tab → Import → Aukeratu zabala.crt" -ForegroundColor Yellow
