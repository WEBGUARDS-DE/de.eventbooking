<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

try {
    $DB = getDB();
    $termine = $DB->getTermine();
    
    $response = [];
    foreach ($termine as $termin) {
        $response[] = [
            'id' => $termin['id'],
            'label' => $termin['label'],
            'verfuegbar' => $termin['verfuegbare_tickets'],
            'max' => $termin['max_tickets'],
            'ausgebucht' => $termin['verfuegbare_tickets'] <= 0
        ];
    }
    
    echo json_encode(['success' => true, 'termine' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
