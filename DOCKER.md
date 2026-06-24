# Docker Deployment — Portainer + Traefik

Deployment mit Portainer Stack + Traefik Reverse Proxy.

---

## 📋 Vorbereitung

### 1. Verzeichnis auf Host erstellen

```bash
mkdir -p /opt/containers/websites/de-eventbooking
cd /opt/containers/websites/de-eventbooking

# Repo clonen
git clone https://github.com/WEBGUARDS-DE/de.eventbooking.git .

# .env vorbereiten
cp .env.example .env
nano .env  # Stripe Keys + Event-Details eintragen
```

### 2. Apache Konfiguration

Stelle sicher, dass `/opt/containers/websites/apache/servername.conf` existiert:

```apache
# /opt/containers/websites/apache/servername.conf
<VirtualHost *:80>
    ServerName booking.ob5.dev
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 🚀 Portainer Stack Deploy

### 1. In Portainer: Stacks → Add Stack

**Name:** `de-eventbooking`

**Compose:**
```yaml
version: "3.9"

services:
  de-eventbooking:
    image: php:8-apache
    container_name: de-eventbooking
    restart: unless-stopped

    working_dir: /var/www/html

    volumes:
      - /opt/containers/websites/de-eventbooking:/var/www/html
      - /opt/containers/websites/apache/servername.conf:/etc/apache2/conf-available/servername.conf
    
    networks:
      - traefik-proxy

    labels:
      - traefik.enable=true
      - traefik.http.routers.de-eventbooking.entrypoints=https
      - traefik.http.routers.de-eventbooking.tls=true
      - traefik.http.routers.de-eventbooking.tls.certresolver=http
      - traefik.http.routers.de-eventbooking.rule=Host(`booking.ob5.dev`)
      - traefik.http.services.de-eventbooking.loadbalancer.server.port=80

networks:
  traefik-proxy:
    external: true
```

### 2. Deploy

Portainer → **Deploy the Stack**

---

## 🔧 Setup im Container

Nach dem Deploy muss noch initialisiert werden:

```bash
# In Container gehen
docker exec -it de-eventbooking /bin/bash

# Composer dependencies installieren
cd /var/www/html
composer install --no-dev --optimize-autoloader

# Datenverzeichnisse vorbereiten
mkdir -p data/log data/qrcodes
chmod 755 data
chmod 755 data/log data/qrcodes
touch data/termine.json data/tickets.json
chmod 666 data/termine.json data/tickets.json

# Apache mod_rewrite aktivieren
a2enmod rewrite
a2enconf servername
systemctl reload apache2

exit
```

---

## ✅ Verify

```bash
# Container läuft?
docker ps | grep de-eventbooking

# Logs prüfen
docker logs de-eventbooking

# Health Check
curl -I https://booking.ob5.dev/
```

---

## 📁 Dateistruktur auf Host

```
/opt/containers/websites/de-eventbooking/
├── .env                   (Konfiguration)
├── config/
├── api/
├── public/
├── data/                  (persistent - Tickets, QR-Codes)
│   ├── termine.json
│   ├── tickets.json
│   └── qrcodes/
├── vendor/
└── ...
```

---

## 🔄 Updates

```bash
cd /opt/containers/websites/de-eventbooking
git pull origin main
docker restart de-eventbooking
```

---

## 🚨 Troubleshooting

### Composer nicht installiert
```bash
docker exec de-eventbooking apt-get update && apt-get install -y git
docker exec de-eventbooking composer install --no-dev --optimize-autoloader
```

### mod_rewrite funktioniert nicht
```bash
docker exec de-eventbooking a2enmod rewrite
docker exec de-eventbooking apache2ctl configtest
docker exec de-eventbooking systemctl reload apache2
```

### Daten-Permissions
```bash
docker exec de-eventbooking chown -R www-data:www-data /var/www/html/data
```

### Logs ansehen
```bash
docker logs -f de-eventbooking
docker exec de-eventbooking tail -f /var/log/apache2/error.log
```

---

## 🌐 Domain anpassen

In `docker-compose.yml` ändern:
```yaml
- traefik.http.routers.de-eventbooking.rule=Host(`DEINE_DOMAIN.de`)
```

Dann:
```bash
docker-compose up -d
docker restart de-eventbooking
```

---

## 📊 Monitoring in Portainer

- **Container Stats:** Memory, CPU
- **Logs:** Real-time Apache/PHP Logs
- **Volumes:** `/opt/containers/websites/de-eventbooking` Status

---

**Bereit!** Stack in Portainer deployen. 🚀

