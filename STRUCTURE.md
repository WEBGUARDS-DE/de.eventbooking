# Dateistruktur

```
de.eventbooking/
├── .gitignore                 # Git-Ignore (keine .env, /data, /vendor)
├── .env.example               # Template für .env
├── README.md                  # Diese Dokumentation
├── STRUCTURE.md               # Diese Datei
├── composer.json              # Dependency Management
│
├── config/
│   ├── bootstrap.php          # Init: Autoload, .env laden, DB starten
│   └── db.php                 # JsonDB Klasse mit File-Locking
│
├── api/
│   ├── get-termine.php        # GET Termine + Verfügbarkeit
│   ├── create-checkout.php    # POST Stripe Session erstellen
│   └── webhook-stripe.php     # POST Webhook Handler
│
├── public/
│   ├── index.php              # Landingpage
│   ├── verify.php             # QR-Verify Seite
│   ├── .htaccess              # Apache Routing
│   ├── css/
│   │   └── style.css          # Responsive Design
│   └── js/
│       └── app.js             # Frontend Logic + Stripe Integration
│
├── data/
│   ├── termine.json           # 📝 Editierbar: Termine + Verfügbarkeiten
│   ├── tickets.json           # 🔒 Auto-generiert: Verkaufte Tickets
│   ├── qrcodes/               # 🖼️ Auto-generiert: QR-Code PNGs
│   ├── log/
│   │   ├── php_errors.log     # 📋 PHP Errors
│   │   └── *.bak              # 💾 Automatische Backups
│   ├── .gitkeep               # Verhindert leere Verzeichnisse
│   └── log/.gitkeep
│
└── vendor/                    # Composer Dependencies (nicht in Git)
    ├── stripe/
    ├── phpqrcode/
    └── ...
```

## Wichtige Dateien zum Editieren

1. **.env** — Stripe Keys + Event-Details
2. **data/termine.json** — Termine + Kapazitäten
3. **public/css/style.css** — Design anpassen
4. **api/webhook-stripe.php** — Email-Vorlage ändern

## Nicht editieren (Auto-Generated)

- `data/tickets.json` — wird von API erstellt
- `data/qrcodes/*.png` — QR-Codes
- `data/log/*.bak` — Backups

