# mkcert Instalazio Eskuzkoa (Chocolatey gabe)

## 1. Deskargatu mkcert

1. Joan: https://github.com/FiloSottile/mkcert/releases/latest
2. Deskargatu: `mkcert-v1.4.4-windows-amd64.exe` (edo azken bertsioa)
3. Aldatu izena `mkcert.exe` eta mugitu `C:\Windows\System32\` karpetara (edo gehitu PATH-era)

## 2. Instalatu mkcert CA

Ireki PowerShell **administratzaile gisa** eta exekutatu:

```powershell
mkcert -install
```

Honek:
- Sortzen du lokala Certificate Authority (CA)
- Gehitzen du Windows konfiantza-biltegian
- Ez da gehiago abisu mezurik agertuko!

## 3. Sortu zertifikatuak

```powershell
cd C:\Users\idz60\ERRONKA-1\certs
mkcert localhost 127.0.0.1 ::1 zabala.local
```

Sortuko dira:
- `localhost+3.pem` (zertifikatua)
- `localhost+3-key.pem` (gako pribatua)

## 4. Eguneratu Caddyfile

```caddy
localhost, 127.0.0.1, zabala.local {
    reverse_proxy web:80
    tls /etc/caddy/certs/localhost+3.pem /etc/caddy/certs/localhost+3-key.pem
    # ... gainerako konfigurazioa
}
```

## 5. Berrabiarazi

```powershell
docker compose down
docker compose up -d
```

## ✅ Emaitza

Nabigatzailean: **Ez da abisurik** - Giltz berdea! 🔒✅
