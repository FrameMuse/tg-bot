<?php

include 'useful.php';
useful::setUp([
    "notice" => on,
]);
include 'bot/classes/database.php';

$database = new AppName\abilities\DataBase(APPNAME_BOT_DIR."/database.json");
$database->connect();
$database->ping();

unset($argv[0]);

$isIt = function (string $command, object $closure) use (&$argv) {
    $command = "--".$command;
    if (in_array($command, $argv)) {
        unset($argv[array_search($command, $argv)]);
        $closure->__invoke($argv);
        return true;
    }
};

$isIt("fuck", function ($commands) use (&$isIt) {
    console("Fuck")
        -> logln();
    $isIt("you", function ($commands) use (&$isIt) {
        console("you")
        -> lnlog();
        
    });
});

$isIt("users", function () use (&$database) {
    $result = $database->query("SELECT * FROM users");
    while ($row = $result->fetch_assoc()) {
        console($row['first_name']." (".$row['phone'].") => ")
            -> paint("BLACK", "LIGHTGRAY", true);
            
        console($row['user']." ({$row['extra']})")
            -> paint("BLACK", "LIGHTGRAY");
        
        print PHP_EOL;
    }
});

$isIt("op", function ($commands) use (&$database) {
    $result = $database->query("UPDATE users set extra = 'opped' WHERE user = '{$commands[0]}'");
    if ($result) {
        console("Права успешно ввыданы!")
        -> paint("WHITE", "GREEN");
    } else {
        console("Права не были ввыданы!")
        -> paint("WHITE", "RED");
    }
});

$isIt("deop", function ($commands) use (&$database) {
    $result = $database->query("UPDATE users set extra = NULL WHERE user = '{$commands[0]}'");
    if ($result) {
        console("Права успешно удалены!")
        -> paint("WHITE", "GREEN");
    } else {
        console("Права не были удалены!")
        -> paint("WHITE", "RED");
    }
});

$isIt("clear_name", function ($commands) use (&$database) {
    $commands = implode(', ', $commands);
    $result = $database->query("UPDATE users set phone = NULL WHERE user IN ($commands)");
    if ($result) {
        console("Успешно удалено!")
        -> paint("WHITE", "GREEN");
    } else {
        console("Удалено не было!")
        -> paint("WHITE", "RED");
    }
});
