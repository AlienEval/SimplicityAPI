<?php
// Función para escribir la IP en global.txt
function logIP($ip) {
    $log_message = "Dirección IP: $ip\n";
    file_put_contents("global.txt", $log_message, FILE_APPEND);
}

// Función para borrar un archivo JSON específico
function deleteJsonFile($filename) {
    $file_path = './json/' . $filename . '.json';
    if (file_exists($file_path)) {
        unlink($file_path);
        return true;
    }
    return false;
}

// Función para borrar todos los archivos JSON en la carpeta
function clearJsonFolder() {
    $folder_path = './json/';
    $files = scandir($folder_path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            unlink($folder_path . $file);
        }
    }
    return true;
}

// Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Origin: *");

// Otros encabezados que puedes necesitar permitir
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Métodos HTTP que quieres permitir
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Obtener la fecha y hora actual
$date_time = date("Y-m-d H:i:s");

// Obtener la dirección IP del cliente
$client_ip = $_SERVER['REMOTE_ADDR'];

// Obtener la solicitud actual
$request = $_SERVER['REQUEST_URI'];

// Construir el mensaje de registro
$log_message = "[$date_time] Solicitud recibida desde $client_ip: $request\n";

// Escribir el mensaje en el archivo de registro
file_put_contents("log.txt", $log_message, FILE_APPEND);

// Verifica si se ha enviado la solicitud GET con el parámetro "3d=users"
if (isset($_GET['3d']) && $_GET['3d'] === 'users') {
    // Define la ruta de la carpeta JSON
    $folder_path = './json/';

    // Obtiene la lista de archivos en la carpeta JSON
    $files = scandir($folder_path);

    // Inicializa un array para almacenar los nombres de archivo
    $file_names = [];

    // Recorre los archivos y agrega los nombres al array
    foreach ($files as $file) {
        // Excluye los directorios '.' y '..'
        if ($file != '.' && $file != '..') {
            $file_names[] = $file;
        }
    }

    // Imprime los nombres de archivo separados por comas
    echo implode(",", $file_names);

    // Termina la ejecución después de enviar la respuesta
    exit;
} elseif (isset($_GET['clr'])) {
    // Verifica si se proporcionó el parámetro "clr" para borrar un archivo específico
    $token_to_delete = $_GET['clr'];

    // Intenta borrar el archivo JSON correspondiente al token
    if (deleteJsonFile($token_to_delete)) {
        // Registro de acción en el archivo de registro
        $log_message = "[$date_time] Se borró el archivo JSON: " . $token_to_delete . ".json\n";
        file_put_contents("log.txt", $log_message, FILE_APPEND);

        // Devuelve mensaje de éxito
        echo json_encode(array("mensaje" => "Se borró el archivo JSON: " . $token_to_delete . ".json"));
    } else {
        // Devuelve mensaje de error si no se pudo borrar el archivo
        http_response_code(500);
        echo json_encode(array("mensaje" => "Error al borrar el archivo JSON: " . $token_to_delete . ".json"));
    }
} elseif (isset($_GET['3d']) && $_GET['3d'] === 'clear') {
    // Verifica si se proporcionó el parámetro "3d=clear" para borrar todos los archivos JSON
    if (clearJsonFolder()) {
        // Registro de acción en el archivo de registro
        $log_message = "[$date_time] Se borraron todos los archivos JSON en la carpeta /json\n";
        file_put_contents("log.txt", $log_message, FILE_APPEND);

        // Devuelve mensaje de éxito
        echo json_encode(array("mensaje" => "Se borraron todos los archivos JSON en la carpeta /json"));
    } else {
        // Devuelve mensaje de error si no se pudieron borrar los archivos
        http_response_code(500);
        echo json_encode(array("mensaje" => "Error al borrar los archivos JSON en la carpeta /json"));
    }
} elseif (isset($_GET['status']) && $_GET['status'] === '!') {
    // Responde con un mensaje 200 OK
    echo json_encode(array("message" => "success", "status" => "200 OK"));
} else {
    // Si no se proporciona ninguno de los parámetros esperados, ejecuta el código original para manejar otros casos
    // Lee el valor del parámetro "3d"
    $parametro_3d = isset($_GET['3d']) ? $_GET['3d'] : null;

    // Verifica que el token tenga el formato correcto (una letra seguida por dos números y dos letras)
    if ($parametro_3d && preg_match('/^[A-Za-z]\d{2}[A-Za-z]{2}$/', $parametro_3d)) {
        // Define la ruta completa del archivo JSON (carpeta actual + carpeta json + nombre de archivo)
        $file_path = './json/' . $parametro_3d . '.json';

        // Verifica si el archivo ya existe
        if (file_exists($file_path)) {
            // Si el archivo existe, lee su contenido
            $json_content = file_get_contents($file_path);
            $json_data = json_decode($json_content, true);

            // Verifica si se recibieron los parámetros adicionales del 1 al 6
            $par1 = isset($_GET['1']) ? $_GET['1'] : null;
            $par2 = isset($_GET['2']) ? $_GET['2'] : null;
            $par3 = isset($_GET['3']) ? $_GET['3'] : null;
            $par4 = isset($_GET['4']) ? $_GET['4'] : null;
            $par5 = isset($_GET['5']) ? $_GET['5'] : null;
            $par6 = isset($_GET['6']) ? $_GET['6'] : null;

            // Actualiza los valores solo si se recibieron
            if ($par1 !== null) { $json_data[0]['par1'] = $par1; }
            if ($par2 !== null) { $json_data[0]['par2'] = $par2; }
            if ($par3 !== null) { $json_data[0]['par3'] = $par3; }
            if ($par4 !== null) { $json_data[0]['par4'] = $par4; }
            if ($par5 !== null) { $json_data[0]['par5'] = $par5; }
            if ($par6 !== null) { $json_data[0]['par6'] = $par6; }

            // Marca como actualizado si se han recibido parámetros
            if ($par1 !== null || $par2 !== null || $par3 !== null || $par4 !== null || $par5 !== null || $par6 !== null) {
                $json_data[0]['Updated'] = "YES";
            }

            // Convierte el contenido a formato JSON
            $json_content = json_encode($json_data, JSON_PRETTY_PRINT);

            // Escribe el contenido actualizado en el archivo
            if (file_put_contents($file_path, $json_content) !== false) {
                // Agregar registro al archivo de registro
                $log_message = "[$date_time] Se actualizó el archivo JSON: " . $parametro_3d . ".json\n";
                file_put_contents("log.txt", $log_message, FILE_APPEND);

                // Devuelve el contenido actualizado en formato JSON
                echo $json_content;
            } else {
                // Devuelve una respuesta indicando que hubo un error al actualizar el archivo
                http_response_code(500);
                echo json_encode(array("mensaje" => "Error al actualizar el archivo JSON."));
            }
        } else {
            // Si el archivo no existe y se recibieron los parámetros adicionales, crea el archivo JSON
            $par1 = isset($_GET['1']) ? $_GET['1'] : "??";
            $par2 = isset($_GET['2']) ? $_GET['2'] : "??";
            $par3 = isset($_GET['3']) ? $_GET['3'] : "??";
            $par4 = isset($_GET['4']) ? $_GET['4'] : "??";
            $par5 = isset($_GET['5']) ? $_GET['5'] : "??";
            $par6 = isset($_GET['6']) ? $_GET['6'] : "??";

            $json_content = [
                [
                    "ssid" => $parametro_3d,
                    "Updated" => "NO",
                    "par1" => $par1,
                    "par2" => $par2,
                    "par3" => $par3,
                    "par4" => $par4,
                    "par5" => $par5,
                    "par6" => $par6
                ]
            ];

            // Convierte el contenido a formato JSON
            $json_data = json_encode($json_content, JSON_PRETTY_PRINT);

            // Intenta escribir el contenido JSON en el archivo
            if (file_put_contents($file_path, $json_data) !== false) {
                // Agregar registro al archivo de registro
                $log_message = "[$date_time] Se creó un nuevo archivo JSON: " . $parametro_3d . ".json\n";
                file_put_contents("log.txt", $log_message, FILE_APPEND);

                // Devuelve el contenido recién creado en formato JSON
                echo $json_data;
            } else {
                // Devuelve una respuesta indicando que hubo un error al crear el archivo
                http_response_code(500);
                echo json_encode(array("mensaje" => "Error al crear el archivo JSON."));
            }
        }
    } else {
        // Si el formato del token no es válido, devuelve un error
        http_response_code(400);
        echo json_encode(array("mensaje" => "Formato de token inválido. Debe ser una letra seguida por dos números y dos letras."));
        // Registra la IP en global.txt
        logIP($client_ip);
    }
}
?>
