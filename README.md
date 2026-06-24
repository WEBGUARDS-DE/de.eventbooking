# Event Ticketing System

Multi-Termin Ticket-Buchungssystem mit Stripe-Integration für Events mit mehreren Zeitslots.

**Tech Stack:** Apache + PHP 7.4+ + JSON (dateibasiert) + Stripe API + QR-Codes

---

## 🚀 Features

- ✅ Mehrfach-Terminauswahl (z.B. verschiedene Tage/Uhrzeiten)
- ✅ Live-Verfügbarkeitsanzeige pro Termin (max. 30 Tickets/Termin)
- ✅ Stripe Payment Integration (Checkout Sessions)
- ✅ Automatische QR-Code-Generierung
- ✅ Verifizierungs-Seite mit Termin im Klartext
- ✅ Email-Benachrichtigungen
- ✅ Webhooks für sichere Zahlungsbestätigung
- ✅ Überbucht-Schutz durch serverseitige Prüfung
- ✅ File-Locking gegen Race-Conditions

---

## 📋 Requirements

- Apache 2.4+ mit `mod_rewrite`
- PHP 7.4+
- Composer
- Stripe Account (Live oder Test)

---

## 🔧 Installation

### 1. Clone & Composer Setup

```bash
cd /var/www/html
git clone https://github.com/WEBGUARDS-DE/de.eventbooking.git
cd de.eventbooking

composer install
```

### 2. Environment Konfiguration

```bash
cp .env.example .env
# Bearbeite .env mit deinen Stripe Keys und Event-Details
nano .env
```

**Erforderliche Variablen in `.env`:**
```env
STRIPE_PUBLIC_KEY=pk_test_... oder pk_live_...
STRIPE_SECRET_KEY=sk_test_... oder sk_live_...
EVENT_NAME=Schlagerparty beim Griechen
EVENT_LOCATION=Tschaikowskistraße, Rostock
EVENT_PRICE_EUR=8
ORGANIZER_EMAIL=deine-email@example.com
APP_URL=https://tickets.example.com
WEBHOOK_SECRET=whsec_test_... (aus Stripe Webhook Settings)
DATA_PATH=/var/www/html/de.eventbooking/data
```

### 3. Verzeichnisse & Rechte

```bash
mkdir -p data/log data/qrcodes
chmod 755 data
chmod 755 data/log data/qrcodes
chmod 666 data/termine.json data/tickets.json
```

### 4. Apache VirtualHost

```apache
<VirtualHost *:443>
    ServerName tickets.example.com
    DocumentRoot /var/www/html/de.eventbooking/public
    
    <Directory /var/www/html/de.eventbooking/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Rewrite für /api
    <Directory /var/www/html/de.eventbooking/api>
        AllowOverride All
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your-key.key
</VirtualHost>
```

### 5. Stripe Webhook konfigurieren

1. Gehe zu Stripe Dashboard → Webhooks
2. Endpoint hinzufügen: `https://tickets.example.com/api/webhook-stripe`
3. Events abonnieren: `checkout.session.completed`
4. Secret kopieren → in `.env` als `WEBHOOK_SECRET`

---

## 📊 Datenstruktur

### data/termine.json
```json
{
    "termine": [
        {
            "id": "term_abc123xyz",
            "datum_uhrzeit": "2026-06-28T15:00:00",
            "label": "Samstag, 28. Juni 15:00 Uhr",
            "max_tickets": 30,
            "verfuegbare_tickets": 25
        }
    ]
}
```

### data/tickets.json
```json
{
    "tickets": [
        {
            "id": "tick_def456uvw",
            "termin_id": "term_abc123xyz",
            "stripe_session_id": "cs_test_...",
            "status": "gültig",
            "erstellt_am": "2026-06-24 14:30:00",
            "eingeloest_am": null,
            "qr_code_path": "/var/www/html/de.eventbooking/data/qrcodes/tick_def456uvw.png"
        }
    ]
}
```

---

## 🔐 API Endpoints

### GET `/api/get-termine`
Liefert alle verfügbaren Termine mit Verfügbarkeitsinfo.

**Response:**
```json
{
    "success": true,
    "termine": [
        {
            "id": "term_abc123xyz",
            "label": "Samstag, 28. Juni 15:00 Uhr",
            "verfuegbar": 25,
            "max": 30,
            "ausgebucht": false
        }
    ]
}
```

### POST `/api/create-checkout`
Erstellt eine Stripe Checkout Session für einen Termin.

**Request:**
```json
{ "termin_id": "term_abc123xyz" }
```

**Response:**
```json
{
    "success": true,
    "session_id": "cs_test_...",
    "url": "https://checkout.stripe.com/..."
}
```

### POST `/api/webhook-stripe`
Empfängt Webhook von Stripe nach erfolgreicher Zahlung.
- Erstellt Ticket
- Generiert QR-Code
- Sendet Confirmation-Email
- Decrementiert Verfügbarkeit für Termin

---

## 🖥️ Frontend

### `/` (Landingpage)
- Event-Details anzeigen
- Termine dynamisch laden
- Terminauswahl mit Live-Verfügbarkeitsanzeige
- Button zu Stripe Checkout

### `/verify/[ticket-id]`
- QR-Code anzeigen
- Ticket-Status (GÜLTIG / EINGELÖST)
- Termin im Klartext
- Scan-ready für Einlass-Scanner

---

## 🧪 Testing

### Mit Stripe Test Keys

Verwende diese Testdaten:
```
Kartennummer: 4242 4242 4242 4242
Ablauf: 12/50
CVC: 123
Postleitzahl: 12345
```

### Webhook lokal testen

```bash
# Mit stripe-cli
stripe listen --forward-to localhost:8000/api/webhook-stripe
```

---

## 🛠️ Admin-Tasks

### Termine bearbeiten
Bearbeite `data/termine.json` direkt:
```json
{
    "id": "term_xyz789",
    "datum_uhrzeit": "2026-07-05T16:00:00",
    "label": "Sonntag, 5. Juli 16:00 Uhr",
    "max_tickets": 40,
    "verfuegbare_tickets": 40
}
```

### Ticket als "eingelöst" markieren
```php
$DB->updateTicket('tick_abc123', ['status' => 'eingelöst', 'eingeloest_am' => date('Y-m-d H:i:s')]);
```

### Verfügbarkeit zurücksetzen
```php
$DB->updateTicket('tick_abc123', ['verfuegbare_tickets' => 30]);
```

---

## 🔒 Sicherheit

- ✅ Stripe Webhook Signature Validation
- ✅ File-Locking gegen Race-Conditions
- ✅ Serverseitige Verfügbarkeitsprüfung
- ✅ Transaktionale Decrement-Operationen
- ✅ `.env` nicht in Git
- ✅ HTTPS erforderlich (für Stripe)

---

## 📧 Emails

Nach erfolgreicher Zahlung wird an den Kunden eine Email mit:
- Event-Name
- Termin (Klartext)
- Ticket-ID
- Link zur Verifizierungs-Seite

Email-Vorlage in `api/webhook-stripe.php` anpassen.

---

## 🚨 Fehlerbehandlung

Logs:
- PHP Errors: `data/log/php_errors.log`
- Backups: `data/log/termine.json.*.bak` und `data/log/tickets.json.*.bak`

---

## 📱 Deployment Checklist

- [ ] `.env` mit echten Stripe Keys (nicht test!)
- [ ] `composer install` laufen lassen
- [ ] `/data` Verzeichnis mit korrekten Rechten (755)
- [ ] `.htaccess` aktiviert (mod_rewrite)
- [ ] HTTPS/SSL konfiguriert
- [ ] Stripe Webhook konfiguriert
- [ ] Termine in `termine.json` aktualisiert
- [ ] Email-Versand getestet
- [ ] Test-Zahlung durchgeführt
- [ ] Go-Live! 🎉

---

## 📝 Lizenz

Intern WEBGUARDS UG. Nicht öffentlich freigeben.

---

**Support:** siehe KaI Issues unter #96 / #94
