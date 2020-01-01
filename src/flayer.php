<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 05/05/2019 17:45:39
 * @last modified     : 23/12/2019 21:56:37
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

include_once "libs/utilities.php";

/**
 *
 * layer.  This is to implement deeper services like container and injection, class services and also provides some utility functions
 *
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @link        https://appsw.dev
 * @link        https://justtest.app
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 */
class flayer
{
    /**
     * @access private
     * @internal
     */
    public function __construct()
    {
        $args = func_get_args();
        foreach ($args as $index => $arg) {
            if (is_object($arg)) {
                container::store($arg);
            } else {
                container::bind_class($arg, "");
            }

            unset($args[$index]);
        }
    }

    /**
     * Bind external classes into Flayer
     *
     * @param string $class Class name
     * @param string $alias_name Alias name
     * @return object
     */
    public function __bind($class, $alias_name = null)
    {
        return container::build($class, $alias_name);
    }

    public function __call($name, $arguments)
    {
        switch ($name) {
            case "bind":
                if (is_object($arguments[0])) {
                    return container::store($arguments[0], (isset($arguments[1]) && is_value($arguments[1]) ? $arguments[1] : null));
                } else {
                    return $this->__bind($arguments[0], (isset($arguments[1]) && is_value($arguments[1]) ? $arguments[1] : null));
                }

                break;

            case "exists":
                if (is_object(container::get($arguments[0]))) {
                    return true;
                } else {
                    return false;
                }
                break;

            case "get":
                if (isset($arguments[1]) && is_value($arguments[1])) {
                    $name = $arguments[1];
                } else {
                    $name = $arguments[0];
                }
                break;

            default:
                break;
        }

        if (is_object(container::get($name))) {
            return container::get($name);
        }
    }

    public static function __callStatic($name, $arguments = null)
    {
        switch ($name) {
            case "bind":
                if (is_object($arguments[0])) {
                    return forward_static_call_array(
                        array(__NAMESPACE__ . '\container', 'store'),
                        $arguments
                    );
                } else {
                    return forward_static_call_array(
                        array(__NAMESPACE__ . '\container', 'build'),
                        $arguments
                    );
                }

                break;

            case "exists":
                if (is_object(container::get($arguments[0]))) {
                    return true;
                } else {
                    return false;
                }
                break;

            case "get":
                if (isset($arguments[1]) && is_value($arguments[1])) {
                    $name = $arguments[1];
                } else {
                    $name = $arguments[0];
                }
                break;

            default:
                break;
        }

        if (is_object(container::get($name))) {
            return container::get($name);
        } else {
            if (isset($name) && is_value($name) && $name != "get") {
                return container::build(__NAMESPACE__ . "\\$name");
            }
        }
    }

    public function __invoke()
    {
        //Call your external function here
    }
}
