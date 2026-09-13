<?php
require_once 'config.php';

try {
    // TK002'ye admin yanıtı ekle
    $pdo->exec("INSERT INTO ticket_replies (ticket_id, admin_id, message, is_admin_reply) VALUES ('TK002', 1, 'Merhaba, siparişinizi kontrol ediyoruz. En kısa sürede geri dönüş yapacağız.', 1)");
    
    // Ticket'ı güncelle
    $pdo->exec("UPDATE support_tickets SET last_reply_at = NOW(), last_reply_by = 'admin', status = 'in_progress' WHERE ticket_id = 'TK002'");
    
    echo "✅ TK002 ticket'ına admin yanıtı eklendi!\n";
    
    // Kontrol et
    $stmt = $pdo->query("SELECT ticket_id, subject, last_reply_by, status FROM support_tickets WHERE ticket_id IN ('TK001', 'TK002')");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Güncel Ticket Durumları:\n";
    foreach ($tickets as $ticket) {
        echo "- {$ticket['ticket_id']}: {$ticket['subject']} - Son yanıt: {$ticket['last_reply_by']} - Durum: {$ticket['status']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
