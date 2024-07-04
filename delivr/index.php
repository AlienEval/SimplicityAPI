<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json");

$okFile = '../ok.txt';
$okLoadFile = 'okload.txt';
$deliveredFile = 'delivered.txt';

if (isset($_GET['loadtokens']) && $_GET['loadtokens'] === '!') {
    copy($okFile, $okLoadFile);
    http_response_code(200);
    echo json_encode(['message' => 'Tokens loaded successfully', 'status' => '200 OK']);
    exit();
}

if (isset($_GET['newtoken']) && $_GET['newtoken'] === '!') {
    $tokens = file($okLoadFile, FILE_IGNORE_NEW_LINES);

    if (!empty($tokens)) {
        $token = $tokens[0];
        array_shift($tokens);
        file_put_contents($deliveredFile, $token . PHP_EOL, FILE_APPEND);
        file_put_contents($okLoadFile, implode(PHP_EOL, $tokens));
        echo json_encode(['token' => $token]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No hay tokens disponibles']);
    }
}

if (isset($_GET['verify'])) {
    $tokenToVerify = $_GET['verify'];
    $tokens = file($okFile, FILE_IGNORE_NEW_LINES);

    if (in_array(trim($tokenToVerify), $tokens)) {
        echo json_encode(['valid' => true]);
    } else {
        echo json_encode(['valid' => false]);
    }
}
?>
