<?php
/**
 * Endpoint de Contacto - Plantilla Web Intelligis
 * Compatible con cPanel / Apache en hosting compartido (GoDaddy)
 * Permite recibir formularios estáticos de Astro y procesarlos vía PHP
 */

header('Content-Type: application/json; charset=utf-8');

// Cabeceras CORS para peticiones locales o de dominio
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Obtener datos (JSON o POST tradicional)
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if (!is_array($inputData)) {
    $inputData = $_POST;
}

$recaptchaToken = $inputData['recaptchaToken'] ?? $inputData['g-recaptcha-response'] ?? '';
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '';

// 1. Verificación opcional de reCAPTCHA si está configurado el Secret Key
if (!empty($recaptchaSecret)) {
    if (empty($recaptchaToken)) {
        http_response_code(400);
        echo json_encode(['error' => 'reCAPTCHA token is required']);
        exit;
    }

    $verifyCh = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($verifyCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($verifyCh, CURLOPT_POST, true);
    curl_setopt($verifyCh, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $recaptchaSecret,
        'response' => $recaptchaToken,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]));
    curl_setopt($verifyCh, CURLOPT_TIMEOUT, 10);
    $verifyResponse = curl_exec($verifyCh);
    curl_close($verifyCh);

    $verifyResult = json_decode($verifyResponse, true);

    if (!isset($verifyResult['success']) || !$verifyResult['success']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'reCAPTCHA verification failed',
            'details' => $verifyResult['error-codes'] ?? []
        ]);
        exit;
    }
}

// 2. Extraer campos del formulario
$name = trim($inputData['name'] ?? $inputData['First_Name'] ?? '');
$email = trim($inputData['email'] ?? $inputData['Email'] ?? '');
$message = trim($inputData['message'] ?? $inputData['Description'] ?? '');

if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and Email are required fields']);
    exit;
}

// 3. Reenvío opcional a Webhook o Web-to-Lead (configurable por entorno)
$webhookUrl = getenv('CONTACT_WEBHOOK_URL') ?: '';

if (!empty($webhookUrl)) {
    $wh = curl_init($webhookUrl);
    curl_setopt($wh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($wh, CURLOPT_POST, true);
    curl_setopt($wh, CURLOPT_POSTFIELDS, http_build_query($inputData));
    curl_setopt($wh, CURLOPT_TIMEOUT, 15);
    $whResponse = curl_exec($wh);
    $whStatus = curl_getinfo($wh, CURLINFO_HTTP_CODE);
    curl_close($wh);
}

// Respuesta exitosa estándar
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Message received successfully'
]);
