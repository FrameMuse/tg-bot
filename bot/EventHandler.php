<?php

class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function __construct($MadelineProto)
    {
        parent::__construct($MadelineProto);
        useful::setUp([
            "notice" => off,
        ]);
        $this->db = new \AppName\abilities\DataBase(APPNAME_BOT_DIR."/database.json");
        $this->stringer = new \AppName\abilities\stringer(APPNAME_BOT_DIR."/strings");
        $this->awaitings = new \AppName\abilities\awaitings($this->db);
        $this->info = $this->stringer->cat("bot");
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
    public function commands(object $closure)
    {
        try {
            yield $closure->__invoke();
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }

        global $time;
        $time = microtime(true);

        $awaitings["user"] = yield $this->awaitings->pullWith("user", [
            "peer" => &$update['message']['from_id'],
            "message" => &$update['message']['message'],
        ]);

        print_r($awaitings);

        $handleMessage = function ($message = false, $closure = null, array $users = []) use (&$awaitings) {
            if (
                ($message && $message != $awaitings['user']['givenData']['message'])
                || (!empty($users) && !in_array($awaitings['user']['givenData']['peer'], $users))
                ) return;

            global $time;

            $options = [ // Default options
                "peer" => &$awaitings['user']['givenData']['peer'],
                "message" => "Default message",
                'parse_mode' => 'HTML',
            ];
            unset($options['_']);

            if (gettype($closure) == "object") 
                $options = yield array_merge($options, yield $closure->__invoke());
            elseif (gettype($closure) == "array")
                $options = yield array_merge($options, $closure);
            else {
                $options['message'] .= ' in '.(microtime(true) - $time).' seconds';
            }


            yield $this->message($options);
            if (isset($options['die'])) die;
            else throw new Exception("OK");
        };
        /*
        if ($awaitings['user']['isNew']) {
            $Chat = yield $this->get_info($update);
            $user = $update['message']['from_id'];
            if (!isset($Chat['User']['first_name']) && empty($Chat['User']['first_name'])) $Chat['User']['first_name'] = null;
            if (!isset($Chat['User']['last_name']) && empty($Chat['User']['last_name'])) $Chat['User']['last_name'] = null;
            yield $this->db->query("INSERT INTO users (user, first_name, last_name) VALUES ('$user', '{$Chat['User']['first_name']}', '{$Chat['User']['last_name']}')");
        }
        #*/
        /*
        yield $this->actions(function () use (&$handleMessage) {

            yield $handleMessage(); // Test message

        });
        */
        yield $this->commands(function () use (&$handleMessage) {

            yield \AppName\bot\cluster($handleMessage);

            yield $handleMessage("/yoll", [
                "message" => "Arrrrgh..."
            ]);

        });
        #/*
        yield $this->commands(function () use (&$handleMessage, &$awaitings) {

            yield $handleMessage("/test"); // Test message

            yield $handleMessage("/updateInfo", function () use (&$awaitings) {
                if ($awaitings['user']['isOpped']) {
                    yield $this->updateInfo();
                    return $options = [
                        'peer' => $awaitings["user"]["givenData"]["peer"],
                        'message' => "Данные были обновлены",
                        'parse_mode' => 'HTML',
                    ];
                }
            });

            yield $handleMessage("/restart", function () {
                $output = yield shell_exec("php ../start.php > ../bot.log &");
                return [
                    "message" => "Restarting...",
                    "die" => true,
                ];
            }, [565324826, ]);

        });
        #*/
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

class Matter extends EventHandler
{
    public function handle (array &$update, $closure) {
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
    public function eachUser(object $closure, $random = false)
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
    public function message(array $options)
    {
        if (!isset($options['method'])) $options['method'] = null;
        try {
            switch ($options['method']) {
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
}



?>