<?php

// Test patient registration - PERFECT test
$data = json_encode([
    'email' => 'perfect1761178394388@example.com',
    'token' => 'perfect-test-token-1761178394281',
    'first_name' => 'Perfect',
    'last_name' => 'Test',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'phone' => '555-123-4567',
    'date_of_birth' => '1990-01-01'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data,
        'ignore_errors' => true
    ]
]);

$result = file_get_contents('http://localhost:8000/api/patient-invitations/complete', false, $context);

if ($result !== false) {
    $decoded = json_decode($result, true);
    echo "Response received:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($decoded['success']) && $decoded['success']) {
        echo "✅ REGISTRATION SUCCESS!\n";
    } else {
        echo "❌ Registration failed\n";
    }
} else {
    echo "Registration failed - connection error\n";
}

if (isset($http_response_header)) {
    echo "\nResponse headers:\n";
    foreach ($http_response_header as $header) {
        echo "  $header\n";
    }
}