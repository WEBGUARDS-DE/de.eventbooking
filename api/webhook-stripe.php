<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        WEBHOOK_SECRET
    );
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

try {
    if ($event['type'] === 'checkout.session.completed') {
        $session = $event['data']['object'];
        $termin_id = $session['metadata']['termin_id'] ?? null;
        
        if (!$termin_id) {
            throw new Exception('termin_id in metadata not found');
        }
        
        $DB = getDB();
        
        // Decrement termin availability
        if (!$DB->decrementTermin($termin_id)) {
            throw new Exception('Could not decrement termin availability');
        }
        
        // Create ticket
        $ticket = $DB->createTicket($termin_id, $session['id']);
        
        // Generate QR Code
        $qr_dir = DATA_PATH . '/qrcodes';
        if (!is_dir($qr_dir)) mkdir($qr_dir, 0755, true);
        
        $qr_file = $qr_dir . '/' . $ticket['id'] . '.png';
        $qr_data = APP_URL . '/verify/' . $ticket['id'];
        
        \QRcode::png($qr_data, $qr_file, QR_ECLEVEL_L, 4);
        
        // Update ticket with QR path
        $DB->updateTicket($ticket['id'], ['qr_code_path' => $qr_file]);
        
        // Send email notification
        $customer_email = $session['customer_details']['email'] ?? ORGANIZER_EMAIL;
        $termin_label = $session['metadata']['termin_label'] ?? 'Unbekannter Termin';
        
        $email_body = "Vielen Dank für deinen Kauf!\n\n" .
            "Event: " . EVENT_NAME . "\n" .
            "Termin: " . $termin_label . "\n" .
            "Ticket-ID: " . $ticket['id'] . "\n\n" .
            "QR-Code zum Scannen: " . APP_URL . "/verify/" . $ticket['id'] . "\n\n" .
            "Viel Spaß!";
        
        mail(
            $customer_email,
            'Dein Ticket für ' . EVENT_NAME,
            $email_body,
            "From: " . ORGANIZER_EMAIL . "\r\nContent-Type: text/plain; charset=utf-8"
        );
        
        http_response_code(200);
        echo json_encode(['success' => true, 'ticket_id' => $ticket['id']]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    error_log('Webhook Error: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
