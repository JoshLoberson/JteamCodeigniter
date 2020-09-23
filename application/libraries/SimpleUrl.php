<?php
class SimpleUrl {

    private $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private $salt = '';

    private $padding = 1;

    private $base_url = '';

    private $db_connection = null;

    public function __construct($params) {
        $this->base_url = $params['base_url'];
        $this->db_connection = $params['db_connection'];
    }

    public function set_chars($chars) {
        if (!is_string($chars) || empty($chars)) {
            throw new Exception('Wrong chars.');
        }
        $this->chars = $chars;
    }

    public function set_salt($salt) {
        $this->salt = $salt;
    }

    public function set_padding($padding) {
        $this->padding = $padding;
    }

    public function encode($n) {
        $k = 0;

        if ($this->padding > 0 && !empty($this->salt)) {
            $k = self::get_seed($n, $this->salt, $this->padding);
            $n = (int)($k.$n);
        }

        return self::num_to_alpha($n, $this->chars);
    }

    public function decode($s) {
        $n = self::alpha_to_num($s, $this->chars);

        return (!empty($this->salt)) ? substr($n, $this->padding) : $n;
    }

    public static function get_seed($n, $salt, $padding) {
        $hash = md5($n.$salt);
        $dec = hexdec(substr($hash, 0, $padding));
        $num = $dec % pow(10, $padding);
        if ($num == 0) $num = 1;
        $num = str_pad($num, $padding, '0');

        return $num;
    }

    public static function num_to_alpha($n, $s) {
        $b = strlen($s);
        $m = $n % $b;

        if ($n - $m == 0) return substr($s, $n, 1);

        $a = '';

        while ($m > 0 || $n > 0) {
            $a = substr($s, $m, 1).$a;
            $n = ($n - $m) / $b;
            $m = $n % $b;
        }

        return $a;
    }

    public static function alpha_to_num($a, $s) {
        $b = strlen($s);
        $l = strlen($a);

        for ($n = 0, $i = 0; $i < $l; $i++) {
            $n += strpos($s, substr($a, $i, 1)) * pow($b, $l - $i - 1);
        }

        return $n;
    }

    public function fetch($id) {
        $statement = $this->db_connection->prepare(
            'SELECT * FROM urls WHERE id = ?'
        );
        $statement->execute(array($id));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function find($url) {
        $statement = $this->db_connection->prepare(
            'SELECT * FROM urls WHERE url = ?'
        );
        $statement->execute(array($url));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function store($url) {
        $datetime = date('Y-m-d H:i:s');

        $statement = $this->db_connection->prepare(
            'INSERT INTO urls (url, created) VALUES (?,?)'
        );
        $statement->execute(array($url, $datetime));

        return $this->db_connection->lastInsertId();
    }

    public function update($id) {
        $datetime = date('Y-m-d H:i:s');

        $statement = $this->db_connection->prepare(
            'UPDATE urls SET hits = hits + 1, accessed = ? WHERE id = ?'
        );
        $statement->execute(array($datetime, $id));
    }

    public function redirect($url) {
        header("Location: $url", true, 301);
        exit();
    }

    public function not_found() {
        header('Status: 404 Not Found');
        exit(
            '<h1>404 Not Found</h1>'.
            str_repeat(' ', 512)
        );
    }

    public function error($message) {
        exit("<h1>$message</h1>");
    }

    /**
     * run the main proccess.
     */
    public function run() {
        $q = (isset($_GET['q']) && $_GET['q'] != null)?$_GET['q']:'';
        $q = str_replace('/', '', $q);

        $url = '';
        if (isset($_GET['url'])) {
          $url = urldecode($_GET['url']);
        }

        $format = '';
        if (isset($_GET['format'])) {
          $format = strtolower($_GET['format']);
        }

        if (!empty($url)) {
            if (preg_match('/^http[s]?\:\/\/[\w]+/', $url)) {
                $result = $this->find($url);

                if (empty($result)) {

                    $id = $this->store($url);

                    $url = $this->base_url.'/'.$this->encode($id);
                }
                else {
                    $url = $this->base_url.'/'.$this->encode($result['id']);
                }

                switch ($format) {
                    case 'text':
                        exit($url);

                    case 'json':
                        header('Content-Type: application/json');
                        exit(json_encode(array('url' => $url)));

                    case 'xml':
                        header('Content-Type: application/xml');
                        exit(implode("\n", array(
                            '<?xml version="1.0"?'.'>',
                            '<response>',
                            '  <url>'.htmlentities($url).'</url>',
                            '</response>'
                        )));

                    default:
                        exit('<a href="'.$url.'">'.$url.'</a>');
                }
            }
            else {
                $this->error('Bad input.');
            }
        }
        else {
            if (empty($q)) {
              $this->not_found();
              return;
            }

            if (preg_match('/^([a-zA-Z0-9]+)$/', $q, $matches)) {
                $id = self::decode($matches[1]);

                $result = $this->fetch($id);

                if (!empty($result)) {
                    $this->update($id);

                    $this->redirect($result['url']);
                }
                else {
                    $this->not_found();
                }
            }
        }
    }
}
