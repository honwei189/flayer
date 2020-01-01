<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 05/05/2019 21:38:39
 * @last modified     : 23/12/2019 21:51:29
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

/**
 *
 * Container
 *
 * This is copied from https://github.com/krasimir/php-dependency-injection/
 *
 * And modified by honwei189
 *
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @link        https://appsw.dev
 * @link        https://justtest.app
 * @link        https://github.com/krasimir/php-dependency-injection/
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 */
class container
{
    private static $instance;

    public static function all()
    {
        return self::$instance;
    }

    /**
     * @access private
     * @internal
     */

    /**
     * Bind classes into container, and also inject sub-classes into parent class
     *
     * @param string $className Class name
     * @param string $alias_name Alias name
     * @param string|array $arguments Class method/constructor's arguements
     * @return object
     */
    public static function build($className, $alias_name = null, $arguments = null)
    {
        $requires = "";
        // checking if the class exists
        if (!class_exists($className)) {
            // echo "Unknown class '" . $className . "'." . PHP_EOL;
            // throw new \Exception("Unknown class '" . $className . "'.");
            throw die("Unknown class '" . $className . "'." . PHP_EOL);
        }

        // initialized the ReflectionClass
        $reflection = new \ReflectionClass($className);

        // creating an instance of the class
        if ($arguments === null || count($arguments) == 0) {
            $obj = new $className;
        } else {
            if (!is_array($arguments)) {
                $arguments = array($arguments);
            }
            $obj = $reflection->newInstanceArgs($arguments);
        }

        // $className = substr($className, strrpos($className, '\\') + 1);

        // injecting.  Get all required classes and create instance
        try {
            // var_dump($reflection->getProperty('require')->getValue($reflection));
            $requires = \Closure::bind(function ($prop) {return $this->$prop;}, $obj, $obj)("require");

            if (is_array($requires)) {
                foreach ($requires as $k => $v) {
                    if (strrpos($v, '\\') !== false) {
                        $require_class = substr($v, strrpos($v, '\\') + 1);
                    } else {
                        $require_class = $v;
                    }

                    $obj->$require_class = self::build($v, null, null);
                    // if (self::$instance->$require_class->instance === null) {
                    //     $obj->$require_class = self::$instance->$require_class->instance = self::build($v, null);
                    // } else {
                    //     $obj->$require_class = self::$instance->$require_class->instance;
                    // }
                }

            }
        } catch (\Exception $e) {

        }

        // injecting.  Find all required classes and create instance
        if ($doc = $reflection->getDocComment()) {
            $lines = explode("\n", $doc);
            foreach ($lines as $line) {
                if (count($parts = explode("@require", $line)) > 1) {
                    $parts = explode(" ", $parts[1]);
                    if (count($parts) > 1) {
                        $key           = $parts[1];
                        $key           = str_replace("\n", "", $key);
                        $key           = str_replace("\r", "", $key);
                        $require_class = substr($key, strrpos($key, '\\') + 1);

                        if (isset(self::$instance->$require_class)) {
                            switch (self::$instance->$require_class->type) {
                                case "value":
                                    $obj->$require_class = self::$instance->$require_class->value;
                                    break;
                                case "class":
                                    $obj->$require_class = self::build(self::$instance->$require_class->value, self::$instance->$require_class->alias_name, self::$instance->$require_class->arguments);
                                    break;
                                case "classSingleton":
                                    if (self::$instance->$require_class->instance === null) {
                                        $obj->$require_class = self::$instance->$require_class->instance = self::build(self::$instance->$require_class->value, self::$instance->$require_class->alias_name, self::$instance->$require_class->arguments);
                                    } else {
                                        $obj->$require_class = self::$instance->$require_class->instance;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }

        self::store($obj, $alias_name);
        return $obj;
    }

    public static function bind_value($key, $value)
    {
        self::register($key, (object) array(
            "value" => $value,
            "type"  => "value",
        ));
    }

    public static function bind_class($key, $value, $alias_name = null, $arguments = null)
    {
        self::register($key, (object) array(
            "value"      => $value,
            "type"       => "class",
            "arguments"  => $arguments,
            "alias_name" => $alias_name,
        ));
    }

    public static function bind_classAsSingleton($key, $value, $alias_name = null, $arguments = null)
    {
        self::register($key, (object) array(
            "value"      => $value,
            "type"       => "classSingleton",
            "instance"   => null,
            "arguments"  => $arguments,
            "alias_name" => $alias_name,
        ));
    }

    public static function get($key)
    {
        if (isset(self::$instance->$key)) {
            return self::$instance->$key->instance;
        }
    }

    public static function store($instance, $alias_name = null)
    {
        // $reflect = new ReflectionClass($instance);
        // echo $reflect->getShortName();

        $class = get_class($instance);

        if (strrpos($class, '\\') !== false) {
            $class = substr($class, strrpos($class, '\\') + 1);
        }

        if (!empty($alias_name) && !is_null($alias_name)) {
            $class = $alias_name;
        }

        self::register($class, (object) array(
            "value"      => "",
            "type"       => "classSingleton",
            "instance"   => $instance,
            "arguments"  => null,
            "alias_name" => null,
        ));

        return self::$instance->$class->instance;
    }

    private static function register($key, $obj)
    {
        if (self::$instance === null) {
            self::$instance = (object) array();
        }
        self::$instance->$key = $obj;
    }
}
