<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $termin_id = $data['termin_id'] ?? null;
    
    if (!$termin_id) {
        throw new Exception('termin_id erforderlich');
    }
    
    $DB = getDB();
    $termin = $DB->getTerminById($termin_id);
    if (!$termin) {
        throw new Exception('Termin nicht gefunden');
    }
    
    if ($termin['verfuegbare_tickets'] <= 0) {
        throw new Exception('Dieser Termin ist ausgebucht');
    }
    
    // Create Stripe Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => EVENT_NAME . ' — ' . $termin['label'],
                    'description' => 'Ticket für ' . EVENT_LOCATION
                ],
                'unit_amount' => EVENT_PRICE_EUR * 100
            ],
            'quantity' => 1
        ]],
        'mode' => 'payment',
        'success_url' => APP_URL . '/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => APP_URL . '/',
        'metadata' => [
            'termin_id' => $termin_id,
            'termin_label' => $termin['label']
        ]
    ]);
    
    echo json_encode([
        'success' => true,
        'session_id' => $session->id,
        'url' => $session->url
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
