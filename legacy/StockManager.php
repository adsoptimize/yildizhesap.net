<?php
/**
 * Hesap Stok Yönetim Sistemi
 * Her ürün için benzersiz hesap satırları yönetir
 */
class StockManager {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Hesaba toplu stok ekleme
     * @param int $accountId Hesap ID
     * @param array $stockData Hesap bilgileri array'i - her satır düz metin olarak hesap bilgisi
     * @return array Sonuç
     */
    public function addBulkStock($accountId, $stockData) {
        try {
            $this->pdo->beginTransaction();
            
            $addedCount = 0;
            $skippedCount = 0;
            $stmt = $this->pdo->prepare("INSERT INTO account_stock (account_id, username, password) VALUES (?, ?, '')");
            
            foreach ($stockData as $data) {
                $data = trim($data);
                
                // Boş satırları atla
                if (empty($data)) {
                    $skippedCount++;
                    continue;
                }
                
                // Her satırı düz metin olarak hesap bilgisi kabul et
                $accountInfo = $data;
                
                // Zaten var mı kontrol et
                if ($this->isDuplicateStock($accountId, $accountInfo, '')) {
                    $skippedCount++;
                    continue;
                }
                
                $stmt->execute([$accountId, $accountInfo]);
                $addedCount++;
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'added' => $addedCount,
                'skipped' => $skippedCount,
                'message' => "$addedCount hesap eklendi, $skippedCount atlandı."
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Stok ekleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Tek hesap ekleme
     */
    public function addSingleStock($accountId, $accountData) {
        $accountData = trim($accountData);
        
        if (empty($accountData)) {
            return ['success' => false, 'message' => 'Hesap bilgisi boş olamaz.'];
        }
        
        // Düz metin olarak hesap bilgisini kabul et
        $accountInfo = $accountData;
        
        if ($this->isDuplicateStock($accountId, $accountInfo, '')) {
            return ['success' => false, 'message' => 'Bu hesap zaten mevcut.'];
        }
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO account_stock (account_id, username, password) VALUES (?, ?, '')");
            $stmt->execute([$accountId, $accountInfo]);
            
            return ['success' => true, 'message' => 'Hesap başarıyla eklendi.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Stok listesini getir
     */
    public function getStockList($accountId, $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        try {
            // Toplam sayı
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM account_stock WHERE account_id = ?");
            $countStmt->execute([$accountId]);
            $total = $countStmt->fetchColumn();
            
            // Stok listesi
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.username as buyer_username, u.email as buyer_email,
                       s.username as account_data
                FROM account_stock s
                LEFT JOIN users u ON s.sold_to_user_id = u.id
                WHERE s.account_id = ?
                ORDER BY s.is_sold ASC, s.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$accountId, $limit, $offset]);
            $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'stocks' => $stocks,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Mevcut stok sayılarını getir
     */
    public function getStockCounts($accountId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_sold = 0 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN is_sold = 1 THEN 1 ELSE 0 END) as sold
                FROM account_stock 
                WHERE account_id = ?
            ");
            $stmt->execute([$accountId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['total' => 0, 'available' => 0, 'sold' => 0];
        }
    }
    
    /**
     * Satın alma işlemi - hesap rezerve etme
     */
    public function reserveAccounts($accountId, $quantity, $userId, $orderId) {
        try {
            $this->pdo->beginTransaction();
            
            // Mevcut stoğu kontrol et
            $availableStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM account_stock 
                WHERE account_id = ? AND is_sold = 0
            ");
            $availableStmt->execute([$accountId]);
            $available = $availableStmt->fetchColumn();
            
            if ($available < $quantity) {
                throw new Exception("Yetersiz stok! Mevcut: $available, İstenen: $quantity");
            }
            
            // Hesapları rezerve et
            $reserveStmt = $this->pdo->prepare("
                UPDATE account_stock 
                SET is_sold = 1, sold_to_user_id = ?, sold_at = NOW(), order_id = ?
                WHERE account_id = ? AND is_sold = 0
                ORDER BY created_at ASC
                LIMIT ?
            ");
            $reserveStmt->execute([$userId, $orderId, $accountId, $quantity]);
            
            // Rezerve edilen hesapları getir
            $getAccountsStmt = $this->pdo->prepare("
                SELECT username as account_data FROM account_stock
                WHERE account_id = ? AND sold_to_user_id = ? AND order_id = ? AND is_sold = 1
                ORDER BY sold_at DESC
                LIMIT ?
            ");
            $getAccountsStmt->execute([$accountId, $userId, $orderId, $quantity]);
            $accounts = $getAccountsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'accounts' => $accounts,
                'message' => "$quantity hesap başarıyla rezerve edildi."
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Stok düzenleme
     */
    public function updateStock($stockId, $accountData) {
        $accountData = trim($accountData);
        
        if (empty($accountData)) {
            return ['success' => false, 'message' => 'Hesap bilgisi boş olamaz.'];
        }
        
        // Düz metin olarak hesap bilgisini kabul et
        $accountInfo = $accountData;
        
        try {
            $stmt = $this->pdo->prepare("UPDATE account_stock SET username = ?, password = '' WHERE id = ? AND is_sold = 0");
            $stmt->execute([$accountInfo, $stockId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Stok güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Stok güncellenemedi veya zaten satılmış.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Stok silme
     */
    public function deleteStock($stockId) {
        try {
            // Satılmış mı kontrol et
            $checkStmt = $this->pdo->prepare("SELECT is_sold FROM account_stock WHERE id = ?");
            $checkStmt->execute([$stockId]);
            $stock = $checkStmt->fetch();
            
            if (!$stock) {
                return ['success' => false, 'message' => 'Stok bulunamadı.'];
            }
            
            if ($stock['is_sold']) {
                return ['success' => false, 'message' => 'Satılmış stok silinemez.'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM account_stock WHERE id = ?");
            $stmt->execute([$stockId]);
            
            return ['success' => true, 'message' => 'Stok silindi.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Boş stokları temizle
     */
    public function cleanEmptyStock($accountId = null) {
        try {
            $sql = "DELETE FROM account_stock WHERE (username IS NULL OR username = '' OR TRIM(username) = '') AND is_sold = 0";
            $params = [];
            
            if ($accountId) {
                $sql .= " AND account_id = ?";
                $params[] = $accountId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $deletedCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'deleted' => $deletedCount,
                'message' => "$deletedCount boş stok temizlendi."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Duplicate kontrol
     */
    private function isDuplicateStock($accountId, $username, $password) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM account_stock WHERE account_id = ? AND username = ? AND password = ?");
        $stmt->execute([$accountId, $username, $password]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Tüm ürünlerin gerçek stok sayılarını getir
     */
    public function getAllAccountsWithStock() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    a.*,
                    COALESCE(stock_counts.total, 0) as real_total_stock,
                    COALESCE(stock_counts.available, 0) as real_available_stock,
                    COALESCE(stock_counts.sold, 0) as real_sold_stock
                FROM accounts a
                LEFT JOIN (
                    SELECT 
                        account_id,
                        COUNT(*) as total,
                        SUM(CASE WHEN is_sold = 0 THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN is_sold = 1 THEN 1 ELSE 0 END) as sold
                    FROM account_stock
                    GROUP BY account_id
                ) stock_counts ON a.id = stock_counts.account_id
                ORDER BY a.created_at DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
