<?php

function getFileSize($file) {
    $size = filesize($file);
    if ($size < 0) {
        $size = fsize($file);
    }
    return $size;
}

function fsize($file) {
    // filesize will only return the lower 32 bits of
    // the file's size! Make it unsigned.
    $fmod = filesize($file);
    if ($fmod < 0)
        $fmod += 2.0 * (PHP_INT_MAX + 1);

    // find the upper 32 bits
    $i = 0;

    $myfile = fopen($file, "r");

    // feof has undefined behaviour for big files.
    // after we hit the eof with fseek,
    // fread may not be able to detect the eof,
    // but it also can't read bytes, so use it as an
    // indicator.
    while (strlen(fread($myfile, 1)) === 1) {
        fseek($myfile, PHP_INT_MAX, SEEK_CUR);
        $i++;
    }

    fclose($myfile);

    // $i is a multiplier for PHP_INT_MAX byte blocks.
    // return to the last multiple of 4, as filesize has modulo of 4 GB (lower 32 bits)
    if ($i % 2 == 1)
        $i--;

    // add the lower 32 bit to our PHP_INT_MAX multiplier
    return ((float) ($i) * (PHP_INT_MAX + 1)) + $fmod;
}

?>