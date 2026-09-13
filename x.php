<?php
require_once 'config.php';

// Mevcut AdSense kodunu göster
$currentCode = $siteSettings->get('adsense_code');
echo "<h2>Current AdSense Code:</h2>";
echo "<pre>" . htmlspecialchars(substr($currentCode, 0, 1000)) . "</pre>";
echo "<p><strong>Total length:</strong> " . strlen($currentCode) . " characters</p>";

echo "<hr>";

// AdSense kodunu temizle
if (isset($_POST['clear_adsense'])) {
    $siteSettings->set('adsense_code', '');
    echo "<div style='background: #4CAF50; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>AdSense code has been cleared!</p>";
    echo "<p><a href='index.php' style='color: white; text-decoration: underline;'>Go to homepage to check</a></p>";
    echo "</div>";
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='clear_adsense' style='background: #f44336; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>CLEAR ADSENSE CODE (Fix the duplicate issue)</button>";
    echo "</form>";
}
?>
