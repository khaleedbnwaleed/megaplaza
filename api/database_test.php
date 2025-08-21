<?php
/**
 * Database Connection Test
 * Test the database connection and display basic info
 */

require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Test query to get basic stats
        $stats = [];
        
        // Count users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $stats['users'] = $stmt->fetch()['count'];
        
        // Count shops
        $stmt = $db->query("SELECT COUNT(*) as count FROM shops");
        $stats['shops'] = $stmt->fetch()['count'];
        
        // Count applications
        $stmt = $db->query("SELECT COUNT(*) as count FROM applications");
        $stats['applications'] = $stmt->fetch()['count'];
        
        // Count active leases
        $stmt = $db->query("SELECT COUNT(*) as count FROM leases WHERE status = 'active'");
        $stats['active_leases'] = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'stats' => $stats
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to connect to database'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
