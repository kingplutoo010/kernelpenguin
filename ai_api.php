<?php
session_start();
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'config.php'; // Includes your DB connection and .env variables

// --- IMPORTANT: CHOOSE YOUR AI API PROVIDER ---
// For Google Gemini API:
// Define the API endpoint and your API key from .env
// const AI_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=';
// const AI_MODEL_NAME = 'gemini-pro'; // Or other model like 'gemini-1.5-flash', 'gemini-1.5-pro'

// For OpenAI API:
const AI_API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
const AI_MODEL_NAME = 'gpt-3.5-turbo'; // Or 'gpt-4', 'gpt-4o' etc.
// --- END IMPORTANT ---


try {
    // Get raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $userQuery = trim($input['query'] ?? '');

    if (empty($userQuery)) {
        echo json_encode(['error' => 'Query cannot be empty.']);
        exit();
    }

    // Load AI API Key from config.php (which gets it from .env)
    // Make sure you add 'AI_API_KEY' to your .env file
    $aiApiKey = $env['AI_API_KEY'] ?? null;

    if (!$aiApiKey) {
        error_log("AI API Key not set in .env");
        echo json_encode(['error' => 'AI API Key not configured on the server.']);
        exit();
    }

    // Prepare the request body for the AI API
    $requestBody = [];
    $headers = [];

    // --- IMPORTANT: API REQUEST BODY & HEADERS DEPEND ON YOUR CHOSEN AI API ---
    // Example for OpenAI Chat Completions API:
    $requestBody = [
        'model' => AI_MODEL_NAME,
        'messages' => [
            ['role' => 'user', 'content' => $userQuery]
        ],
        'max_tokens' => 500, // Limit response length
        'temperature' => 0.7 // Creativity level
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $aiApiKey
    ];
    // --- END IMPORTANT ---


    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, AI_API_ENDPOINT);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        error_log("cURL Error: " . $error_msg);
        echo json_encode(['error' => 'Failed to reach AI service: ' . $error_msg]);
        exit();
    }
    curl_close($ch);

    // Decode the AI API's response
    $responseData = json_decode($response, true);

    if ($httpCode !== 200) {
        error_log("AI API returned HTTP $httpCode: " . $response);
        echo json_encode(['error' => 'AI service error: ' . ($responseData['error']['message'] ?? 'Unknown API error')]);
        exit();
    }

    // --- IMPORTANT: PARSE AI RESPONSE BASED ON YOUR CHOSEN AI API ---
    // Example for OpenAI Chat Completions API:
    $aiResponseText = $responseData['choices'][0]['message']['content'] ?? 'No response from AI.';
    // --- END IMPORTANT ---

    echo json_encode(['success' => true, 'response' => $aiResponseText]);

} catch (Exception $e) {
    error_log("AI Browser PHP error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error.']);
}
?>