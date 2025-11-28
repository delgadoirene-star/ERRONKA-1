# Zabala Platform - SSL Konfigurazio Automatikoa (OpenSSL)
# Ez behar da instalazio berezirik - Docker erabiliko dugu

Write-Host "🔒 Zabala - SSL Zertifikatuak Konfiguratzen..." -ForegroundColor Cyan

# 1. Sortu certs karpeta
Write-Host "`n📁 Sortzen certs/ karpeta..." -ForegroundColor Yellow
$certsPath = "C:\Users\idz60\ERRONKA-1\certs"
if (-not (Test-Path $certsPath)) {
    New-Item -ItemType Directory -Path $certsPath | Out-Null
}
Set-Location $certsPath
Write-Host "✅ Karpeta prest!" -ForegroundColor Green

# 2. Sortu zertifikatuak Docker OpenSSL erabiliz
Write-Host "`n🔑 Sortzen SSL zertifikatuak OpenSSL erabiliz..." -ForegroundColor Yellow

# Sortu gako pribatua
docker run --rm -v "${certsPath}:/certs" alpine/openssl genrsa -out /certs/zabala.key 2048

# Sortu zertifikatua
docker run --rm -v "${certsPath}:/certs" alpine/openssl req -new -x509 -key /certs/zabala.key -out /certs/zabala.crt -days 365 -subj "/C=ES/ST=Bizkaia/L=Bilbao/O=Zabala/CN=localhost" -addext "subjectAltName=DNS:localhost,DNS:zabala.local,IP:127.0.0.1"

if ((Test-Path "$certsPath\zabala.key") -and (Test-Path "$certsPath\zabala.crt")) {
    Write-Host "✅ Zertifikatuak sortuak!" -ForegroundColor Green
} else {
    Write-Host "❌ Errorea zertifikatuak sortzean" -ForegroundColor Red
    exit 1
}

# 3. Eguneratu Caddyfile
Write-Host "`n📝 Eguneratzen Caddyfile..." -ForegroundColor Yellow
$caddyfile = @"
# Caddy configuration for Zabala Platform
# HTTPS auto-sinatu zertifikatuarekin

localhost, 127.0.0.1, zabala.local {
    # Reverse proxy to Apache container
    reverse_proxy web:80
    
    # SSL zertifikatuak erabili
    tls /etc/caddy/certs/zabala.crt /etc/caddy/certs/zabala.key
    
    # Security headers
    header {
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
    }
    
    # Logging
    log {
        output stdout
        format console
    }
}

# Produkziorako domeino errealekin:
# yourdomain.com {
#     reverse_proxy web:80
#     tls your-email@example.com
# }
"@

Set-Content -Path "C:\Users\idz60\ERRONKA-1\Caddyfile" -Value $caddyfile
Write-Host "✅ Caddyfile eguneratuta!" -ForegroundColor Green

# 4. Eguneratu docker-compose.yml
Write-Host "`n🐳 Eguneratzen docker-compose.yml..." -ForegroundColor Yellow
$dockerCompose = Get-Content "C:\Users\idz60\ERRONKA-1\docker-compose.yml" -Raw

if ($dockerCompose -notmatch './certs:/etc/caddy/certs:ro') {
    $dockerCompose = $dockerCompose -replace '(- ./Caddyfile:/etc/caddy/Caddyfile)', "`$1`n      - ./certs:/etc/caddy/certs:ro"
    Set-Content -Path "C:\Users\idz60\ERRONKA-1\docker-compose.yml" -Value $dockerCompose
    Write-Host "✅ docker-compose.yml eguneratuta!" -ForegroundColor Green
} else {
    Write-Host "⚠️ docker-compose.yml dagoeneko konfiguratuta dago" -ForegroundColor Yellow
}

# 5. Gehitu zabala.local hosts fitxategira (administratzaile baimena behar)
Write-Host "`n🌐 Gehitzen zabala.local hosts fitxategira..." -ForegroundColor Yellow
$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
try {
    $hostsContent = Get-Content $hostsPath -Raw
    if ($hostsContent -notmatch 'zabala.local') {
        Add-Content -Path $hostsPath -Value "`n127.0.0.1    zabala.local" -ErrorAction Stop
        Write-Host "✅ zabala.local gehituta!" -ForegroundColor Green
    } else {
        Write-Host "⚠️ zabala.local dagoeneko hosts-en dago" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️ Ez da hosts fitxategia editatu (administratzaile baimena behar)" -ForegroundColor Yellow
    Write-Host "   Eskuz gehitu: 127.0.0.1    zabala.local" -ForegroundColor Cyan
}

# 6. Berrabiarazi Docker
Write-Host "`n🔄 Berrabiarazten Docker Compose..." -ForegroundColor Yellow
Set-Location "C:\Users\idz60\ERRONKA-1"
docker compose down
docker compose up -d

Write-Host "`n✅ BUKATU! SSL zertifikatuak konfiguratuta!" -ForegroundColor Green
Write-Host "`n📍 Atzitu hauetako edozein:" -ForegroundColor Cyan
Write-Host "   https://localhost" -ForegroundColor White
Write-Host "   https://127.0.0.1" -ForegroundColor White
Write-Host "   https://zabala.local" -ForegroundColor White
Write-Host "`n⚠️ OHARRA: Nabigatzaileak 'auto-sinatua' abisua erakutsiko du." -ForegroundColor Yellow
Write-Host "   Hau normala da. Klikatu 'Aurrera joan' edo 'Advanced > Proceed'." -ForegroundColor Yellow
Write-Host "`n🔒 Enkriptazioa 100% segurua da, bakarrik ez dago CA publikoak sinatua." -ForegroundColor Green

