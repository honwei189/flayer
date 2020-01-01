<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 09/10/2019 21:15:39
 * @last modified     : 23/12/2019 21:50:33
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

include_once "utilities.php";

/**
 * Load and use configuration files from ./config/
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @link        https://appsw.dev
 * @link        https://justtest.app
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 * @copyright   MIT License (MIT), 2019 honwei189
 */
class config
{
    static $config = [];

    /**
     * Get all config data
     *
     * @return array
     */
    public static function all()
    {
        return self::$config;
    }

    /**
     * Clear all loaded config data
     *
     */
    public static function clear()
    {
        self::$config = null;
    }

    /**
     * Get config data
     *
     * @param string $section_name Config file name.  e.g:  db.php, echo $section_name // output: db
     * @param string $config_key_name Key name.  e.g:  echo $config_key_name // output: DB_HOST
     * @return string
     */
    public static function get($section_name, $config_key_name)
    {
        return (isset(self::$config[$section_name][$config_key_name]) ? self::$config[$section_name][$config_key_name] : "");
    }

    /**
     * Load specific or all config files from ./config/
     *
     * @param array $config_file_name Config file name
     */
    public static function load($config_file_name = null)
    {
        if (count(self::$config) == 0) {
            $path = "";

            if (php_sapi_name() == "cli") {
                $path = realpath(__DIR__ . (isset($_SERVER['SHELL']) ? "/../../../../../" : "../../../../../../../")) . DIRECTORY_SEPARATOR;
            } else {
                $path = substr_replace($_SERVER['DOCUMENT_ROOT'], "", strrpos($_SERVER['DOCUMENT_ROOT'], "public"));
            }

            if (is_value($config_file_name)) {
                self::$config[$config_file_name] = include_once $path . "config" . DIRECTORY_SEPARATOR . $config_file_name . ".php";

                if (self::$config[$config_file_name] == 1) {
                    self::$config[$config_file_name] = include $path . "config" . DIRECTORY_SEPARATOR . $config_file_name . ".php";
                }
            } else {
                foreach (glob($path . "/config/*.php") as $filename) {
                    $section                = str_replace(".php", "", basename($filename));
                    self::$config[$section] = include_once $filename;

                    if (self::$config[$section] == 1) {
                        self::$config[$section] = include $filename;
                    }
                }
            }
        }
    }

    /**
     * Check is config file is loaded
     *
     * @return boolean
     */
    public static function is_loaded()
    {
        if (count(self::$config) == 0) {
            return false;
        }

        return true;
    }

    /**
     * set
     * @param string $section_name Config file name.  e.g:  db.php, echo $section_name // output: db
     * @param string $config_key_name Key name.  e.g:  echo $config_key_name // output: DB_HOST
     * @param string $value Data value
     */
    public static function set($section_name, $config_key_name, $value)
    {
        self::$config[$section_name][$config_key_name] = $value;
    }

    /**
     * Set bulk config data
     *
     * @param string $section_name
     * @param array|string $config_key_name
     * @param array|string $value
     */
    public static function setMany($section_name, $config_key_name, $value)
    {
        if (isset($config_key_name) && is_array($config_key_name) && count($config_key_name) > 0) {
            foreach ($config_key_name as $k => $v) {
                self::set($section_name, $v, (isset($value) && is_array($value) && $value[$k] > 0 ? $value[$k] : $value));
            }
        } else {
            self::set($section_name, $config_key_name, $value);
        }
    }
}
