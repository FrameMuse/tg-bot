<?php namespace AppName\abilities;

/**
 * Stringer simplyfize data maintaining
 * 
 * Enter the path to folder where there are files connected to string has call-name and defined for.
 * 
 * Each file should be in JSON fromat.
 * 
 * Max. number of attachments is 2 
 */
class stringer
{
    private $folder;
    private $files;
    public function __construct(string $folder = "strings") {
        $this->folder = $folder;
        $this->readDirectory($folder);
    }
    private function readJSON(string $file_name, bool $isArray = false)
    {
        $file_path = $this->folder . "/" . $file_name . ".json";
        $file_rough_data = file_get_contents($file_path);
        $file_data = json_decode($file_rough_data, $isArray);
        if (!$isArray) $this->files[$file_name] = $file_data;
        return $file_data;
    }
    private function readDirectory(string $folder)
    {
        $files_in_dir = scandir($folder);
        unset($files_in_dir[0], $files_in_dir[1]);
        foreach ($files_in_dir as $file_name) {
            $file = explode(".", $file_name);
            if ($file[1] == "json") {
                $this->readJSON($file[0]);
            }
        }
    }
    public function cat(string $which) : object
    {
        return $this->files[$which];
    }
    public function alter(string $file, array $changes)
    {
        $reads = $this->readJSON($file, true);
        $changes = array_merge($reads, $changes);

        $file_path = $this->folder . "/" . $file . ".json";
        file_put_contents($file_path, json_encode($changes, JSON_PRETTY_PRINT));

        return $changes;
    }
    public function code(string $file, array $variables)
    {
        $changes = $this->readJSON($file, true);
        foreach ($variables as $var => $value) {
            foreach ($changes as $string => $data) {
                if (gettype($data) == "array")
                    foreach ($data as $string2 => $data2) {
                        $changes[$string][$string2] = str_replace($var, $value, $data2);
                    }
                else
                    $changes[$string] = str_replace($var, $value, $data);
            }
        }
        return $changes;
    }
}


?>