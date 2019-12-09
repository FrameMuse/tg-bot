<?php

include 'useful.php';

$status = strstr(shell_exec("service tor status"), "is running") == true;
if ($status) {
    if (in_array(\useful::getGeo(), ["Russia"])) {
        shell_exec('proxychains php -r "include \'bot.php\';" > bot.log');
    } else include 'bot.php';
}

?>