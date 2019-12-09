<?php

use danog\MadelineProto\MyTelegramOrgWrapper;

$wrapper = new MyTelegramOrgWrapper($settings);
$wrapper->async(true);
$wrapper->loop(function () use ($wrapper) {
    if (yield $wrapper->logged_in()) {
        if (yield $wrapper->has_app()) {
            $app = yield $wrapper->get_app();
        } else {
            $app_title = yield $wrapper->readLine('Enter the app\'s name, can be anything: ');
            $short_name = yield $wrapper->readLine('Enter the app\'s short name, can be anything: ');
            $url = yield $wrapper->readLine('Enter the app/website\'s URL, or t.me/yourusername: ');
            $description = yield $wrapper->readLine('Describe your app: ');
            
            $app = yield $wrapper->my_telegram_org_wrapper->create_app_async(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $url, 'app_platform' => 'web', 'app_desc' => $description]);
        }
        
        \danog\MadelineProto\Logger::log($app);
    }
});

?>