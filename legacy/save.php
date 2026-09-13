<?php
// CORS başlıkları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 saat

// OPTIONS isteği için uygun yanıt
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Veritabanı bağlantısı (PDO)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=x_transaction_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı bağlantısı başarısız: ' . $e->getMessage()]);
    exit;
}

// Gelen POST verisini al
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['transaction_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Eksik parametre: transaction_id']);
    exit;
}

$transactionId = $data['transaction_id'];

// 5 dakikadan eski kayıtları sil
$deleteQuery = "DELETE FROM transactions WHERE created_at < (NOW() - INTERVAL 5 MINUTE)";
$pdo->exec($deleteQuery);

// Yeni kaydı ekle
$insertQuery = "INSERT INTO transactions (transaction_id) VALUES (:transaction_id)";
$stmt = $pdo->prepare($insertQuery);
$stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_STR);

try {
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Kayıt başarılı']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Kayıt sırasında hata oluştu: ' . $e->getMessage()]);
}
?>