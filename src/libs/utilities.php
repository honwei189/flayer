<?php
/*
 * @description       : Utilities function
 * @version           : "1.0.1" 22/03/2020 14:26:50 Remove vendor namespace to prevent others package not belong to same vendor, unable to use it
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 13/11/2019 19:23:24
 * @last modified     : 22/03/2020 14:43:31
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

// namespace honwei189;

use \honwei189\flayer as flayer;
use \honwei189\crypto as crypto;

if (!function_exists("auto_date")) {
    function auto_date($date, $format = "")
    {
        $dateStr = null;
        $date    = trim(html_entity_decode(preg_replace('~\x{00a0}~u', ' ', $date)));
        preg_match_all("/[\/-]/", $date, $reg);
        if (preg_match("/(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})/", $date)) {
            // search found YYYY-mm-dd format
            if ($reg[0][0] == $reg[0][1]) {
                $dateExplode = explode($reg[0][0], $date);
                if (trim($format) == "") {
                    $format = "d/m/Y";
                }

                $dateStr = date($format, mktime(0, 0, 0, (int) $dateExplode[1], (int) $dateExplode[2], (int) $dateExplode[0]));
            }
        } elseif (preg_match("/([0-9]{1,2})[\/-]([0-9]{1,2})[\/-]([0-9]{4})/", $date)) {
            // search found dd/mm/YYYY format
            if ($reg[0][0] == $reg[0][1]) {
                $dateExplode = explode($reg[0][0], $date);

                if (trim($format) == "") {
                    $format = "Y-m-d";
                }

                $dateStr = date($format, mktime(0, 0, 0, (int) $dateExplode[1], (int) $dateExplode[0], (int) $dateExplode[2]));
            }
        } else {
            echo "";
        }

        return $dateStr;
    }
}

/*
 * Inserts a new key/value before the key in the array.
 *
 * @param $key
 *   The key to insert before.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, FALSE otherwise.
 *
 * @see array_insert_after()
 */
if (!function_exists("array_insert_before")) {
    function array_insert_before($key, array &$array, $new_key, $new_value)
    {
        if (array_key_exists($key, $array)) {
            $new = array();
            foreach ($array as $k => $value) {
                if ($k === $key) {
                    $new[$new_key] = $new_value;
                }
                $new[$k] = $value;
            }
            return $new;
        }
        return false;
    }
}

/*
 * Inserts a new key/value after the key in the array.
 *
 * @param $key
 *   The key to insert after.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, FALSE otherwise.
 *
 * @see array_insert_before()
 */
if (!function_exists("array_insert_after")) {
    function array_insert_after($key, array &$array, $new_key, $new_value)
    {
        if (array_key_exists($key, $array)) {
            $new = array();
            foreach ($array as $k => $value) {
                $new[$k] = $value;
                if ($k === $key) {
                    $new[$new_key] = $new_value;
                }
            }
            return $new;
        }
        return false;
    }

}

/**
 * Check user privilege able to access the function or not
 *
 * @param mixed $code Privilege code.  e.g: PTS00001
 * @return boolean TRUE or FALSE
 */
if (!function_exists("check_access_privilege")) {
    function check_access_privilege($code)
    {
        if (isset($this->_user_access_privilege[$code])) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists("contains")) {
    function contains($string, $keyword)
    {
        //return strpbrk($string, $keyword);

        $pos = strpos($string, $keyword);

        if ($pos === false) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists("data_combo")) {
    function data_combo($combo_name, $sql, $default_value = null, $prefix_empty_option = true, $extra = "", $extra_sql = null)
    {
        $data = flayer::fdo()->read_all_sql($sql, false, \PDO::FETCH_BOTH);

        $combo = "<select name=\"$combo_name\" class=\"form-control\"" . $extra . ">\n";
        if ($prefix_empty_option) {
            $combo .= "<option></option>\n";
        }

        for ($i = 0; $max = count($data), $i < $max; ++$i) {
            if (is_value($default_value)) {
                if ($default_value == substr($data[$i][0], 0, strlen($default_value))) {
                    $default = " selected";
                } else {
                    $default = "";
                }
            } else {
                if ($default_value == $data[$i][0]) {
                    $default = " selected";
                } else {
                    $default = "";
                }
            }

            $combo .= "<option value=\"" . $data[$i][0] . "\"$default>" . $data[$i][1] . "</option>\n";
        }
        $combo .= "</select>";

        return $combo;

    }

}

if (!function_exists("data_description")) {
    function data_description($sql)
    {
        $data = flayer::fdo()->read_one_sql($sql, false, \PDO::FETCH_BOTH);
        return $data[0];
    }
}

if (!function_exists("error")) {
    function error($title, $contents){
        throw die("<pre><strong style=\"color: red;\">$title</strong>" . PHP_EOL . "<br><br><section style=\"background-color: #f1f1f1; padding: 5px;\">$contents</section><br><br></pre>");
    }
}

if (!function_exists("get_ip")) {
    function get_ip()
    {
        $ip = '';

        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } else if (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } else if (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

}

if (!function_exists("is_assoc_array")) {
    /**
     * Check if PHP array is associative or sequential
     *
     * e.g:
     *
     * $a['a'] = "A";
     * $a['b'] = "B";
     *
     * var_dump(is_assoc_array($a)); //output = true
     *
     *
     * $a[] = "A";
     * $a[] = "B";
     *
     * var_dump(is_assoc_array($a)); //output = false
     *
     * @param array $array
     * @return boolean
     */
    function is_assoc_array($array)
    {
        if (array() === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists("is_multi_array")) {
    function is_multi_array($arr)
    {
        rsort($arr);
        return isset($arr[0]) && is_array($arr[0]);
    }
}

if (!function_exists("is_num")) {
    function is_num($var)
    {
        return (isset($var) && is_numeric($var) ? true : false);
    }
}

if (!function_exists("is_string_keys")) {
    /**
     * Check if PHP array is associative or sequential.  Alternative function of is_assoc_array()
     *
     * e.g:
     *
     * $a['a'] = "A";
     * $a['b'] = "B";
     *
     * var_dump(is_string_keys($a)); //output = true
     *
     *
     * $a[] = "A";
     * $a[] = "B";
     *
     * var_dump(is_string_keys($a)); //output = false
     *
     * @param array $array
     * @return boolean
     */
    function is_string_keys(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}

if (!function_exists("is_tf")) {
    function is_tf($var)
    {
        return (isset($var) && is_bool($var) ? true : false);
    }

}

if (!function_exists("is_value")) {
    function is_value(&$var)
    {
        return (!is_array($var) && !is_null($var) && $var !== "" ? true : false);
    }
}

if (!function_exists("out")) {
    function out($var)
    {
        pre($var);
        echo "<script id=\"__bs_script__\">//<![CDATA[
    document.write(\"<script async src='/browser-sync/browser-sync-client.js?v=2.26.7'><\/script>\".replace(\"HOST\", location.hostname));
//]]></script>";
        exit;
    }
}

if (!function_exists("pre")) {
    function pre($array)
    {
        if (php_sapi_name() == "cli") {
            print_r($array);
        } else {
            echo "<pre>";
            print_r($array);
            echo "</pre>";
        }
    }
}

if (!function_exists("param_data_combo")) {
    function param_data_combo($combo_name, $type, $default_value = null, $prefix_empty_option = true, $extra = "", $extra_sql = null)
    {
        if (method_exists($this, "read_all_sql")) {
            $data  = flayer::fdo()->read_all_sql("select cd, dscpt from params where param='$type' $extra_sql and status='A'");
            $combo = "<select name=\"$combo_name\" class=\"form-control\"" . $extra . ">\n";
            if ($prefix_empty_option) {
                $combo .= "<option></option>\n";
            }

            for ($i = 0; $max = count($data), $i < $max; ++$i) {
                if (is_value($default_value)) {
                    if ($default_value == substr($data[$i]['cd'], 0, strlen($default_value))) {
                        $default = " selected";
                    } else {
                        $default = "";
                    }
                } else {
                    if ($default_value == $data[$i]['cd']) {
                        $default = " selected";
                    } else {
                        $default = "";
                    }
                }

                $combo .= "<option value=\"" . $data[$i]['cd'] . "\"$default>" . $data[$i]['dscpt'] . "</option>\n";
            }
            $combo .= "</select>";

            return $combo;
        } else {
            die("You are requires to load \\honwei189\\fdo\\fdo");
        }
    }
}

if (!function_exists("param_data_description")) {
    function param_data_description($param, $cd)
    {
        if (method_exists($this, "read_one_sql")) {
            $data = flayer::fdo()->read_one_sql("select dscpt from params where param = '$param' and cd = '$cd'", false, \PDO::FETCH_BOTH);
            return $data[0];
        } else {
            die("You are requires to load \\honwei189\\fdo\\fdo");
        }
    }
}

/**
 * Return formatted data
 * @param boolean $bool Process status.  1 = True, 0 = Failure
 * @param string $dscpt Reference data / description
 * @param string|array $additional Specific status code and additional messages.
 *
 * $additional = ["STATUS_CODE", "ADDITIONAL_MESSAGES"];
 *
 * e.g:  $additional = ["S0007", "The following files can't be imported ..... "]
 *
 * e.g:  $additional = ["S0001"]
 *
 * e.g:  $additional = "S0001"
 *
 * @var mixed
 */
if (!function_exists("rtn")) {
    function rtn($bool, $dscpt = null, $additional = null)
    {
        // if ($bool && is_null($dscpt)){
        //     $dscpt = "SF000";
        // }

        if (!is_null($additional) && (is_value($additional) || is_array($additional))) {
            return [(int) $bool, $dscpt, $additional];
        } else {
            return [(int) $bool, $dscpt];
        }
    }
}

/**
 * Return formatted string value
 *
 * @param string $string
 * @param string $type Value type.  e.g: crypt, number, date
 * @return string
 */
if (!function_exists("value_format")) {
    function value_format($string, $type = "")
    {
        switch ($type) {
            case "crypt":
                $string = crypto::d($string);
                break;

            case "date":
                $string = auto_date($string);
                break;

            case "number":
                $string = (int) $string;
                break;
        }

        return $string;
    }
}

/**
 * Send email
 *
 * @param string $from Sender name & email.  e.g:  XXX <no-reply@xxx.com>
 * @param string $to Recipient  e.g:  XXX <abc@xxx.com>
 * @param string $subject Email title
 * @param string $message Email contents
 */
if (!function_exists("send_mail")) {
    function send_mail($from, $to, $subject, $message)
    {
        $xsender = "";
        preg_match_all('/\s*"?([^><,"]+)"?\s*((?:<[^><,]+>)?)\s*/', $from, $matches, PREG_SET_ORDER);

        if (isset($matches) && is_array($matches) && count($matches) > 0) {
            if (isset($matches[0][2]) && trim($matches[0][2]) == "") {
                $xsender = $matches[0][1];
            } else {
                $xsender = trim($matches[0][2], '<>');
            }
        }

        unset($matches);

        if (!is_value($xsender)) {
            $xsender = $from;
        }

        $headers = "From: $from\r\n";
        $headers .= "X-Sender: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        //$headers .= "X-Mailer: PHP/". phpversion()."\r\n";
        $headers .= "X-Mailer: \r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        //$headers .= 'Content-Transfer-Encoding: 8bit' . "\r\n";

        mail($to, $subject, $message, $headers, "-f$xsender");
    }
}

/**
 * Check against the session is the user rise same action within the minutes
 *
 * @param string $key_name Provide an unique key name to identifier the action
 * @param integer $minutes
 * @return boolean True = Session passed, False = Not valid, within the minutes
 */
if (!function_exists("session_check")) {
    function session_check($key_name, $minutes = 1)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $class = get_called_class();
        // session_destroy();

        $hash = crypto::e($this->_user);

        if (isset($_SESSION["session_worker_$hash"][$class][$key_name])) {
            $to_time   = time();
            $from_time = $_SESSION["session_worker_$hash"][$class][$key_name];

            if (round(abs($to_time - $from_time) / 60, 2) > 1) {
                //1 minutes or above
                $_SESSION["session_worker_$hash"][$class][$key_name] = time();
            } else {
                return false;
            }
        } else {
            // $_SESSION[$class][$this->_table][$this->_user][$key_name] = time();
            $_SESSION["session_worker_$hash"][$class][$key_name] = time();
        }

        return true;
    }
}

/**
 * Remove duplicate words from string
 *
 * @param string $str Words would like to check duplicates and remove
 * @return string
 */
if (!function_exists("str_unique")) {
    function str_unique($str)
    {
        $str   = preg_replace("/([,.?!])/", " \\1 ", $str);
        $parts = explode(" ", $str);

        foreach ($parts as $k => $v) {
            if (preg_match('/[^a-zA-Z\d]/', $v)) {
                $parts[$k] = "{{" . $v . ":" . $k . "}}";
            }
        }

        $str = array_unique($parts);
        $str = implode(" ", $str);
        $str = preg_replace("/\{\{(.*?):[0-9]{0,20}\}\}/i", "$1", $str);
        $str = preg_replace("/\s([,.?!])/", "\\1", $str);

        return $str;
    }
}