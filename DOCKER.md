# Docker Deployment

Deployment mit Docker + Portainer.

---

## 🚀 Quick Start (Portainer)

### 1. Stack erstellen

In Portainer:
1. **Stacks** → **Add Stack**
2. **Name:** `eventbooking`
3. **Compose** Textfeld:

```bash
git clone https://github.com/WEBGUARDS-DE/de.eventbooking.git
cd de.eventbooking
cat docker-compose.yml
```

→ Inhalt copieren & in Portainer einfügen

### 2. .env datei

Vor dem Deploy:
```bash
cp .env.example .env
# Stripe Keys + Event-Details eintragen
```

Upload .env zu Server oder als **Environment Variable** in Portainer.

### 3. Deploy

Portainer → **Deploy the Stack**

Container lädt automatisch:
- ✅ PHP 8 Apache Image
- ✅ Dependencies (Composer)
- ✅ Datenverzeichnisse

---

## 🛠️ Lokales Testen

```bash
# .env vorbereiten
cp .env.example .env
nano .env  # Stripe Keys eingeben

# Build & Run
docker-compose build
docker-compose up -d

# Logs
docker-compose logs -f eventbooking

# Stoppen
docker-compose down
```

---

## 📁 Volumes

| Host | Container | Zweck |
|------|-----------|-------|
| `./data` | `/var/www/html/data` | Tickets, Termine, QR-Codes (persistent) |
| `./.env` | `/var/www/html/.env` | Konfiguration (read-only) |

---

## 🌐 Networking

```yaml
networks:
  webguards-net:
    external: true  # Muss vorher erstellt sein!
```

**Network erstellen** (falls nicht vorhanden):
```bash
docker network create webguards-net
```

---

## 🔐 Sicherheit

- Image: `php:8-apache` (official, regelmäßig gepacht)
- `.env` read-only mounted
- Healthcheck alle 30s
- Auto-restart bei Fehler

---

## 📊 Ports

- **80** → HTTP (Redirect zu 443 sollte Apache machen)
- **443** → HTTPS (Reverse Proxy vor dem Container!)

**Mit Traefik/Nginx Reverse Proxy:**

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.eventbooking.rule=Host(`tickets.example.com`)"
  - "traefik.http.routers.eventbooking.entrypoints=websecure"
  - "traefik.http.routers.eventbooking.tls.certresolver=letsencrypt"
  - "traefik.http.services.eventbooking.loadbalancer.server.port=80"
```

---

## 🔄 Updates

```bash
git pull origin main
docker-compose build --no-cache
docker-compose up -d
```

---

## 🚨 Troubleshooting

### Container startet nicht
```bash
docker-compose logs eventbooking
```

### Permission Denied auf /data
```bash
docker exec eventbooking chown -R www-data:www-data /var/www/html/data
```

### Composer Install fehlgeschlagen
```bash
docker-compose build --no-cache --progress=plain
```

---

## 📦 Image bauen & pushen

```bash
# Local bauen
docker build -t webguards/eventbooking:latest .

# Zu Registry pushen
docker push webguards/eventbooking:latest

# In Portainer: Registry auf Docker Hub setzen
```

---

**Bereit?** Stack in Portainer deployen!

