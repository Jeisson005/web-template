<?php
/**
 * Renovador Automático de SSL Let's Encrypt para cPanel
 * Diseñado para smartmaps360.ai
 */

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$domain = 'smartmaps360.ai';
$email = 'jeisson.pineros@intelligisgroup.com';
$baseDir = dirname(__DIR__); // /home/usrigwebpage/public_html/smartmaps360.ai
$certsDir = __DIR__ . '/certs';
$challengeDir = $baseDir . '/.well-known/acme-challenge';

if (!file_exists($certsDir)) {
    mkdir($certsDir, 0700, true);
}
if (!file_exists($challengeDir)) {
    mkdir($challengeDir, 0755, true);
}

echo "=== INICIANDO VERIFICACIÓN DE SSL PARA $domain ===\n";

$certFile = $certsDir . '/certificate.crt';
$keyFile = $certsDir . '/private.key';
$caFile = $certsDir . '/ca_bundle.crt';

// Verificar si el certificado actual aún es válido por más de 30 días
if (file_exists($certFile)) {
    $certData = openssl_x509_parse(file_get_contents($certFile));
    if ($certData && isset($certData['validTo_time_t'])) {
        $daysRemaining = round(($certData['validTo_time_t'] - time()) / 86400);
        echo "Días restantes del certificado actual: $daysRemaining días.\n";
        $isForce = isset($_GET['force']) || (isset($argv[1]) && $argv[1] === '--force');
        if ($daysRemaining > 30 && !$isForce) {
            echo "El certificado aún es válido por más de 30 días. No requiere renovación hoy.\n";
            exit(0);
        }
    }
}

echo "Procediendo con la emisión/renovación del certificado con Let's Encrypt...\n";

// Implementación ACME v2 nativa en PHP
function acmeRequest($url, $payload = null, $accountKey = null, $kid = null) {
    global $acmeNonce;
    
    // Obtener nuevo nonce si no existe
    if (!$acmeNonce) {
        $ch = curl_init('https://acme-v02.api.letsencrypt.org/acme/new-nonce');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $res = curl_exec($ch);
        preg_match('/Replay-Nonce:\s*([^\r\n]+)/i', $res, $matches);
        $acmeNonce = trim($matches[1] ?? '');
        curl_close($ch);
    }
    
    if ($payload === null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SmartMaps-ACME/1.0');
        $res = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($res, 0, $headerSize);
        $body = substr($res, $headerSize);
        curl_close($ch);
        preg_match('/Replay-Nonce:\s*([^\r\n]+)/i', $headers, $matches);
        if (!empty($matches[1])) $acmeNonce = trim($matches[1]);
        return ['headers' => $headers, 'body' => json_decode($body, true), 'raw' => $body];
    }
    
    // Firmar JWS
    $header = [
        'alg' => 'RS256',
        'nonce' => $acmeNonce,
        'url' => $url
    ];
    if ($kid) {
        $header['kid'] = $kid;
    } else {
        $pubDetails = openssl_pkey_get_details($accountKey);
        $header['jwk'] = [
            'kty' => 'RSA',
            'n' => rtrim(strtr(base64_encode($pubDetails['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($pubDetails['rsa']['e']), '+/', '-_'), '=')
        ];
    }
    
    $protected64 = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $payload64 = $payload === "" ? "" : rtrim(strtr(base64_encode(is_string($payload) ? $payload : json_encode($payload)), '+/', '-_'), '=');
    $sigData = "$protected64.$payload64";
    
    openssl_sign($sigData, $signature, $accountKey, OPENSSL_ALGO_SHA256);
    $sig64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    $jws = json_encode([
        'protected' => $protected64,
        'payload' => $payload64,
        'signature' => $sig64
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jws);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/jose+json']);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SmartMaps-ACME/1.0');
    $res = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($res, 0, $headerSize);
    $body = substr($res, $headerSize);
    curl_close($ch);
    
    preg_match('/Replay-Nonce:\s*([^\r\n]+)/i', $headers, $matches);
    if (!empty($matches[1])) $acmeNonce = trim($matches[1]);
    
    preg_match('/Location:\s*([^\r\n]+)/i', $headers, $locMatches);
    $location = trim($locMatches[1] ?? '');
    
    return ['headers' => $headers, 'body' => json_decode($body, true), 'raw' => $body, 'location' => $location];
}

$acmeNonce = null;

// Cargar o crear clave de cuenta
$accKeyFile = $certsDir . '/account.key';
if (!file_exists($accKeyFile)) {
    $accKeyRes = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($accKeyRes, $accKeyPem);
    file_put_contents($accKeyFile, $accKeyPem);
} else {
    $accKeyPem = file_get_contents($accKeyFile);
    $accKeyRes = openssl_pkey_get_private($accKeyPem);
}

// 1. Registro de cuenta ACME
$regRes = acmeRequest('https://acme-v02.api.letsencrypt.org/acme/new-acct', [
    'termsOfServiceAgreed' => true,
    'contact' => ["mailto:$email"]
], $accKeyRes);

$kid = $regRes['location'] ?? '';
echo "Cuenta Let's Encrypt lista ($kid).\n";

// 2. Crear nueva orden
$orderRes = acmeRequest('https://acme-v02.api.letsencrypt.org/acme/new-order', [
    'identifiers' => [['type' => 'dns', 'value' => $domain]]
], $accKeyRes, $kid);

$orderUrl = $orderRes['location'];
$authUrl = $orderRes['body']['authorizations'][0];

// 3. Obtener desafío HTTP-01
$authRes = acmeRequest($authUrl, null, $accKeyRes, $kid);
$challenge = null;
foreach ($authRes['body']['challenges'] as $c) {
    if ($c['type'] === 'http-01') {
        $challenge = $c;
        break;
    }
}

$token = $challenge['token'];
$pubDetails = openssl_pkey_get_details($accKeyRes);
$thumbprint = rtrim(strtr(base64_encode(hash('sha256', json_encode([
    'e' => rtrim(strtr(base64_encode($pubDetails['rsa']['e']), '+/', '-_'), '='),
    'kty' => 'RSA',
    'n' => rtrim(strtr(base64_encode($pubDetails['rsa']['n']), '+/', '-_'), '=')
]), true)), '+/', '-_'), '=');

$keyAuth = "$token.$thumbprint";
file_put_contents("$challengeDir/$token", $keyAuth);
@chmod("$challengeDir/$token", 0644);

echo "Desafío HTTP-01 publicado en .well-known/acme-challenge/$token\n";

// 4. Responder desafío
acmeRequest($challenge['url'], new stdClass(), $accKeyRes, $kid);
echo "Validación enviada a Let's Encrypt. Verificando estado...\n";

// Polling de autorización
$authValid = false;
for ($i = 0; $i < 15; $i++) {
    sleep(2);
    $check = acmeRequest($authUrl, null, $accKeyRes, $kid);
    $status = $check['body']['status'] ?? '';
    echo "Estado de autorización: $status\n";
    if ($status === 'valid') {
        $authValid = true;
        break;
    }
    if ($status === 'invalid') {
        echo "Error: Desafío inválido.\n";
        print_r($check['body']);
        exit(1);
    }
}

if (!$authValid) {
    echo "Timeout esperando validación.\n";
    exit(1);
}

// 5. Generar CSR y finalizar orden
$domainKeyRes = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($domainKeyRes, $domainKeyPem);

$csrRes = openssl_csr_new(['commonName' => $domain], $domainKeyRes, ['digest_alg' => 'sha256']);
openssl_csr_export($csrRes, $csrPem);

// Convertir CSR a DER y luego base64url
$csrLines = explode("\n", trim($csrPem));
array_shift($csrLines);
array_pop($csrLines);
$csrDer = base64_decode(implode('', $csrLines));
$csr64 = rtrim(strtr(base64_encode($csrDer), '+/', '-_'), '=');

$finalizeRes = acmeRequest($orderRes['body']['finalize'], ['csr' => $csr64], $accKeyRes, $kid);

// Polling orden finalizada
$certUrl = '';
for ($i = 0; $i < 15; $i++) {
    sleep(2);
    $orderCheck = acmeRequest($orderUrl, null, $accKeyRes, $kid);
    $certUrl = $orderCheck['body']['certificate'] ?? '';
    if ($certUrl) break;
}

if (!$certUrl) {
    echo "No se pudo obtener la URL del certificado.\n";
    exit(1);
}

$certChainRes = acmeRequest($certUrl, null, $accKeyRes, $kid);
$fullChain = $certChainRes['raw'];

// Separar hoja y bundle
$chainParts = explode("-----END CERTIFICATE-----", $fullChain);
$leafCert = trim($chainParts[0]) . "\n-----END CERTIFICATE-----\n";
$caBundle = "";
for ($i = 1; $i < count($chainParts) - 1; $i++) {
    $caBundle .= trim($chainParts[$i]) . "\n-----END CERTIFICATE-----\n";
}

file_put_contents($certFile, $leafCert);
file_put_contents($keyFile, $domainKeyPem);
file_put_contents($caFile, $caBundle);

echo "¡Certificados descargados y guardados en $certsDir/!\n";

// Limpiar desafío
@unlink("$challengeDir/$token");

// 6. INSTALACIÓN AUTOMÁTICA EN CPANEL VÍA UAPI (JSON STDIN)
echo "Instalando certificado en cPanel mediante uapi...\n";

$payload = json_encode([
    'domain' => $domain,
    'cert' => $leafCert,
    'key' => $domainKeyPem,
    'cabundle' => $caBundle
]);

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];

$process = proc_open('/usr/bin/uapi --input=json SSL install_ssl', $descriptors, $pipes);

if (is_resource($process)) {
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);
    
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    
    $ret = proc_close($process);
    echo "Resultado de uapi install_ssl (Código $ret):\n$stdout\n";
}

echo "=== PROCESO DE RENOVACIÓN FINALIZADO CON ÉXITO ===\n";
?>
