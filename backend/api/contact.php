<?php
/**
 * Endpoint de Contacto - Plantilla Web Intelligis
 * Compatible con cPanel / Apache en hosting compartido (GoDaddy)
 * Permite recibir formularios estáticos y procesarlos con reCAPTCHA y Zoho CRM / Webhook
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

// 1. Verificación obligatoria de reCAPTCHA si está configurado el Secret Key
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
$name = trim($inputData['name'] ?? (($inputData['First_Name'] ?? '') . ' ' . ($inputData['Last_Name'] ?? '')));
$email = trim($inputData['email'] ?? $inputData['Email'] ?? '');
$message = trim($inputData['message'] ?? $inputData['Description'] ?? '');

if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and Email are required fields']);
    exit;
}

// 3. Reenvío a Zoho CRM si está configurado (variables ZOHO_CRM_XNQSJSDP y ZOHO_CRM_XMIWTLD)
$zohoXnQsjsdp = getenv('ZOHO_CRM_XNQSJSDP') ?: '';
$zohoXmIwtLD  = getenv('ZOHO_CRM_XMIWTLD') ?: '';

if (!empty($zohoXnQsjsdp) && !empty($zohoXmIwtLD)) {
    $zohoData = [
        'xnQsjsdp'    => $zohoXnQsjsdp,
        'xmIwtLD'     => $zohoXmIwtLD,
        'actionType'  => 'TGVhZHM=',
        'returnURL'   => 'null',
        'zc_gad'      => $inputData['zc_gad'] ?? '',
        'aG9uZXlwb3Q' => '',
        
        'First Name'  => $inputData['First_Name'] ?? $inputData['firstName'] ?? $name,
        'Last Name'   => $inputData['Last_Name'] ?? $inputData['lastName'] ?? '',
        'Email'       => $email,
        'Company'     => $inputData['Company'] ?? $inputData['company'] ?? '',
        'Mobile'      => $inputData['Mobile'] ?? $inputData['phone'] ?? '',
        'LEADCF2'     => $inputData['LEADCF2'] ?? $inputData['country'] ?? '-None-',
        'Description' => $message,
        
        'LEADCF14'    => getenv('ZOHO_CRM_LEADCF14') ?: ($inputData['LEADCF14'] ?? 'SmartMaps Pro'),
        'Lead Status' => $inputData['Lead_Status'] ?? 'NO CONTACTADO',
        'Lead Source' => $inputData['Lead_Source'] ?? 'Pag, WEB smartmaps',
        'service'     => getenv('ZOHO_CRM_SERVICE') ?: ($inputData['service'] ?? 'smarturl')
    ];

    $zohoCh = curl_init('https://crm.zoho.com/crm/WebToLeadForm');
    curl_setopt($zohoCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($zohoCh, CURLOPT_POST, true);
    curl_setopt($zohoCh, CURLOPT_POSTFIELDS, http_build_query($zohoData));
    curl_setopt($zohoCh, CURLOPT_TIMEOUT, 15);
    curl_setopt($zohoCh, CURLOPT_USERAGENT, 'Intelligis-WebToLead/1.0');
    $zohoResponse = curl_exec($zohoCh);
    $zohoHttpStatus = curl_getinfo($zohoCh, CURLINFO_HTTP_CODE);
    curl_close($zohoCh);

    if ($zohoHttpStatus < 200 || $zohoHttpStatus >= 400) {
        http_response_code(502);
        echo json_encode([
            'error' => 'Zoho CRM request failed',
            'status' => $zohoHttpStatus
        ]);
        exit;
    }
}

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

// Respuesta exitosa estándar
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Message received successfully'
]);
