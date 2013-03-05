<?php

/**
 * Description of transformador
 *
 * @author neto
 * 
 */
function transformarVideo($file) {
    if (file_exists($file)) {
        $return_var = -1;
        $return_var2 = -1;
        $duration = obtenerDuracion($file);
        $pathInfo = pathinfo($file);

        $outputFileMp4 = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . ".mp4";
        if (file_exists($outputFileMp4)) {
            $outputFileMp4 = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_.mp4";
        }
        $outputFileOgv = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . ".ogv";
        if (file_exists($outputFileOgv)) {
            $outputFileOgv = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_.ogv";
        }

        //command 
        //ffmpeg -i "file" -acodec libfaac -vcodec libx264 "mp4" -vcodec libvpx -acodec libvorbis "ogv"
        $cmd = 'ffmpeg -i "' . $file . '" "' . $outputFileMp4 . '"';
        //putLog($cmd);
        //Le quitamos el límite de ejecución al script
        set_time_limit(0);
        ob_start();
        passthru($cmd, $return_var);
        $aux = ob_get_contents();
        ob_end_clean();

        $cmd = 'ffmpeg -i "' . $file . '" "' . $outputFileOgv . '"';
        //putLog($cmd);
        //Le quitamos el límite de ejecución al script
        set_time_limit(0);
        ob_start();
        passthru($cmd, $return_var2);
        $aux = ob_get_contents();
        ob_end_clean();

        if ($return_var !== 0 || $return_var2 !== 0) {
            $return_var = -1;
            if (file_exists($outputFileMp4)) {
                unlink($outputFileMp4);
            }
            if (file_exists($outputFileOgv)) {
                unlink($outputFileOgv);
            }
        }

        return array("return_var" => $return_var, "duration" => $duration, "outputFileMp" => $outputFileMp4, "outputFileOg" => $outputFileOgv);
    } else {
        return array("return_var" => -2, "error" => "El archivo no existe");
    }
}

function transformarAudio($file) {
    if (file_exists($file)) {
        $return_var = -1;
        $return_var2 = -1;
        $duration = obtenerDuracion($file);
        $pathInfo = pathinfo($file);

        $outputFileMp3 = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . ".mp3";
        if (file_exists($outputFileMp3)) {
            $outputFileMp3 = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_.mp3";
        }
        $outputFileOgg = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . ".ogg";
        if (file_exists($outputFileOgg)) {
            $outputFileOgg = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_.ogg";
        }

        //$cmd = 'ffmpeg -i "' . $file . '" "' . $outputFileMp3 . '" -acodec libvorbis -ar 44100 -b 200k "' . $outputFileOgg . '"';
        $cmd = 'ffmpeg -i "' . $file . '" "' . $outputFileMp3 . '"';
        //putLog($cmd);
        ob_start();
        passthru($cmd, $return_var);
        $aux = ob_get_contents();
        ob_end_clean();

        $cmd = 'ffmpeg -i "' . $file . '" "' . $outputFileOgg . '"';
        //putLog($cmd);
        ob_start();
        passthru($cmd, $return_var2);
        $aux = ob_get_contents();
        ob_end_clean();

        if ($return_var !== 0 || $return_var2 !== 0) {
            $return_var = -1;
            if (file_exists($outputFileMp3)) {
                unlink($outputFileMp3);
            }
            if (file_exists($outputFileOgg)) {
                unlink($outputFileOgg);
            }
        }

        return array("return_var" => $return_var, "duration" => $duration, "outputFileMp" => $outputFileMp3, "outputFileOg" => $outputFileOgg);
    } else {
        return array("return_var" => -2, "error" => "El archivo no existe");
    }
}

function obtenerDuracion($file) {
    //Obtener la duración
    ob_start();
    passthru('ffmpeg -i "' . $file . '" 2>&1');
    $duration = ob_get_contents();
    ob_end_clean();

    $search = '/Duration: (.*?),/';
    $duration = preg_match($search, $duration, $matches, PREG_OFFSET_CAPTURE);
    if (isset($matches[1]) && isset($matches[1][0])) {
        $duration = $matches[1][0];
    } else {
        $duration = "00:00:00";
    }

    $exploded = explode(':', $duration);
    switch (sizeof($exploded)) {
        case 0:
        case 1:
            $hours = 0;
            $mins = 0;
            $secs = 0;
            break;
        case 2:
            $hours = 0;
            $mins = $exploded[0];
            $secs = $exploded[1];
            break;
        case 3:
        case 4:
            $hours = $exploded[0];
            $mins = $exploded[1];
            $secs = $exploded[2];
            break;
        default:
            $hours = 0;
            $mins = 0;
            $secs = 0;
            break;
    }
    $mins = $mins + ($hours * 60);
    if ($mins < 10)
        $mins = '0' . $mins;
    $secs = substr($secs, 0, 2);
    $duration = $mins . ":" . $secs;
    return $duration;
}

?>