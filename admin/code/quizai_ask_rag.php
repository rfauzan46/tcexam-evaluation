<?php
// Read the incoming JSON request
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Check if query is provided
if (empty($data['query'])) {
    echo json_encode(['error' => 'Query not provided']);
    exit;
}

// Prepare data to be sent to /ask_rag
$ask_rag_api_url = 'http://localhost:19645/ask_rag';

// Add file path to data if provided
$query_data = ['query' => $data['query']];
if (!empty($data['file_path'])) {
    $query_data['file_path'] = $data['file_path'];
}

// Initialize cURL session for /ask_rag request
$ch = curl_init();

// Set cURL options for /ask_rag request
curl_setopt($ch, CURLOPT_URL, $ask_rag_api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_data)); // Convert data array to JSON
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Set content type to JSON

// Execute cURL request for /ask_rag
$response = curl_exec($ch);

// Check for errors in /ask_rag request
if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

// Close cURL session for /ask_rag request
curl_close($ch);

// Process /ask_rag API response
if ($response) {
    // Decode JSON response
    $response_data = json_decode($response, true);
    echo json_encode($response_data);
} else {
    echo json_encode(['error' => 'No response from the API']);
}
?>
