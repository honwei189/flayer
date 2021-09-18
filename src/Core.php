<?php
/**
 * Created       : 2019-05-05 05:45:39 am
 * Author        : Gordon Lim
 * Last Modified : 2021-09-18 04:12:43 pm
 * Modified By   : Gordon Lim
 * ---------
 * Changelog
 * 
 * Date & time           By                    Version   Comments
 * -------------------   -------------------   -------   ---------------------------------------------------------
 * 
*/

namespace honwei189\Flayer;

include_once "Helpers.php";

/**
 *
 * This is to provides deeper services like container and injection, class services and also provides some utility / useful functions
 *
 *
 * @package     Flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/flayer/
 * @version     "1.0.0"
 * @since       "1.0.0"
 */
class Core
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
                Container::store($arg);
            } else {
                Container::bind_class($arg, "");
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
        return Container::build($class, $alias_name);
    }

    public function __call($name, $arguments)
    {
        switch ($name) {
            case "bind":
                if (is_object($arguments[0])) {
                    return Container::store($arguments[0], (isset($arguments[1]) && is_value($arguments[1]) ? $arguments[1] : null));
                } else {
                    return $this->__bind($arguments[0], (isset($arguments[1]) && is_value($arguments[1]) ? $arguments[1] : null));
                }

                break;

            case "exists":
                if (is_object(Container::get($arguments[0]))) {
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

        if (is_object(Container::get($name))) {
            return Container::get($name);
        }
    }

    public static function __callStatic($name, $arguments = null)
    {
        switch ($name) {
            case "bind":
                if (is_object($arguments[0])) {
                    return forward_static_call_array(
                        array(__NAMESPACE__ . '\Container', 'store'),
                        $arguments
                    );
                } else {
                    return forward_static_call_array(
                        array(__NAMESPACE__ . '\Container', 'build'),
                        $arguments
                    );
                }

                break;

            case "exists":
                if (is_object(Container::get($arguments[0]))) {
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

        if (is_object(Container::get($name))) {
            return Container::get($name);
        } else {
            if (isset($name) && is_value($name) && $name != "get") {
                return Container::build(__NAMESPACE__ . "\\$name");
            }
        }
    }

    public function __invoke()
    {
        //Call your external function here
    }
}
