<?php namespace AppName\abilities;

class awaitings
{
    public function __construct(object $db) {
        $this->db = &$db;
    }
    public function pullWith(string $function, array $settings)
    {
        $options = yield $this->$function($settings);
        $this->options[$function] = $options;
        return $options;
    }
    private function user(array $settings)
    {
        try {
            yield $this->db->ping();
            $result = yield $this->db->query("SELECT * FROM users WHERE user = '{$settings['peer']}'");
            $row = yield $result->fetch_assoc();

            return [
                'givenData' => &$settings,
                'isNew' => $result->num_rows == 0,
                'hasName' => $row['phone'] != NULL,
                'name' => $row['phone'],
                'isOpped' => $row['extra'] == "opped",
            ];
        } catch (\Exception $e) {
            print $e->getMessage();
        }
    }
}

?>
