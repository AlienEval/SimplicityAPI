<?php
// Permitir solicitudes CORS
header("Access-Control-Allow-Origin: *");
// Permitir método GET
header("Access-Control-Allow-Methods: GET");
// Permitir cualquier cabecera
header("Access-Control-Allow-Headers: *");

// Función para eliminar token de ok.txt y agregarlo a deleted.txt
function moverToken($token) {
    $file = file_get_contents('ok.txt');
    $tokens = explode("\n", $file);
    $key = array_search($token, $tokens);
    if ($key !== false) {
        unset($tokens[$key]);
        file_put_contents('ok.txt', implode("\n", $tokens));
        file_put_contents('deleted.txt', $token . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Verificar si la IP está en las listas
        $ip = $_SERVER['REMOTE_ADDR'];
        $ipInList = verificarIPLists($ip);
        
        agregarLog($token, true, "eliminado", $_GET, $ipInList); // Agregar parámetros $_GET y $ipInList
        return true;
    }
    // Si el token no se encontró, también verificamos la IP y registramos en el log
    $ip = $_SERVER['REMOTE_ADDR'];
    $ipInList = verificarIPLists($ip);
    agregarLog($token, false, "denied", $_GET, $ipInList); // Agregar parámetros $_GET y $ipInList
    return false;
}

// Función para agregar registro a log.txt
function agregarLog($token, $valido, $accion, $params, $ipStatus) {
    $tokenText = !empty($token) ? "Token '" . $token . "' " : "";
    $mensaje = date('Y-m-d H:i:s') . " - IP:" . $_SERVER['REMOTE_ADDR'] . " - " . $tokenText;
    $mensaje .= $valido ? "valido." : "invalido.";
    $mensaje .= " Accion: " . $accion;
    $mensaje .= " // Referer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No Referer');
    $mensaje .= " // Parametros: " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Evitar escapes en JSON
    $mensaje .= " // IP Status: " . $ipStatus; // Agregar estado de la IP

    if (!$valido) {
        $mensaje .= " // IP: " . $_SERVER['REMOTE_ADDR'];
        file_put_contents('global.txt', $_SERVER['REMOTE_ADDR'] . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    // Escribir con codificación UTF-8 y bloqueo exclusivo
    file_put_contents('log.txt', $mensaje . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Función para verificar si una IP está en ips.txt o blacklist.txt
function verificarIPLists($ip) {
    $ips = file_exists('ips.txt') ? file('ips.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $blacklist = file_exists('blacklist.txt') ? file('blacklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    
    if (in_array($ip, $ips)) {
        return "readd";
    } elseif (in_array($ip, $blacklist)) {
        return "ban";
    } else {
        return "noaction";
    }
}

// Función para registrar IP en ips.txt sin duplicados y verificar si ya existía
function registrarIP($ip) {
    $ips = file_exists('ips.txt') ? file('ips.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!in_array($ip, $ips)) {
        file_put_contents('ips.txt', $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
        return false;
    }
    return true;
}

// Función para agregar una IP a blacklist.txt sin duplicados
function agregarABlacklist($ip) {
    $blacklist = file_exists('blacklist.txt') ? file('blacklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!in_array($ip, $blacklist)) {
        file_put_contents('blacklist.txt', $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
        return false;
    }
    return true;
}

// Función para verificar si una IP está en blacklist.txt
function verificarBlacklist($ip) {
    $blacklist = file_exists('blacklist.txt') ? file('blacklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    return in_array($ip, $blacklist);
}

// Función para borrar todos los archivos de texto
function clearFiles() {
    $files = ['ok.txt', 'deleted.txt', 'ips.txt', 'blacklist.txt', 'log.txt', 'global.txt'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Función para recrear todos los archivos de texto
function recreateFiles() {
    $files = ['ok.txt', 'deleted.txt', 'ips.txt', 'blacklist.txt', 'log.txt', 'global.txt'];
    foreach ($files as $file) {
        file_put_contents($file, '');
    }
}

// Función para limpiar info.txt
function clearInfo() {
    if (file_exists('info.txt')) {
        file_put_contents('info.txt', '');
    }
}

// Respuesta OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: *");
    http_response_code(200);
    exit;
}

// Validar status parameter
if (isset($_GET['status']) && $_GET['status'] === '!') {
    http_response_code(200);
    $response = array(
        "status" => "200 OK"
    );
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Codificación correcta
    exit;
}

// Validar solicitud GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validar token
    if (isset($_GET['token'])) {
        $token = strtoupper($_GET['token']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $ipExists = registrarIP($ip);
        if ($ipExists) {
            http_response_code(200);
            $response = array(
                "status" => "Readded!",
                "readd" => "YES",
                "ban" => verificarBlacklist($ip) ? "YES" : "NO"
            );
        } else {
            $tokenValido = moverToken($token);
            if ($tokenValido) {
                // Verificar si la IP está en las listas
                $ipInList = verificarIPLists($ip);
                
                http_response_code(200);
                $response = array(
                    "status" => "200 OK",
                    "readd" => $ipInList === "readd" ? "YES" : "NO",
                    "ban" => $ipInList === "ban" ? "YES" : "NO"
                );
            } else {
                // Si el token no se encontró, devolver "denied" y estado de la IP
                $ipInList = verificarIPLists($ip);
                http_response_code(404);
                $response = array(
                    "status" => "denied",
                    "readd" => $ipInList === "readd" ? "YES" : "NO",
                    "ban" => $ipInList === "ban" ? "YES" : "NO"
                );
            }
        }
    } 
    // Validar IP
    elseif (isset($_GET['ip']) && $_GET['ip'] === '!') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ipExists = registrarIP($ip);
        $banned = verificarBlacklist($ip) ? "YES" : "NO";
        http_response_code(200);
        $response = array(
            "ip" => $ip,
            "status" => "200 OK",
            "readded" => $ipExists ? "YES" : "NO",
            "BAN" => $banned
        );
        agregarLog("", true, "IP registrado", $_GET, ""); // Agregar parámetros $_GET y dejar IP Status vacío
    } 
    // Agregar IP a blacklist
    elseif (isset($_GET['addb'])) {
        $ip = $_GET['addb'] === '!' ? $_SERVER['REMOTE_ADDR'] : $_GET['addb'];
        $blacklistExists = agregarABlacklist($ip);
        http_response_code(200);
        $response = array(
            "ip" => $ip,
            "status" => "200 OK",
            "readded" => $blacklistExists ? "YES" : "NO"
        );
        agregarLog($ip, true, "IP anadida a blacklist", $_GET, ""); // Agregar parámetros $_GET y dejar IP Status vacío
    }
    // Limpiar todos los archivos y recrearlos
    elseif (isset($_GET['clearz']) && $_GET['clearz'] === '!') {
        clearFiles();
        recreateFiles();
        http_response_code(200);
        $response = array(
            "status" => "200 OK",
            "message" => "Archivos limpiados y recreados"
        );
    }
    // Limpiar info.txt
    elseif (isset($_GET['clearinfo']) && $_GET['clearinfo'] === '!') {
        clearInfo();
        http_response_code(200);
        $response = array(
            "status" => "200 OK",
            "message" => "info.txt limpiado"
        );
    }
    // Guardar datos recibidos en info.txt
    elseif (isset($_GET['img'])) {
        $data = $_GET['img'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = date('Y-m-d H:i:s');
        $logData = "Data: " . $data . " // Timestamp: " . $timestamp . " // IP: " . $ip . PHP_EOL;
        file_put_contents('info.txt', $logData, FILE_APPEND | LOCK_EX);
        http_response_code(200);
        $response = array(
            "status" => "200 OK",
            "message" => "Datos guardados en info.txt"
        );
    } 
    // Solicitud inválida
    else {
        http_response_code(400);
        $response = array(
            "status" => "400 Bad Request"
        );
    }

    // Enviar respuesta JSON con codificación correcta
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Solicitud no permitida
http_response_code(405);
$response = array(
    "status" => "405 Method Not Allowed"
);
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>