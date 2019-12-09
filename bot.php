<?php namespace AppName\bot;

function cluster($handleMessage) {
    yield $handleMessage("/jopa1", [
        'message' => "jopa1",
    ]);

    yield $handleMessage("/jopa2", [
        'message' => "jopa2",
    ]);

    yield $handleMessage("/jopa3", [
        'message' => "jopa3",
    ]);
};


include 'bot/launcher.php';

?>