<?php

//Load de sdk
require_once 'vendor/autoload.php';

//Para acceder al S3
use Aws\Common\Aws;
//Para subir archivos grandes
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

function uploadFileToS3($sourceFile, $bucket, $folder = "") {
    $resultado = array("res" => false);
    // Instanciamos un cliente de s3
    $client = Aws::factory( __DIR__ . '/configurationFile.php')->get('s3');
    $key = generateFileKey($sourceFile, $folder);
    while ($client->doesObjectExist($bucket, $key)) {
        //Si ese objeto ya existe, generamos otro key
        //Este caso es muy raro, debido a la generación de clave aleatoria
        //Pero puede pasar
        $key = generateFileKey($sourceFile, $folder);
    }
    require_once 'funcionesParaArchivos.php';
    //Si el archivo es más grande que 10MB, utilizamos la función
    //para subir por partes
    $megabytesLimit = 10 * 1048576;
    if (getFileSize($sourceFile) < $megabytesLimit) {
        $client->putObject(array(
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $sourceFile,
            'ACL' => 'public-read'
        ));
        $resultado["res"] = true;
    } else {
        $uploader = UploadBuilder::newInstance()
                ->setClient($client)
                ->setSource($sourceFile)
                ->setBucket($bucket)
                ->setKey($key)
                ->setOption('ACL', 'public-read')
                ->build();
        try {
            $uploader->upload();
            $resultado["res"] = true;
        } catch (MultipartUploadException $e) {
            $uploader->abort();
            $resultado["res"] = false;
        }
    }
    if ($resultado['res']) {
        $resultado["bucket"] = $bucket;
        $resultado["key"] = $key;
    }
    return $resultado;
}

function generateFileKey($sourceFile, $folder = "") {
    $pathInfo = pathinfo($sourceFile);
    if ($folder === "") {
        $folder = getFolderName($pathInfo['extension']);
    }
    $fileName = getUniqueCode(150) . '.' . $pathInfo['extension'];
    $fileKey = $folder . '/' . $fileName;
    return $fileKey;
}

function deleteFileFromS3($bucket, $key) {
    $client = Aws::factory( __DIR__ . '/configurationFile.php')->get('s3');
    try {
        $client->deleteObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
        return true;
    } catch (Exception $e) {
        //echo 'No se borro el archivo ' . $bucket . "/" . $key;
        return false;
    }
}

function deleteFileFromS3ByUrl($url) {
    $prefijo = getPrefijoLink();
    $url = str_ireplace($prefijo, "", $url);
    list($bucket, $key) = explode("/", $url, 2);
    return deleteFileFromS3($bucket, $key);
}

function getFileFromS3($bucket, $key) {
    list($name, $extension) = explode('.', $key);
    $client = Aws::factory( __DIR__ . '/configurationFile.php')->get('s3');
    $fileName =  __DIR__ . '/temporal/' . getUniqueCode() . '.' . $extension;
    try {
        $result = $client->getObject(array(
            'Bucket' => $bucket,
            'Key' => $key,
            'SaveAs' => $fileName
                ));
        return $fileName;
    } catch (Exception $e) {
        return null;
    }
}

?>