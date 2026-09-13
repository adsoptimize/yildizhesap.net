<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SELECT order_id, amount, currency FROM crypto_payments ORDER BY created_at DESC LIMIT 5');
    echo "Mevcut siparişler:\n";
    while($row = $stmt->fetch()) {
        echo $row['order_id'] . ' - ' . $row['amount'] . ' ' . $row['currency'] . "\n";
    }
} catch(Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>