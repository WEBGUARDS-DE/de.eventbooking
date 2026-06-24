<?php
require_once __DIR__ . '/../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(EVENT_NAME); ?> — Tickets</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars(EVENT_NAME); ?></h1>
        <p class="event-meta">📍 <?php echo htmlspecialchars(EVENT_LOCATION); ?> | 💶 <?php echo EVENT_PRICE_EUR; ?> EUR</p>
        
        <div id="termine-container" class="termine">
            <p>⏳ Lade Termine...</p>
        </div>
        
        <div id="checkout-container" style="display:none;">
            <button id="checkout-btn">Zur Kasse</button>
        </div>
    </div>
    
    <script src="/js/app.js"></script>
    <script>
        const stripePublicKey = '<?php echo STRIPE_PUBLIC_KEY; ?>';
        const apiUrl = '<?php echo rtrim(APP_URL, '/'); ?>/api';
    </script>
</body>
</html>
