<?php

// Simple test script to test the email API endpoint
$url = 'http://localhost:8000/api/emails/patient-invitation';
$data = [
    'patient_name' => 'Test Patient',
    'patient_email' => 'grant88271@gmail.com',
    'portal_url' => 'https://yourapp.com/login',
    'temporary_password' => 'temp123',
    'clinic_name' => 'Test Clinic',
    'business_name' => 'Safe Haven Restoration Ministries',
    'contact_email' => 'info@safehavenrestorationministries.com'
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error: Failed to send request\n";
    if (isset($http_response_header)) {
        echo "Response headers:\n";
        foreach ($http_response_header as $header) {
            echo $header . "\n";
        }
    }
} else {
    echo "Success! Response:\n";
    echo $result . "\n";
}

// Also print response headers
if (isset($http_response_header)) {
    echo "\nResponse Headers:\n";
    foreach ($http_response_header as $header) {
        echo $header . "\n";
    }
}
?>
