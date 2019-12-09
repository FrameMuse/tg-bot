<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
include 'useful.php';

class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function __construct($MadelineProto)
    {
        parent::__construct($MadelineProto);
        $this->db = yield new DataBase();
        yield $this->db->connect();
        yield $this->db->ping();

        yield $this->updateInfo();
    }
    public function forUsers(object $closure, $random = false)
    {
        $me = yield $this->get_self();
        $dialogs = yield $this->get_dialogs();

        if ($random) shuffle($dialogs);
        foreach ($dialogs as $peer) {
            if ($peer['user_id'] == $me['id']) continue;
            $action = yield @$closure->__invoke($peer);
            if ($action['_']) return $action['return'];
        }
    }
    public function updateInfo()
    {
        // Updating general information
        $result = $this->db->query("SELECT name, content FROM info");
        while (yield $row = $result->fetch_assoc()) {
            yield $this->info[$row['name']] = $row['content'];
        }

        // Updating game options
        $obtain = yield $this->obtain();
        yield $message = "<b>{$obtain['header']},</b><br>{$obtain['text']}";
        $options = [
            'message' => $message,
            'media' => [
                '_' => 'inputMediaUploadedPhoto',
                'file' => $obtain['picture'],
            ],
            'reply_markup' => $obtain['markups'],
            'parse_mode' => 'HTML',
        ];
        $this->game['options'] = $options;
    }
    public function isOpped(int $user)
    {
        $this->db->ping();
        $result = $this->db->query("SELECT id, user, extra FROM users WHERE user = '$user'");
        $row = $result->fetch_assoc();
        return $row['extra'] == "opped";
    }
    public function message(array $options)
    {
        if (isset($options['method'])) {
            $method = $options['method'];
            unset($options['method']);
        } else $method = "xer";

        try {
            switch ($method) {
                case "edit":
                    if (!isset($options['id'])) {
                        $options['id'] = $options['peer']['message']['id']+1;
                    }
                    yield $this->messages->editMessage($options);
                    break;

                case "media":
                    yield $this->messages->sendMedia($options);
                    break;
                
                default:
                    yield $this->messages->sendMessage($options);
                    break;
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->messages->sendMessage(['peer' => 565324826, 'message' => $e]);
        }
    }
    public function isWaiting($data, string $process)
    {
        $user = is_int($data) ? $data:$data['message']['from_id'];
        $this->db->ping();
        switch($process) {
            case "phoneNumber":
                $result = $this->db->query("SELECT id, user, phone FROM users WHERE user = '$user'");
                $row = $result->fetch_assoc();
                return [
                    '_' => $row['phone'] == NULL,
                    'this' => $row['phone'],
                ];

                break;
            case "newUser":
                $result = $this->db->query("SELECT id, user, phone FROM users WHERE user = '$user'");
                return $result->num_rows == 0;

                break;
        }
    }
    public function obtain()
    {
        yield $result = $this->db->query("SELECT * FROM questions WHERE id = '3'");
        yield $row = $result->fetch_assoc();

        $header = $row['qtitle'];
        $text = $row['qtext'];
        $picture = empty($row['picture']) ? null:$row['picture'];
        yield $answers = json_decode($row['answers']);

        if ($row['answers'] != "[]") {
            $i = 1;
            foreach ($answers as $key => $value) {
                $buttons[] = [
                    '_' => 'keyboardButtonCallback',
                    'text' => "{$value->title}",
                    'data' => "{$value->correct}",
                ];
                if ($i == count($answers) || ($i > 0 && ($i % 2 == 0))) {
                    $rows[] = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
                    unset($buttons);
                }
                $i++;
            }
            $markups = ['_' => 'replyInlineMarkup', 'rows' => $rows];
        }

        return [
            "header" => $header,
            "text" => $text,
            "picture" => $picture,
            "markups" => isset($markups) ? $markups:null,
        ];
    }
    public function onUpdateBotCallbackQuery($update)
    {
        yield $this->messages->setTyping(['peer' => $update, 'action' => ['_' => 'sendMessageTypingAction']]);

        $markups = ['_' => 'replyInlineMarkup', 'rows' => [
            ['_' => 'keyboardButtonRow', 'buttons' => [
                [
                    '_' => 'keyboardButtonCallback',
                    'text' => $this->info['button_again'],
                    'data' => "again",
                ]
            ]]
        ]];
        $options = [
            'peer' => $update,
            'id' => $update['msg_id'],
            'message' => $update['data'],
            'reply_markup' => $markups,
            'parse_mode' => 'HTML',
        ];
        

        if ($update['data'] == "true") {
            $options['message'] = $this->info['info_correct'];
            $options['reply_markup'] = null;
        } elseif ($update['data'] == "again") {
            try {
                $obtain = yield $this->obtain();
            } catch (\Throwable $e) {
                print $e->getMessage();
            }
            $message = "<b>{$obtain['header']},</b><br>{$obtain['text']}";
            $options['message'] = $message;
            $options['reply_markup'] = $obtain['markups'];
        } elseif ($update['data'] == "false") {
            $options['message'] = $this->info['info_inCorrect'];
        }

        try {
            yield $this->messages->editMessage($options);
        } catch (\Throwable $e) {
            print $e->getMessage();
        }
        yield $this->messages->setBotCallbackAnswer(['query_id' => $update["query_id"], 'cache_time' => 0]);
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
        $res = json_encode($update, JSON_PRETTY_PRINT);
        if ($res == '') {
            $res = var_export($update, true);
        }

        yield $Message = function ($if, $closure = null) use (&$update) {
            $peer = $update['message']['from_id'];
            $message = $update['message']['message'];

            switch (gettype($if)) {
                case "string":
                    #$getif = $if == $message;
                    $getif = similar_text($if, $message, $percent) > 0 && $percent > 83;
                    print $percent;
                    break;
                
                case "array":
                    $getif = in_array($message, $if);
                    break;
            }

            if ($getif) {
                $options = [ // Default options
                    "peer" => &$update,
                    "message" => "Default message",
                    'parse_mode' => 'HTML',
                ];
                unset($options['_']);

                if (gettype($closure) == "object") 
                    foreach (yield $closure->__invoke() as $key => $value)
                        yield $options[$key] = &$value;

                yield $this->message($options);
                throw new Exception("OK");
            }
        };

        try {

            yield $Message("/yoll");
            /*
            yield $Message("/start", function () use (&$update) {
                $options = [
                    'message' => $this->info['start_before'],
                    'parse_mode' => 'HTML',
                ];
                
                $Chat = yield $this->get_info($update);
                $name = yield $this->isWaiting($update, "phoneNumber");
                if (!$name['_']) {
                    $start_after = str_replace('%name%', $name['this'], $this->info['start_after']);
                    $options['message'] = $start_after;
                }

                return $options;
            });

            yield $Message("/start", [
                '_' => [
                    'rights' => [
                        [
                            '_' => 'UsersCanSee',
                            'right' => 'yes',
                        ],
                    ],
                ],
                'message' => $this->info['start_before'],
                'parse_mode' => 'HTML',
            ]);

            yield $Message("/start", function () {
                return [
                    '_' => [
                        'rights' => [
                            [
                                '_' => 'UsersCanSee',
                                'right' => 'yes',
                            ],
                        ],
                    ],
                    'message' => $this->info['start_before'],
                    'parse_mode' => 'HTML',
                ];
            });
            */
        } catch (Exception $e) {
            yield print $e->getMessage()."\r\nThe exception was created on line: " . $e->getLine();
        }
        /*
        if ($update['message']['message'] == "/SendMessage" && $this->isOpped($update['message']['from_id'])) {
            yield $this->updateInfo();
            $options = $this->game['options'];
            yield $this->forUsers(function ($peer) use ($options) {
                try {
                    $options['peer'] = $peer;
                    yield $this->messages->sendMedia($options);
                } catch (\Throwable $e) {
                    yield print $e->getMessage();
                }
            });

            return;
        }

        if ($update['message']['message'] == "/showWinner" && $this->isOpped($update['message']['from_id'])) {
            $user = yield $this->forUsers(function ($peer) {
                yield $user = $this->isWaiting($peer['user_id'], "phoneNumber");
                if (!$user['_']) return ['_' => true, 'return' => $user];
            }, true);

            yield $this->forUsers(function ($peer) use ($user) {
                try {
                    $options['peer'] = $peer;
                    $options['parse_mode'] = "HTML";
                    $options['message'] = str_replace("%name%", $user['this'], $this->info['winner_message']);
                    yield $this->messages->sendMessage($options);
                } catch (\Throwable $e) {
                    print $e->getMessage();
                }
            });

            return;
        }

        if ($update['message']['message'] == "/updateInfo" && $this->isOpped($update['message']['from_id'])) {
            yield $this->updateInfo();
            yield $options = [
                'peer' => $update,
                'message' => "Данные были обновлены",
                'parse_mode' => 'HTML',
            ];
            try {
                yield $this->messages->sendMessage($options);
            } catch (\Throwable $e) {
                print $e->getMessage();
            }
            return;
        }

        if (yield $this->isWaiting($update, "phoneNumber")['_']) {
            $user = $update['message']['from_id'];
            $phone = $update['message']['message'];
            yield $this->db->query("UPDATE users set phone = '$phone' WHERE user = '$user'");
            $options = [
                'peer' => $update,
                #'message' => "Ваш телефон ({$update['message']['message']}) был подписан на рассылку! Вам будут приходить интересные сообщения.)",
                'parse_mode' => 'HTML',
            ];
            $start_after = str_replace('%name%', $phone, $this->info['start_after']);
            $options['message'] = $start_after;
            try {
                yield $this->messages->sendMessage($options);
            } catch (\Throwable $e) {
                print $e->getMessage();
            }
            return;
        }
        */
        try {
            if (isset($update['message']['media']) && ($update['message']['media']['_'] == 'messageMediaPhoto' || $update['message']['media']['_'] == 'messageMediaDocument')) {
                $time = microtime(true);
                $file = yield $this->download_to_dir($update, 'photos');
                yield $this->messages->sendMessage(['peer' => $update, 'message' => 'Downloaded to '.$file.' in '.(microtime(true) - $time).' seconds', 'reply_to_msg_id' => $update['message']['id']]);
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->messages->sendMessage(['peer' => '@danogentili', 'message' => $e]);
        }
    }
}

$settings = file_get_contents(APPNAME_BOT_DIR . "/settings.json");
$settings = json_decode($settings, true);

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


$MadelineProto = new \danog\MadelineProto\API('bot2.session', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
#    yield $MadelineProto->bot_login('1039807617:AAG5S9Rca2qnS1CxehvUsuoZ-zjSMWMqGx8');
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
$MadelineProto->loop();

?>
