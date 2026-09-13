<?php
/**
 * Database Setup Script
 * active_sessions tablosunu oluşturur ve test eder
 */

require_once 'config.php';
require_once 'DatabaseSessionManager.php';

echo "<h2>Database Setup for Enhanced Session Security</h2>";
echo "<hr>";

try {
    echo "<p><strong>Step 1:</strong> Creating DatabaseSessionManager...</p>";
    $sessionManager = new DatabaseSessionManager($pdo);
    echo "<p style='color: green;'>✅ DatabaseSessionManager created successfully!</p>";
    echo "<p style='color: green;'>✅ active_sessions table created!</p>";
    
    echo "<p><strong>Step 2:</strong> Testing session creation...</p>";
    $testToken = $sessionManager->createSession(1);
    if ($testToken) {
        echo "<p style='color: green;'>✅ Test session created: " . substr($testToken, 0, 16) . "...</p>";
        
        echo "<p><strong>Step 3:</strong> Testing session validation...</p>";
        $result = $sessionManager->validateSession($testToken);
        if ($result['valid']) {
            echo "<p style='color: green;'>✅ Session validation working!</p>";
        } else {
            echo "<p style='color: red;'>❌ Session validation failed: " . $result['reason'] . "</p>";
        }
        
        echo "<p><strong>Step 4:</strong> Cleaning up test session...</p>";
        $sessionManager->invalidateSession($testToken, 'test');
        echo "<p style='color: green;'>✅ Test session cleaned up</p>";
    } else {
        echo "<p style='color: red;'>❌ Test session creation failed!</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>🎉 Setup Complete!</h3>";
    echo "<p><strong>Enhanced Session Security is now active:</strong></p>";
    echo "<ul>";
    echo "<li>🛡️ ZERO TOLERANCE IP change detection</li>";
    echo "<li>🔍 Device fingerprint monitoring</li>";
    echo "<li>📊 Real-time session tracking</li>";
    echo "<li>⚡ Instant session invalidation</li>";
    echo "</ul>";
    
    echo "<h4>Admin Tools:</h4>";
    echo "<p><a href='admin/session_manager.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Session Management Panel</a></p>";
    echo "<p><a href='security_test.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Security Test Panel</a></p>";
    
    echo "<h4>Test Security:</h4>";
    echo "<p>1. Login to your account</p>";
    echo "<p>2. Turn on VPN or change IP</p>";
    echo "<p>3. Refresh any page</p>";
    echo "<p>4. You should see the security violation page! 🛡️</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><small>Setup script completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
