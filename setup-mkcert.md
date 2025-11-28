# mkcert Konfigurazioa - Konfiantza-mailako SSL Zertifikatuak

## Instalazioa Windows-en

### 1. Chocolatey bidez (gomendatua)
```powershell
choco install mkcert
```

### 2. Edo eskuz deskargatu
- Joan https://github.com/FiloSottile/mkcert/releases
- Deskargatu `mkcert-v*-windows-amd64.exe`
- Aldatu izena `mkcert.exe` eta gehitu PATH-era

## Erabilera Zabala Plataforman

### 1. Konfiguratu lokala CA (Certificate Authority)
```powershell
mkcert -install
```
Honek sisteman konfiantza-mailako CA bat sortzen du.

### 2. Sortu zertifikatuak localhost-erako
```powershell
cd C:\Users\idz60\ERRONKA-1
mkdir certs
cd certs
mkcert localhost 127.0.0.1 ::1
```

Sortuko dira:
- `localhost+2.pem` (zertifikatua)
- `localhost+2-key.pem` (gako pribatua)

### 3. Eguneratu Caddyfile
```caddy
localhost, 127.0.0.1 {
    reverse_proxy web:80
    
    # Erabili mkcert zertifikatuak
    tls /etc/caddy/certs/localhost+2.pem /etc/caddy/certs/localhost+2-key.pem
    
    header {
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
    }
    
    log {
        output stdout
        format console
    }
}
```

### 4. Eguneratu docker-compose.yml
Gehitu volume mount zertifikatuetarako:
```yaml
caddy:
  image: caddy:2-alpine
  ports:
    - "80:80"
    - "443:443"
    - "443:443/udp"
  volumes:
    - ./Caddyfile:/etc/caddy/Caddyfile
    - ./certs:/etc/caddy/certs:ro  # <-- GEHITU HAU
    - caddy_data:/data
    - caddy_config:/config
  depends_on:
    - web
  restart: always
```

### 5. Berrabiarazi Docker
```powershell
docker compose down
docker compose up -d
```

## Abantailak
✅ Nabigatzaileak konfiantza osoa emango dio  
✅ Ez da "Ez-segurua" mezurik  
✅ Ez da eskuzko salbuespenik behar  
✅ Lan egiten du Chrome, Firefox, Edge-ekin  
✅ Oso erraza konfiguratzea  

## Desabantailak
⚠️ Windows bakoitzean instalatu behar da  
⚠️ Garapen lokalerako bakarrik (ez produkziorako)  

## Beste aukera bat: Dominio lokala hosts fitxategiarekin

### 1. Editatu C:\Windows\System32\drivers\etc\hosts
Gehitu:
```
127.0.0.1    zabala.local
```

### 2. Sortu zertifikatuak dominio honekin
```powershell
cd C:\Users\idz60\ERRONKA-1\certs
mkcert zabala.local
```

### 3. Eguneratu Caddyfile
```caddy
zabala.local {
    reverse_proxy web:80
    tls /etc/caddy/certs/zabala.local.pem /etc/caddy/certs/zabala.local-key.pem
    # ... gainerako konfigurazioa
}
```

### 4. Atzitu https://zabala.local
Konfiantza osoa, abisu gabe!
