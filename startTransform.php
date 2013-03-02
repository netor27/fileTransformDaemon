<?php

/*
  Los mensajes son con el siguiente formato
 * 
  {
  "bucket":"unovaTesting",
  "key":"porTransformar\/380269367483a031f1557667400c5af70fb53469cd721188ee3593123a2678f74c3d323d62720ac95a490deba8e6b438b6dc66dda161cb312c3e2d5fdab92860a83eee29176811474254c4.mp3",
  "tipo":4,
  "host":"privado.localhost",
  "idClase": 23,
  }
 */
$oldPath = set_include_path("/");
$newPath = $oldPath . ":" . __DIR__ . "/";
set_include_path($newPath);

require_once 'sqsModelo.php';
require_once 's3Modelo.php';
require_once 'funcionesParaArchivos.php';
require_once 'transformadorModelo.php';
require_once 'loggingModelo.php';

logMessage("Iniciando Daemon.." . date("d-m-Y H:i:s"), true);
logMessage("Se cargaron las librerías", true);

while (true) {
    logMessage("Esperando mensaje...".date("d-m-Y H:i:s"), true);

//Obtenemos el mensaje
    $mensaje = readMessageFromQueue();
    if (isset($mensaje)) {
        logMessage(" Se encontro un mensaje", true);
        //hay un mensaje, lo decodificamos
        $msgBody = json_decode($mensaje['Body']);
        //Obtenemos el host del que viene el mensaje
        $host = $msgBody->host;
        if (strpos($host, "http://") === false) {
            //el host no tiene http se lo pegamos
            $host = "http://" . $host;
        }
        //Descargamos el archivo a un archivo temporal
        $fileName = getFileFromS3($msgBody->bucket, $msgBody->key);
        if (isset($fileName)) {
            //El archivo se descargo con éxito
            //Transformamos y obtenemos la duración              
            switch ($msgBody->tipo) {
                case 0:
                    //Es un video
                    $res = transformarVideo($fileName);
                    $folder = "video";
                    break;
                case 4:
                    $res = transformarAudio($fileName);
                    $folder = "audio";
                    //Es un audio
                    break;
            }
            //print_r($res);
            if ($res >= 0) {
                //El archivo se transformo correctamente
                $resMp = uploadFileToS3($res['outputFileMp'], $msgBody->bucket, $folder);
                if ($resMp['res']) {
                    //se subio correctamente uno, subimos el otro
                    $resOg = uploadFileToS3($res['outputFileOg'], $msgBody->bucket, $folder);
                    if ($resOg['res']) {
                        //Obtenemos el tamaño de los archivos
                        $fileSizeMp = getFileSize($res['outputFileMp']);
                        $fileSizeOg = getFileSize($res['outputFileOg']);
                        $totalSize = $fileSizeMp + $fileSizeOg;
                        //Se subio correctamente el segundo
                        $resutaldoGeneral = array(
                            "bucket" => $msgBody->bucket,
                            "idClase" => $msgBody->idClase,
                            "key1" => $resMp['key'],
                            "key2" => $resOg['key'],
                            "duracion" => $res['duration'],
                            "size" => $totalSize
                        );
                        //print_r($resutaldoGeneral);                    
                        //Hacemos el post al servidor con los datos para que actualize la bd  
//                        $url = $host . '/clases.php?a=actualizarDatosDespuesDeTransformacion';
//                        $query = http_build_query($resutaldoGeneral);
//                        $options = array(
//                            'http' => array(
//                                'method' => 'POST',
//                                'header' => "Connection: close\r\n" .
//                                "Content-Type: application/x-www-form-urlencoded\r\n" .
//                                "Content-Length: " . strlen($query) . "\r\n",
//                                'content' => $query
//                            )
//                        );
//                        $context = stream_context_create($options);
//                        $result = file_get_contents($url, false, $context);
                        $result = "ok";
                        echo 'No hacemos el post de regreso';
                        if ($result == "ok") {
                            logMessage("Se transformo correctamente", true);
                            //echo 'todo ok';
                            //$emailBody = "Todo se hizo bien";
                            //enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
                        } else {
                            //echo 'error al actualizar';
                            $emailBody = "Ocurrio un error al actualizar la base de datos en el host";
                            enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
                        }
                        //Borramos el archivo original del S3
                        //deleteFileFromS3($msgBody->bucket, $msgBody->key);
                        //Borramos los archivos temporales
                        unlink($res['outputFileMp']);
                        unlink($res['outputFileOg']);
                        unlink($fileName);
                    } else {
                        $emailBody = "Error al subir archivo og";
                        enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
                    }
                } else {
                    //echo ' Error al subir el archivo mp ';
                    $emailBody = "Error ar subir el archivo mp";
                    enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
                }
            } else {
                //echo ' Ocurrió un error con la transformación error= ' . $res['return_var'];
                $emailBody = "Ocurrió un error con la transformación, return_var=" . $res['return_var'];
                enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
            }
        } else {
            $emailBody = "No se pudo descargar el archivo $msgBody->bucket/$msgBody->key";
            enviarMailErrorTransformacion($emailBody, $host, $mensaje['Body']);
        }
        deleteMessageFromQueue($mensaje['ReceiptHandle']);
    }
    //Dormimos el proceso por X segundos
    $xSeg = 45;
    
    usleep($xSeg * 1000000);
}

function enviarMailErrorTransformacion($emailBody, $host, $mensajeRecibido) {
    $urlPublicar = $host . "/publicarMensaje.php?key=er105706&msg=" . urlencode($mensajeRecibido);
    $emailBody .= "<br><br>Mensaje recibido: " . $mensajeRecibido;
    $emailBody .= "<br><br><a href='" . $urlPublicar . "'>Publicar de nuevo</a>";
    require_once 'emailModelo.php';
    $receiver = "neto.r27@gmail.com";
    $from = "unova-noreply@unova.mx";
    $subject = "Error al transformar contenido";
    $text = 'Ocurrio un error en el procedimiento de transformacion\n\n' . $emailBody;
    $html = '<h3>Ocurrio un error en el procedimiento de transformacion</h3>
        <br><br>' . $emailBody . '<br><br><br>';
    return sendMail($text, $html, $subject, $from, $receiver);
}

?>