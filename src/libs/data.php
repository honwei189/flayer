<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 21/04/2019 21:15:39
 * @last modified     : 06/06/2020 15:11:37
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

include_once "utilities.php";

/**
 * Get / Set global data
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @version     "1.0.0"
 * @since       "1.0.0"
 * @copyright   MIT License (MIT), 2019 honwei189
 */
class data
{
    static $data = [];
    static $_user;

    /**
     * Get all saved global data
     *
     * @return array
     */
    public static function all()
    {
        return self::$data;
    }

    /**
     * Clear all global data
     *
     */
    public static function clear()
    {
        self::$data = null;
    }

    /**
     * Get global data
     *
     * @param string $key_name Key name / Variable name
     * @return string
     */
    public static function get($key_name)
    {
        if ($key_name == "_user" || $key_name == "user") {
            return self::$_user;
        } else {
            return (isset(self::$data[$key_name]) && is_value(self::$data[$key_name]) ? self::$data[$key_name] : "");
        }
    }

    /**
     * Set global data
     *
     * @param string $key_name Key name / Variable name
     * @param string $value Value
     * @return string
     */
    public static function set($key_name, $value)
    {
        if ($key_name == "_user" || $key_name == "user") {
            self::$_user = $value;
        } else {
            self::$data[$key_name] = $value;
        }
    }

    /**
     * Set bulk global data
     *
     * @param array|string $key_name Key name / Variable name
     * @param array|string $value Value
     */
    public static function setMany($key_name, $value)
    {
        if (isset($key_name) && is_array($key_name) && count($key_name) > 0) {
            foreach ($key_name as $k => $v) {
                self::set($k, (isset($value) && is_array($value) && $value[$k] > 0 ? $value[$k] : $value));
            }
        } else {
            self::set($key_name, $value);
        }
    }
}
