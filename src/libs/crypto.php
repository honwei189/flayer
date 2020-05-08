<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 06/05/2019 19:03:39
 * @last modified     : 23/12/2019 21:52:13
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;
use \honwei189\config as config;

/**
 *
 * AES encryptor
 *
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 */
class crypto
{
    public function __construct()
    {
        if (!config::is_loaded()) {
            config::load();
        }
    }

    /**
     * Decrypt string (static method)
     * @param string $input Encrypted string
     * @return string
     */
    public static function d($input)
    {
        if (!is_value($input)) {
            return "";
        }

        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $input[$k] = $this->decrypt(trim($v));
            }

            return $input;
        }

        $input = trim($input);

        $crypto_config = config::get("flayer", "crypto");

        if (is_array($crypto_config)) {
            $first_key  = base64_decode($crypto_config["key"]);
            $second_key = base64_decode($crypto_config["pin"]);
        } else {
            throw new \Exception("flayer config file not found.  Requires for \"key\" and \"pin\"" . PHP_EOL . PHP_EOL . "Please copy flayer.php from \"/vendor/honwei189/flayer/config/\" to \"/config/\"");
            exit;
        }

        $mix = base64_decode(strtr($input, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($input)) % 4));
        // $mix        = base64_decode($input);

        $method    = "aes-256-cbc";
        $iv_length = openssl_cipher_iv_length($method);

        $iv               = substr($mix, 0, $iv_length);
        $second_encrypted = substr($mix, $iv_length, 64);
        $first_encrypted  = substr($mix, $iv_length + 64);

        if (!(bool) $first_encrypted) {
            return $input;
        }

        $data                 = openssl_decrypt($first_encrypted, $method, $first_key, OPENSSL_RAW_DATA, $iv);
        $second_encrypted_new = hash_hmac('sha3-512', $first_encrypted, $second_key, true);
        // $second_encrypted_new = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);

        settype($second_encrypted, "string");
        settype($second_encrypted_new, "string");

        if (hash_equals($second_encrypted, $second_encrypted_new)) {
            return $data;
        }

        return $input;
    }

    /**
     * Decrypt string
     * @param string $input Encrypted string
     * @return string
     */

    public function decrypt($input)
    {
        if (!is_value($input)) {
            return "";
        }

        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $input[$k] = $this->decrypt(trim($v));
            }

            return $input;
        }

        $input = trim($input);

        $crypto_config = config::get("flayer", "crypto");

        if (is_array($crypto_config)) {
            $first_key  = base64_decode($crypto_config["key"]);
            $second_key = base64_decode($crypto_config["pin"]);
        } else {
            throw new \Exception("flayer config file not found.  Requires for \"key\" and \"pin\"" . PHP_EOL . PHP_EOL . "Please copy flayer.php from \"/vendor/honwei189/flayer/config/\" to \"/config/\"");
            exit;
        }

        $mix = base64_decode(strtr($input, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($input)) % 4));
        // $mix        = base64_decode($input);

        $method    = "aes-256-cbc";
        $iv_length = openssl_cipher_iv_length($method);

        $iv               = substr($mix, 0, $iv_length);
        $second_encrypted = substr($mix, $iv_length, 64);
        $first_encrypted  = substr($mix, $iv_length + 64);

        if (!(bool) $first_encrypted) {
            return $input;
        }

        $data                 = openssl_decrypt($first_encrypted, $method, $first_key, OPENSSL_RAW_DATA, $iv);
        $second_encrypted_new = hash_hmac('sha3-512', $first_encrypted, $second_key, true);
        // $second_encrypted_new = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);

        settype($second_encrypted, "string");
        settype($second_encrypted_new, "string");

        if (hash_equals($second_encrypted, $second_encrypted_new)) {
            return $data;
        }

        return $input;
    }

    /**
     * Decrypt string with simple mode (static method)
     * @param string $data Encrypted string
     * @param string $key Password to encrypt
     * @param integer $length Encrypt length
     * @return string
     */
    public static function decrypt_simple($data, $key = "", $length = 16)
    {
        /*
        $Key = Your Unique Secret Key/Token (Encrypt key) to decrypt the hash.  E.g: Who Am i
         */

        $encrypt_method = "AES-256-CBC";
        // hash
        $key = hash('sha256', $key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', APP), 0, $length);
        return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', openssl_decrypt(base64_decode($data), $encrypt_method, $key, 0, $iv)));
    }

    /**
     * Encrypt the string (static method)
     *
     * Encryption result will always changed
     *
     * Encrypt algorithm = SHA3-512 + AES-256-CBC + random pseudo bytes + SPECIAL KEY + base64
     *
     * @param string $data
     * @return string
     */
    public static function e($data)
    {
        if (!is_value($data)) {
            return "";
        }

        $crypto_config = config::get("flayer", "crypto");

        if (is_array($crypto_config)) {
            $first_key  = base64_decode($crypto_config["key"]);
            $second_key = base64_decode($crypto_config["pin"]);
        } else {
            throw new \Exception("flayer config file not found.  Requires for \"key\" and \"pin\"" . PHP_EOL . PHP_EOL . "Please copy flayer.php from \"/vendor/honwei189/flayer/config/\" to \"/config/\"");
            exit;
        }

        unset($crypto_config);

        $method    = "aes-256-cbc";
        $iv_length = openssl_cipher_iv_length($method);
        $iv        = openssl_random_pseudo_bytes($iv_length);

        $first_encrypted  = openssl_encrypt($data, $method, $first_key, OPENSSL_RAW_DATA, $iv);
        $second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, true);

        // $second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);
        // $output = base64_encode($iv . $second_encrypted . $first_encrypted);
        // return base64_encode($iv.$first_encrypted);

        $output = rtrim(strtr(base64_encode($iv . $second_encrypted . $first_encrypted), '+/', '-_'), '=');
        return $output;
    }

    /**
     * Encrypt the string
     *
     * Encryption result will always changed
     *
     * Encrypt algorithm = SHA3-512 + AES-256-CBC + random pseudo bytes + SPECIAL KEY + base64
     *
     * @param string $data
     * @return string
     */
    public function encrypt($data)
    {
        if (!is_value($data)) {
            return "";
        }

        $crypto_config = config::get("flayer", "crypto");

        if (is_array($crypto_config)) {
            $first_key  = base64_decode($crypto_config["key"]);
            $second_key = base64_decode($crypto_config["pin"]);
        } else {
            throw new \Exception("flayer config file not found.  Requires for \"key\" and \"pin\"" . PHP_EOL . PHP_EOL . "Please copy flayer.php from \"/vendor/honwei189/flayer/config/\" to \"/config/\"");
            exit;
        }

        unset($crypto_config);

        $method    = "aes-256-cbc";
        $iv_length = openssl_cipher_iv_length($method);
        $iv        = openssl_random_pseudo_bytes($iv_length);

        $first_encrypted  = openssl_encrypt($data, $method, $first_key, OPENSSL_RAW_DATA, $iv);
        $second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, true);

        // $second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);
        // $output = base64_encode($iv . $second_encrypted . $first_encrypted);
        // return base64_encode($iv.$first_encrypted);

        $output = rtrim(strtr(base64_encode($iv . $second_encrypted . $first_encrypted), '+/', '-_'), '=');
        return $output;
    }

    /**
     * Encrypt the string with simple mode(static method)
     *
     * @param string $data String to encrypt
     * @param string $key Password to encrypt
     * @param integer $length Encrypt length
     * @return string
     */
    public static function encrypt_simple($data, $key = "", $length = 16)
    {
        /*
        $Key = Your Unique Secret Key/Token to encrypt words.  E.g: Who Am i

        Encryption result will always fixed (e.g:  encrypt number = 1, result will be hashed.  However, if encrypt another number also 1, the result maybe will same as previous)
         */

        $encrypt_method = "AES-256-CBC";
        // hash
        $key  = hash('sha256', $key);
        $data = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data));

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', APP), 0, $length);
        return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', base64_encode(openssl_encrypt($data, $encrypt_method, $key, 0, $iv))));
    }

    /**
     * Get plaintext.
     *
     * If $string is encrypted, decrypt and return
     *
     * @param string $string
     * @return string
     */
    public function get($string = null)
    {
        if (is_null($string)) {
            return $string;
        } else if (is_value($string)) {
            if (!is_numeric($string)) {
                $id = $this->decrypt($string);
            }

            return $string;
        }
    }
}
