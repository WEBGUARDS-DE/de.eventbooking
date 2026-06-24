<?php
require_once __DIR__ . '/../config/bootstrap.php';

$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    die('❌ Ticket-ID erforderlich');
}

$DB = getDB();
$ticket = $DB->getTicketById($ticket_id);
if (!$ticket) {
    die('❌ Ticket nicht gefunden');
}

$termin = $DB->getTerminById($ticket['termin_id']);
if (!$termin) {
    die('❌ Termin nicht gefunden');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Verifizierung</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container verify">
        <h1>Ticket Verifizierung</h1>
        
        <div class="ticket-status <?php echo htmlspecialchars($ticket['status']); ?>">
            ✅ <?php echo strtoupper(htmlspecialchars($ticket['status'])); ?>
        </div>
        
        <div class="ticket-details">
            <p><strong>Ticket-ID:</strong> <code><?php echo htmlspecialchars($ticket_id); ?></code></p>
            <p><strong>Gültig für:</strong> <?php echo htmlspecialchars($termin['label']); ?></p>
            <p><strong>Erstellt:</strong> <?php echo htmlspecialchars($ticket['erstellt_am']); ?></p>
            <?php if ($ticket['eingeloest_am']): ?>
                <p><strong>Eingelöst:</strong> <?php echo htmlspecialchars($ticket['eingeloest_am']); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($ticket['qr_code_path'] && file_exists($ticket['qr_code_path'])): ?>
            <div class="qr-code">
                <img src="data:image/png;base64,<?php echo base64_encode(file_get_contents($ticket['qr_code_path'])); ?>" alt="QR Code" title="QR Code für Einlass">
            </div>
        <?php else: ?>
            <div class="qr-code" style="background: #f0f0f0; padding: 2rem; text-align: center; border-radius: 6px;">
                <p>⏳ QR-Code wird noch generiert...</p>
            </div>
        <?php endif; ?>
        
        <a href="/" class="btn">← Zurück zur Startseite</a>
    </div>
</body>
</html>
