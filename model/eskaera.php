<?php
/**
 * Eskaera (Order) model for e-commerce functionality
 */
require_once __DIR__ . '/seguritatea.php';
require_once __DIR__ . '/saskia.php';

class Eskaera {
    
    /**
     * Create order from cart
     */
    public static function sortuSaskiatik($conn, int $saskia_id, array $bezero_datuak): ?int {
        if (!$conn) return null;
        
        // Get cart items
        $items = Saskia::lortuItemak($conn, $saskia_id);
        if (empty($items)) return null;
        
        // Calculate total
        $guztira = Saskia::lortuTotala($conn, $saskia_id);
        if ($guztira <= 0) return null;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO eskaera (bezero_id, izena, abizena, email, telefonoa, helbidea, hiria, posta_kodea, probintzia, guztira, ordainketa_metodoa, oharra)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $bezero_id = $bezero_datuak['bezero_id'] ?? null;
            $stmt->bind_param("issssssssdss", 
                $bezero_id,
                $bezero_datuak['izena'],
                $bezero_datuak['abizena'],
                $bezero_datuak['email'],
                $bezero_datuak['telefonoa'],
                $bezero_datuak['helbidea'],
                $bezero_datuak['hiria'],
                $bezero_datuak['posta_kodea'],
                $bezero_datuak['probintzia'],
                $guztira,
                $bezero_datuak['ordainketa_metodoa'] ?? 'tarjeta',
                $bezero_datuak['oharra'] ?? ''
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Could not create order");
            }
            $eskaera_id = $conn->insert_id;
            $stmt->close();
            
            // Add order items and update stock
            foreach ($items as $item) {
                // Add order item
                $stmt = $conn->prepare("
                    INSERT INTO eskaera_item (eskaera_id, produktu_id, produktu_izena, kantitatea, prezioa_unitarioa)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iisid", 
                    $eskaera_id, 
                    $item['produktu_id'], 
                    $item['izena'], 
                    $item['kantitatea'], 
                    $item['prezioa_unitarioa']
                );
                if (!$stmt->execute()) {
                    throw new Exception("Could not add order item");
                }
                $stmt->close();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE produktua SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmt->bind_param("iii", $item['kantitatea'], $item['produktu_id'], $item['kantitatea']);
                if (!$stmt->execute() || $stmt->affected_rows === 0) {
                    throw new Exception("Insufficient stock for product: " . $item['izena']);
                }
                $stmt->close();
            }
            
            // Clear cart
            Saskia::hutsitu($conn, $saskia_id);
            
            $conn->commit();
            return $eskaera_id;
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Eskaera::sortuSaskiatik error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order by ID
     */
    public static function lortuIdAgatik($conn, int $id): ?array {
        if (!$conn) return null;
        
        $stmt = $conn->prepare("SELECT * FROM eskaera WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $eskaera = $res->fetch_assoc();
        $stmt->close();
        
        return $eskaera ?: null;
    }
    
    /**
     * Get order items
     */
    public static function lortuItemak($conn, int $eskaera_id): array {
        if (!$conn) return [];
        
        $stmt = $conn->prepare("SELECT * FROM eskaera_item WHERE eskaera_id = ?");
        $stmt->bind_param("i", $eskaera_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    /**
     * Get customer orders
     */
    public static function lortuBezeroEskaerak($conn, int $bezero_id): array {
        if (!$conn) return [];
        
        $stmt = $conn->prepare("SELECT * FROM eskaera WHERE bezero_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $bezero_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $eskaerak = [];
        while ($row = $res->fetch_assoc()) {
            $eskaerak[] = $row;
        }
        $stmt->close();
        return $eskaerak;
    }
    
    /**
     * Update order status
     */
    public static function eguneratuEgoera($conn, int $id, string $egoera): bool {
        if (!$conn) return false;
        
        $valid_states = ['pendiente', 'prozesatzen', 'bidalita', 'entregatuta', 'ezeztatuta'];
        if (!in_array($egoera, $valid_states)) return false;
        
        $stmt = $conn->prepare("UPDATE eskaera SET egoera = ? WHERE id = ?");
        $stmt->bind_param("si", $egoera, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    
    /**
     * Get all orders (admin)
     */
    public static function lortuGuztiak($conn, ?string $egoera = null): array {
        if (!$conn) return [];
        
        if ($egoera) {
            $stmt = $conn->prepare("SELECT * FROM eskaera WHERE egoera = ? ORDER BY created_at DESC");
            $stmt->bind_param("s", $egoera);
        } else {
            $stmt = $conn->prepare("SELECT * FROM eskaera ORDER BY created_at DESC");
        }
        
        $stmt->execute();
        $res = $stmt->get_result();
        $eskaerak = [];
        while ($row = $res->fetch_assoc()) {
            $eskaerak[] = $row;
        }
        $stmt->close();
        return $eskaerak;
    }
    
    /**
     * Get order statistics
     */
    public static function lortuStatistikak($conn): ?array {
        if (!$conn) return null;
        
        $sql = "SELECT 
                COUNT(*) as eskaera_totala,
                SUM(guztira) as diru_totala,
                AVG(guztira) as batez_bestekoa,
                SUM(CASE WHEN egoera = 'pendiente' THEN 1 ELSE 0 END) as pendienteak,
                SUM(CASE WHEN egoera = 'prozesatzen' THEN 1 ELSE 0 END) as prozesatzen,
                SUM(CASE WHEN egoera = 'bidalita' THEN 1 ELSE 0 END) as bidalitakoak,
                SUM(CASE WHEN egoera = 'entregatuta' THEN 1 ELSE 0 END) as entregatuak
                FROM eskaera";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}
