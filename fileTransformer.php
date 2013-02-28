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

require_once 'sqsModelo.php';
require_once 's3Modelo.php';
require_once 'funcionesParaArchivos.php';
require_once 'transformadorModelo.php';

//Obtenemos el mensaje
$mensaje = readMessageFromQueue();
if (isset($mensaje)) {
    //hay un mensaje, lo decodificamos
    $msgBody = json_decode($mensaje['Body']);
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
                    $host = $msgBody->host;
                    if (strpos($host, "http://") === false) {
                        //el host no tiene http se lo pegamos
                        $host = "http://" . $host;
                    }
                    $url = $host . '/clases.php?a=actualizarDatosDespuesDeTransformacion';
                    $options = array(
                        'http' => array(
                            'method' => 'POST',
                            'content' => http_build_query($resutaldoGeneral)
                        )
                    );
                    $context = stream_context_create($options);
                    $result = file_get_contents($url, false, $context);

                    if($result == "ok"){
                        echo 'todo ok';
                    }else{
                        echo 'error al actualizar';
                    }
                    //Borramos el archivo original del S3
                    deleteFileFromS3($msgBody->bucket, $msgBody->key);
                    //Borramos los archivos temporales
                    unlink($res['outputFileMp']);
                    unlink($res['outputFileOg']);
                    unlink($fileName);
                } else {
                    echo ' Error al subir el archivo og ';
                }
            } else {
                echo ' Error al subir el archivo mp ';
            }
        } else {
            echo ' Ocurrió un error con la transformación error= ' . $res['return_var'];
        }
    } else {
        echo ' no se descargo ';
    }
    deleteMessageFromQueue($mensaje['ReceiptHandle']);
}
?>