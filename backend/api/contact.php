<?php
/**
 * Endpoint de Contacto con verificación de Google reCAPTCHA v2 y reenvío a Zoho CRM
 * Compatible con cPanel / Apache en smartmaps360.ai
 */

// Cargar variables desde .env si existe en el directorio backend
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

// Permitir solicitudes CORS si aplica
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

if (empty($recaptchaToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'reCAPTCHA token is required']);
    exit;
}

// 1. Verificar reCAPTCHA con Google (variable o fallback de Smartmaps)
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '6LcUPforAAAAAGh5FlL1FWf8ATnc8t5g90molOnz';

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

// 2. Preparar datos para Zoho CRM (variables o fallbacks oficiales de Smartmaps)
$zohoXnQsjsdp = getenv('ZOHO_CRM_XNQSJSDP') ?: '5e1bc7ca8ff29b70e99dfe39a3191e2c32fc63b7a6770ecc115c3534725ba304';
$zohoXmIwtLD  = getenv('ZOHO_CRM_XMIWTLD') ?: 'b7f63ea911aba54798fb9e0c91813db48ccbc8973c654850e0620eb9fa2b2c9cb5c8dc20c0d46a922b4b63e2b42aca77';
$zohoService  = getenv('ZOHO_CRM_SERVICE') ?: 'smarturl';
$zohoLeadCf14 = getenv('ZOHO_CRM_LEADCF14') ?: 'SmartMaps Pro';

$zohoData = [
    'xnQsjsdp'    => $zohoXnQsjsdp,
    'xmIwtLD'     => $zohoXmIwtLD,
    'actionType'  => 'TGVhZHM=',
    'returnURL'   => 'null',
    'zc_gad'      => $inputData['zc_gad'] ?? '',
    'aG9uZXlwb3Q' => '',
    
    // Campos del prospecto
    'First Name'  => $inputData['First_Name'] ?? $inputData['firstName'] ?? '',
    'Last Name'   => $inputData['Last_Name'] ?? $inputData['lastName'] ?? '',
    'Email'       => $inputData['Email'] ?? $inputData['email'] ?? '',
    'Company'     => $inputData['Company'] ?? $inputData['company'] ?? '',
    'Mobile'      => $inputData['Mobile'] ?? $inputData['phone'] ?? '',
    'LEADCF2'     => $inputData['LEADCF2'] ?? $inputData['country'] ?? '-None-',
    'Description' => $inputData['Description'] ?? $inputData['details'] ?? $inputData['message'] ?? '',
    
    // Campos de segmentación
    'LEADCF14'    => $zohoLeadCf14,
    'Lead Status' => $inputData['Lead_Status'] ?? 'NO CONTACTADO',
    'Lead Source' => $inputData['Lead_Source'] ?? 'Pag, WEB smartmaps',
    'service'     => $zohoService
];

// 3. Enviar a Zoho CRM
$zohoCh = curl_init('https://crm.zoho.com/crm/WebToLeadForm');
curl_setopt($zohoCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($zohoCh, CURLOPT_POST, true);
curl_setopt($zohoCh, CURLOPT_POSTFIELDS, http_build_query($zohoData));
curl_setopt($zohoCh, CURLOPT_TIMEOUT, 15);
curl_setopt($zohoCh, CURLOPT_USERAGENT, 'SmartMaps-WebToLead/1.0');
$zohoResponse = curl_exec($zohoCh);
$zohoHttpStatus = curl_getinfo($zohoCh, CURLINFO_HTTP_CODE);
curl_close($zohoCh);

// 4. Reenvío opcional a Webhook si está configurado
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

// Zoho CRM devuelve 200 o 302 en caso de éxito
if ($zohoHttpStatus >= 200 && $zohoHttpStatus < 400) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lead created successfully in Zoho CRM'
    ]);
} else {
    http_response_code(502);
    echo json_encode([
        'error' => 'Zoho CRM request failed',
        'status' => $zohoHttpStatus
    ]);
}
?>
