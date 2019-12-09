<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
include_once '../useful.php';
include 'EventHandler.php';
include 'classes/load.php';
    \AppName\abilities\load();



$settings = file_get_contents(APPNAME_BOT_DIR . "/settings.json");
$settings = json_decode($settings, true);

#include 'login_helper.php'; // may not start without this
$MadelineProto = new \danog\MadelineProto\API('sessions/bot.session', $settings);
$MadelineProto->async(true);
$MadelineProto->resetUpdateState();
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\Matter');
});
$MadelineProto->loop();
