<?php
class TicketManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Kullanıcının belirli bir sipariş için ticket'ı var mı kontrol et
     */
    public function hasTicketForOrder($userId, $orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM support_tickets 
                WHERE user_id = :user_id AND order_id = :order_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("TicketManager::hasTicketForOrder Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Yeni ticket oluştur
     */
    public function createTicket($userId, $orderId, $subject, $message, $priority = 'medium') {
        try {
            // Önce bu sipariş için ticket var mı kontrol et
            if ($this->hasTicketForOrder($userId, $orderId)) {
                return ['success' => false, 'message' => 'Bu sipariş için zaten bir destek talebi oluşturulmuş.'];
            }
            
            // Siparişin kullanıcıya ait olduğunu kontrol et
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_id = :order_id AND user_id = :user_id");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                return ['success' => false, 'message' => 'Geçersiz sipariş.'];
            }
            
            $this->pdo->beginTransaction();
            
            // Ticket ID oluştur
            $ticketId = 'TK' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Ticket oluştur
            $stmt = $this->pdo->prepare("
                INSERT INTO support_tickets (
                    ticket_id, user_id, order_id, subject, message, priority,
                    last_reply_at, last_reply_by
                ) VALUES (
                    :ticket_id, :user_id, :order_id, :subject, :message, :priority,
                    NOW(), 'customer'
                )
            ");
            
            $stmt->execute([
                ':ticket_id' => $ticketId,
                ':user_id' => $userId,
                ':order_id' => $orderId,
                ':subject' => $subject,
                ':message' => $message,
                ':priority' => $priority
            ]);
            
            // İlk mesajı ekle
            $stmt = $this->pdo->prepare("
                INSERT INTO ticket_replies (
                    ticket_id, user_id, message, is_admin_reply
                ) VALUES (
                    :ticket_id, :user_id, :message, 0
                )
            ");
            
            $stmt->execute([
                ':ticket_id' => $ticketId,
                ':user_id' => $userId,
                ':message' => $message
            ]);
            
            $this->pdo->commit();
            
            return ['success' => true, 'ticket_id' => $ticketId, 'message' => 'Destek talebi başarıyla oluşturuldu.'];
            
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("TicketManager::createTicket Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Destek talebi oluşturulurken hata oluştu.'];
        }
    }
    
    /**
     * Kullanıcının ticket'larını getir
     */
    public function getUserTickets($userId, $limit = null, $offset = 0) {
        try {
            $sql = "
                SELECT 
                    t.ticket_id,
                    t.order_id,
                    t.subject,
                    t.status,
                    t.priority,
                    t.created_at,
                    t.last_reply_at,
                    t.last_reply_by,
                    o.product_name
                FROM support_tickets t
                INNER JOIN orders o ON t.order_id = o.order_id
                WHERE t.user_id = :user_id
                ORDER BY t.created_at DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TicketManager::getUserTickets Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ticket detaylarını getir
     */
    public function getTicketDetails($ticketId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    t.*,
                    o.product_name,
                    u.email as user_email
                FROM support_tickets t
                INNER JOIN orders o ON t.order_id = o.order_id
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.ticket_id = :ticket_id AND t.user_id = :user_id
            ");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TicketManager::getTicketDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ticket yanıtlarını getir
     */
    public function getTicketReplies($ticketId, $userId) {
        try {
            // Önce ticket'ın kullanıcıya ait olduğunu kontrol et
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE ticket_id = :ticket_id AND user_id = :user_id");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.*,
                    u.email as user_email
                FROM ticket_replies r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = :ticket_id
                ORDER BY r.created_at ASC
            ");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TicketManager::getTicketReplies Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ticket'a yanıt ekle
     */
    public function addReply($ticketId, $userId, $message) {
        try {
            // Ticket'ın durumunu ve son yanıtı kontrol et
            $stmt = $this->pdo->prepare("
                SELECT status, last_reply_by 
                FROM support_tickets 
                WHERE ticket_id = :ticket_id AND user_id = :user_id
            ");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return ['success' => false, 'message' => 'Ticket bulunamadı.'];
            }
            
            if ($ticket['status'] === 'closed') {
                return ['success' => false, 'message' => 'Kapalı ticket\'lara yanıt verilemez.'];
            }
            
            // Kullanıcı arka arkaya mesaj göndermeyi engelle (sadece admin yanıt verdiyse veya ilk mesajsa izin ver)
            if ($ticket['last_reply_by'] === 'customer') {
                return ['success' => false, 'message' => 'Destek ekibimizden yanıt gelmeden yeni mesaj gönderemezsiniz.'];
            }
            
            $this->pdo->beginTransaction();
            
            // Yanıt ekle
            $stmt = $this->pdo->prepare("
                INSERT INTO ticket_replies (
                    ticket_id, user_id, message, is_admin_reply
                ) VALUES (
                    :ticket_id, :user_id, :message, 0
                )
            ");
            
            $stmt->execute([
                ':ticket_id' => $ticketId,
                ':user_id' => $userId,
                ':message' => $message
            ]);
            
            // Ticket'ı güncelle
            $stmt = $this->pdo->prepare("
                UPDATE support_tickets 
                SET last_reply_at = NOW(), last_reply_by = 'customer', 
                    status = CASE WHEN status = 'resolved' THEN 'open' ELSE status END
                WHERE ticket_id = :ticket_id
            ");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->execute();
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Yanıtınız başarıyla gönderildi.'];
            
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("TicketManager::addReply Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Yanıt gönderilirken hata oluştu.'];
        }
    }
    
    /**
     * Kullanıcının ticket istatistiklerini getir
     */
    public function getUserTicketStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets
                FROM support_tickets 
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TicketManager::getUserTicketStats Error: " . $e->getMessage());
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'in_progress_tickets' => 0,
                'resolved_tickets' => 0,
                'closed_tickets' => 0
            ];
        }
    }
    
    /**
     * Sipariş için ticket ve okunmamış admin mesaj sayısını getir
     */
    public function getOrderTicketInfo($userId, $orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    t.ticket_id,
                    t.subject,
                    t.status,
                    t.last_reply_by,
                    (
                        SELECT COUNT(*) 
                        FROM ticket_replies r 
                        WHERE r.ticket_id = t.ticket_id AND r.is_admin_reply = 1
                    ) as admin_replies_count
                FROM support_tickets t
                WHERE t.user_id = :user_id AND t.order_id = :order_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TicketManager::getOrderTicketInfo Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Kullanıcının siparişe erişim hakkı var mı kontrol et
     */
    public function canUserAccessOrder($userId, $orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM orders 
                WHERE order_id = :order_id AND user_id = :user_id
            ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("TicketManager::canUserAccessOrder Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının mesaj yazmasına izin var mı kontrol et
     */
    public function canUserReply($ticketId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT status, last_reply_by 
                FROM support_tickets 
                WHERE ticket_id = :ticket_id AND user_id = :user_id
            ");
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return false;
            }
            
            // Kapalı ticket'lara yanıt verilemez
            if ($ticket['status'] === 'closed') {
                return false;
            }
            
            // Admin son yanıt verdiyse VEYA henüz hiç yanıt yoksa kullanıcı yanıt verebilir
            // Eğer kullanıcı son yanıt verdiyse admin yanıtını beklemeli
            return $ticket['last_reply_by'] === 'admin' || $ticket['last_reply_by'] === null;
            
        } catch (PDOException $e) {
            error_log("TicketManager::canUserReply Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
