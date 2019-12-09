<?php

ini_set('error_display', 1);
error_reporting(E_ALL);

define("APPNAME_BOT_DIR", __DIR__."/bot");

define("off", false);
define("on", true);

/**
     * Pretty colourful version of print, prints text into console
     * 
     * 1st arg is background;
     * 
     * 2nd arg is foreground;
     * 
     * e.g: BLACK+WHITE
     * 
     * Background colours: Black, Red, GREEN, YELLOW, BLUE, MAGENTA, CYAN, LIGHTGRAY
     * 
     * foreground colours: Black, DARKGRAY, RED, LIGHTRED, GREEN, LIGHTGREEN, BROWN, YELLOW, BLUE, LIGHTBLUE, MAGENTA, LIGHTMAGENTA, CAYN, LIGHTCYAN, LIGHTGRAY, WHITE
     */
class log
{
    public $string;
    public $print;
    private $settings = [
        0 => [
            'where' => 'foreground',
            'default' => "1;32;",
        ],
        1 => [
            'where' => 'background',
            'default' => "48m",
        ],
    ];
    private $foreground = [
        'BLACK' => "0;30;",
        'DARKGREY' => "1;30;",
        'RED' => "0;31;",
        'LIGHTRED' => "0;31;",
        'GREEN' => "0;32;",
        'LIGHTGREEN' => "1;32;",
        'BROWN' => "0;33;",
        'YELLOW' => "1;33;",
        'BLUE' => "0;34;",
        'LIGHTBLUE' => "1;34;",
        'MAGENTA' => "0;35;",
        'LIGHTMAGENTA' => "1;35;",
        'CYAN' => "0;36;",
        'LIGHTCYAN' => "1;36;",
        'LIGHTGRAY' => "0;37;",
        'WHITE' => "1;37;",
    ];
    private $background = [
        'BLACK' => "40m",
        'RED' => "41m",
        'GREEN' => "42m",
        'YELLOW' => "43m",
        'BLUE' => "44m",
        'MAGENTA' => "45m",
        'CYAN' => "46m",
        'LIGHTGRAY' => "47m",
    ];

    public function __construct($text, $echo = false)
    {
        $this->string = &$text;
        $this->paint();
    }

    public function formatColours(array $args)
    {
        foreach ($this->settings as $digit => $surface) {
            if (isset($args[$digit]))
                if (isset(($this->{$surface['where']})[$args[$digit]])) {
                    $colours[$surface['where']] = ($this->{$surface['where']})[$args[$digit]];
                    continue;
                }
            $colours[$surface['where']] = $surface['default'];
        }

        $colours['_'] = "\e[".$colours['foreground'].$colours['background']; // uncompleted string
        return $colours;
    }

    public function paint(string $background = null, string $foreground = null, $inline = false)
    {
        $colours = $this->formatColours([$background, $foreground]);
        $this->print = $colours['_']." ".$this->string." "."\e[0m";
        if (isset($foreground) || isset($background))
            if ($inline) $this->logln(); else $this->log();
        else return $this->print;
    }

    public function log($text = false)
    {
        if ($text) print($text.PHP_EOL); else print($this->print.PHP_EOL);
    }

    public function logln($text = false)
    {
        if ($text) print($text); else print($this->print);
    }

    public function lnlog($text = false)
    {
        if ($text) print(PHP_EOL.$text.PHP_EOL); else print(PHP_EOL.$this->print.PHP_EOL);
    }
}
/**
 * This function resembles JavaScript function 'console'
 */
function console(string $text) : object
{
    return new log($text);
}


class useful
{
    public static $settings = [
        "notice" => on,
    ];
    public $print;
    public function __construct()
    {
        console("Hello, I'm constructed and ready to help you because I'm the most useful class ever!")
        ->paint("BLACK", "LIGHTGRAY");
    }
    
    public static function date_to_words(string $date, $lang = null, $letters = null) {
        $date = date_parse_from_format("Y-m-d", $date);
        $month = [
            "1" => "Января",
            "2" => "Февраля",
            "3" => "Марта",
            "4" => "Апреля",
            "5" => "Мая",
            "6" => "Июня",
            "7" => "Июля",
            "8" => "Августа",
            "9" => "Сентября",
            "10" => "Октября",
            "11" => "Ноября",
            "12" => "Декабря",
        ][$date["month"]];
        
        return [
            "_" => "{$date["day"]}-{$month}-{$date["year"]}",
            "day" => $date["day"],
            "month" => $month,
            "year" => $date["year"],
        ];
    }
    public static function setUp(array $settings)
    {
        self::$settings = &$settings;
    }

    public static function getGeo()
    {
        $ip = shell_exec('curl https://ipinfo.io/ip');
        $geo = shell_exec('curl https://ipvigilante.com/'.$ip);
        $geo = json_decode($geo);
        #print_r($geo->data);

        return $geo->data->country_name;
    }
}
