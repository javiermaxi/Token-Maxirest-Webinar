<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Si es una petición de pre-vuelo de CORS, respondemos 200 y salimos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

function alm_login_maxirest_api_externa() {
    // 1. Cargar variables de entorno del .env
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    $email = $_ENV['EMAIL'] ?? '';
    $password = $_ENV['PASSWORD'] ?? '';
    $personalKey = $_ENV['PERSONAL_KEY'] ?? '';

    // Función auxiliar para hacer peticiones POST con cURL nativo de PHP
    $curl_post = function ($url, $headers, $body) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return $error ? ['error' => $error] : $response;
    };

    // 2. Hacer Login en Maxirest para obtener el Token
    $login_url = 'https://mgc.maxirest.com/api/v1/apilogin';
    $login_body = json_encode([
        'email' => $email,
        'password' => $password,
        'personal_key' => $personalKey
    ]);
    
    $login_headers = ['Content-Type: application/json'];
    $login_response = $curl_post($login_url, $login_headers, $login_body);

    if (isset($login_response['error'])) {
        echo json_encode(['success' => false, 'data' => 'Error al autenticar: ' . $login_response['error']]);
        exit;
    }

    $login_data = json_decode($login_response, true);
    $token = $login_data['token'] ?? null;

    if (!$token) {
        echo json_encode(['success' => false, 'data' => 'Token no encontrado en respuesta de login.']);
        exit;
    }

    // 3. Cifrar el token obtenido
    $encrypt_url = 'https://mgc.maxirest.com/api/v1/mgc/encrypt';
    $encrypt_headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'auth-type: mgc_auth'
    ];
    
    $encrypt_body = json_encode(['data' => $token]);
    $encrypt_response = $curl_post($encrypt_url, $encrypt_headers, $encrypt_body);

    if (isset($encrypt_response['error'])) {
        echo json_encode(['success' => false, 'data' => 'Error al cifrar el token: ' . $encrypt_response['error']]);
        exit;
    }

    $encrypt_data = json_decode($encrypt_response, true);

    if (!isset($encrypt_data['encrypted_data'])) {
        echo json_encode(['success' => false, 'data' => 'Token cifrado no encontrado en Maxirest.']);
        exit;
    }

    // 4. ✅ Éxito: Devolvemos el token cifrado en el mismo formato que espera tu JS
    echo json_encode([
        'success' => true,
        'data' => $encrypt_data['encrypted_data']
    ]);
    exit;
}

// 🚀 EJECUTAR LA FUNCIÓN DIRECTAMENTE AL LLAMAR AL ARCHIVO
alm_login_maxirest_api_externa();