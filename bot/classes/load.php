<?php namespace AppName\abilities;

function load()
{
    foreach (scandir(__DIR__) as $file) {
        if ($file == basename(__FILE__) || in_array($file, [".", ".."])) continue;
        print $file.PHP_EOL;
        require_once $file;
    }
}

?>