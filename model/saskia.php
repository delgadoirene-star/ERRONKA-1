<?php
/**
 * Saskia (Shopping Cart) model for e-commerce functionality
 */
require_once __DIR__ . '/seguritatea.php';

class Saskia {
    
    /**
     * Get or create cart for current session/customer
     */
    public static function lortuEdoSortu($conn, ?int $bezero_id = null): ?int {
        if (!$conn) return null;
        
        $saio_id = session_id();
        if (empty($saio_id)) return null;
        
        // First try to find existing cart
        if ($bezero_id) {
            $stmt = $conn->prepare("SELECT id FROM saskia WHERE bezero_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $bezero_id);
        } else {
            $stmt = $conn->prepare("SELECT id FROM saskia WHERE saio_id = ? AND bezero_id IS NULL ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("s", $saio_id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return (int)$row['id'];
        }
        
        // Create new cart
        $stmt = $conn->prepare("INSERT INTO saskia (bezero_id, saio_id) VALUES (?, ?)");
        $stmt->bind_param("is", $bezero_id, $saio_id);
        $ok = $stmt->execute();
        $cart_id = $ok ? $conn->insert_id : null;
        $stmt->close();
        
        return $cart_id;
    }
    
    /**
     * Merge guest cart to customer cart after login
     */
    public static function bateratu($conn, int $bezero_id): void {
        if (!$conn) return;
        
        $saio_id = session_id();
        
        // Get guest cart
        $stmt = $conn->prepare("SELECT id FROM saskia WHERE saio_id = ? AND bezero_id IS NULL");
        $stmt->bind_param("s", $saio_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $guest_cart = $res->fetch_assoc();
        $stmt->close();
        
        if (!$guest_cart) return;
        
        // Get or create customer cart
        $customer_cart_id = self::lortuEdoSortu($conn, $bezero_id);
        if (!$customer_cart_id) return;
        
        // Move items from guest cart to customer cart
        $stmt = $conn->prepare("UPDATE saskia_item SET saskia_id = ? WHERE saskia_id = ?");
        $stmt->bind_param("ii", $customer_cart_id, $guest_cart['id']);
        $stmt->execute();
        $stmt->close();
        
        // Delete guest cart
        $stmt = $conn->prepare("DELETE FROM saskia WHERE id = ?");
        $stmt->bind_param("i", $guest_cart['id']);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Add product to cart
     */
    public static function gehituProduktua($conn, int $saskia_id, int $produktu_id, int $kantitatea = 1): bool {
        if (!$conn || $kantitatea < 1) return false;
        
        // Get product price and check stock
        $stmt = $conn->prepare("SELECT prezioa, stock FROM produktua WHERE id = ?");
        $stmt->bind_param("i", $produktu_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $produktua = $res->fetch_assoc();
        $stmt->close();
        
        if (!$produktua || $produktua['stock'] < $kantitatea) {
            return false;
        }
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, kantitatea FROM saskia_item WHERE saskia_id = ? AND produktu_id = ?");
        $stmt->bind_param("ii", $saskia_id, $produktu_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update quantity
            $new_qty = $existing['kantitatea'] + $kantitatea;
            if ($new_qty > $produktua['stock']) {
                $new_qty = $produktua['stock'];
            }
            $stmt = $conn->prepare("UPDATE saskia_item SET kantitatea = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_qty, $existing['id']);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO saskia_item (saskia_id, produktu_id, kantitatea, prezioa_unitarioa) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $saskia_id, $produktu_id, $kantitatea, $produktua['prezioa']);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }
    }
    
    /**
     * Update item quantity
     */
    public static function eguneratuKantitatea($conn, int $saskia_id, int $produktu_id, int $kantitatea): bool {
        if (!$conn) return false;
        
        if ($kantitatea <= 0) {
            return self::kenduProduktua($conn, $saskia_id, $produktu_id);
        }
        
        // Check stock
        $stmt = $conn->prepare("SELECT stock FROM produktua WHERE id = ?");
        $stmt->bind_param("i", $produktu_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $produktua = $res->fetch_assoc();
        $stmt->close();
        
        if (!$produktua || $kantitatea > $produktua['stock']) {
            return false;
        }
        
        $stmt = $conn->prepare("UPDATE saskia_item SET kantitatea = ? WHERE saskia_id = ? AND produktu_id = ?");
        $stmt->bind_param("iii", $kantitatea, $saskia_id, $produktu_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    
    /**
     * Remove product from cart
     */
    public static function kenduProduktua($conn, int $saskia_id, int $produktu_id): bool {
        if (!$conn) return false;
        
        $stmt = $conn->prepare("DELETE FROM saskia_item WHERE saskia_id = ? AND produktu_id = ?");
        $stmt->bind_param("ii", $saskia_id, $produktu_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    
    /**
     * Get cart items with product details
     */
    public static function lortuItemak($conn, int $saskia_id): array {
        if (!$conn) return [];
        
        $stmt = $conn->prepare("
            SELECT si.id, si.produktu_id, si.kantitatea, si.prezioa_unitarioa,
                   p.izena, p.deskripzioa, p.kategoria, p.stock, p.irudia,
                   (si.kantitatea * si.prezioa_unitarioa) as azpi_totala
            FROM saskia_item si
            JOIN produktua p ON si.produktu_id = p.id
            WHERE si.saskia_id = ?
            ORDER BY si.created_at DESC
        ");
        $stmt->bind_param("i", $saskia_id);
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
     * Get cart total
     */
    public static function lortuTotala($conn, int $saskia_id): float {
        if (!$conn) return 0.0;
        
        $stmt = $conn->prepare("
            SELECT SUM(kantitatea * prezioa_unitarioa) as totala
            FROM saskia_item
            WHERE saskia_id = ?
        ");
        $stmt->bind_param("i", $saskia_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return (float)($row['totala'] ?? 0);
    }
    
    /**
     * Get cart item count
     */
    public static function lortuKopurua($conn, int $saskia_id): int {
        if (!$conn) return 0;
        
        $stmt = $conn->prepare("SELECT SUM(kantitatea) as kopurua FROM saskia_item WHERE saskia_id = ?");
        $stmt->bind_param("i", $saskia_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return (int)($row['kopurua'] ?? 0);
    }
    
    /**
     * Clear cart
     */
    public static function hutsitu($conn, int $saskia_id): bool {
        if (!$conn) return false;
        
        $stmt = $conn->prepare("DELETE FROM saskia_item WHERE saskia_id = ?");
        $stmt->bind_param("i", $saskia_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
