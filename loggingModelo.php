<?php

function logMessage($logMessage, $addNewLine = false) {
    $file = __DIR__ . '/unovaTransformer.log';
    if ($addNewLine) {
        $logMessage .= PHP_EOL;
    }
    file_put_contents($file, $logMessage, FILE_APPEND | LOCK_EX);
}

?>