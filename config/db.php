<?php
/**
 * JSON Database Handler mit File-Locking
 * Sichert Multi-Prozess Zugriff auf Termine und Tickets
 */

class JsonDB {
    private $data_path;
    
    public function __construct($path) {
        $this->data_path = $path;
        $this->init();
    }
    
    /**
     * Initialisiere Default-Dateien
     */
    private function init() {
        // Create log directory
        $log_path = $this->data_path . '/log';
        if (!is_dir($log_path)) {
            mkdir($log_path, 0755, true);
        }
        
        // Initialize termine.json
        $termine_file = $this->data_path . '/termine.json';
        if (!file_exists($termine_file)) {
            $initial = [
                'termine' => [
                    [
                        'id' => 'term_' . uniqid(),
                        'datum_uhrzeit' => '2026-06-28T15:00:00',
                        'label' => 'Samstag, 28. Juni 15:00 Uhr',
                        'max_tickets' => 30,
                        'verfuegbare_tickets' => 30
                    ],
                    [
                        'id' => 'term_' . uniqid(),
                        'datum_uhrzeit' => '2026-06-28T19:00:00',
                        'label' => 'Samstag, 28. Juni 19:00 Uhr',
                        'max_tickets' => 30,
                        'verfuegbare_tickets' => 30
                    ]
                ]
            ];
            file_put_contents(
                $termine_file,
                json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
        
        // Initialize tickets.json
        $tickets_file = $this->data_path . '/tickets.json';
        if (!file_exists($tickets_file)) {
            file_put_contents(
                $tickets_file,
                json_encode(['tickets' => []], JSON_PRETTY_PRINT)
            );
        }
    }
    
    /**
     * Lese Datei mit Shared Lock
     */
    private function read($filename) {
        $path = $this->data_path . '/' . $filename;
        $fp = fopen($path, 'r');
        if (!$fp) throw new Exception("Cannot open $filename for reading");
        
        flock($fp, LOCK_SH);
        $content = file_get_contents($path);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return json_decode($content, true);
    }
    
    /**
     * Schreibe Datei mit Exclusive Lock
     */
    private function write($filename, $data) {
        $path = $this->data_path . '/' . $filename;
        
        // Backup vor Write
        if (file_exists($path)) {
            copy($path, $this->data_path . '/log/' . $filename . '.' . time() . '.bak');
        }
        
        $fp = fopen($path, 'w');
        if (!$fp) throw new Exception("Cannot open $filename for writing");
        
        flock($fp, LOCK_EX);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    
    // ===== TERMINE =====
    
    public function getTermine() {
        return $this->read('termine.json')['termine'];
    }
    
    public function getTerminById($id) {
        $termine = $this->getTermine();
        foreach ($termine as $termin) {
            if ($termin['id'] === $id) return $termin;
        }
        return null;
    }
    
    public function decrementTermin($termin_id) {
        $data = $this->read('termine.json');
        foreach ($data['termine'] as &$termin) {
            if ($termin['id'] === $termin_id) {
                if ($termin['verfuegbare_tickets'] > 0) {
                    $termin['verfuegbare_tickets']--;
                    $this->write('termine.json', $data);
                    return true;
                }
                return false;
            }
        }
        return false;
    }
    
    // ===== TICKETS =====
    
    public function createTicket($termin_id, $stripe_session_id) {
        $data = $this->read('tickets.json');
        
        $ticket = [
            'id' => 'tick_' . uniqid(),
            'termin_id' => $termin_id,
            'stripe_session_id' => $stripe_session_id,
            'status' => 'gültig',
            'erstellt_am' => date('Y-m-d H:i:s'),
            'eingeloest_am' => null,
            'qr_code_path' => null
        ];
        
        $data['tickets'][] = $ticket;
        $this->write('tickets.json', $data);
        
        return $ticket;
    }
    
    public function getTicketById($id) {
        $data = $this->read('tickets.json');
        foreach ($data['tickets'] as $ticket) {
            if ($ticket['id'] === $id) return $ticket;
        }
        return null;
    }
    
    public function updateTicket($id, $updates) {
        $data = $this->read('tickets.json');
        foreach ($data['tickets'] as &$ticket) {
            if ($ticket['id'] === $id) {
                $ticket = array_merge($ticket, $updates);
                $this->write('tickets.json', $data);
                return $ticket;
            }
        }
        return null;
    }
    
    public function getTicketByStripeSession($session_id) {
        $data = $this->read('tickets.json');
        foreach ($data['tickets'] as $ticket) {
            if ($ticket['stripe_session_id'] === $session_id) {
                return $ticket;
            }
        }
        return null;
    }
}

// Global instance
if (!isset($GLOBALS['DB'])) {
    $GLOBALS['DB'] = new JsonDB(DATA_PATH);
}

function getDB() {
    return $GLOBALS['DB'];
}
?>
