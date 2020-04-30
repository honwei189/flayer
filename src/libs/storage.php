<?php
/*
 * @version           : "1.0.0"
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 15/04/2020 11:02:52
 * @last modified     : 30/04/2020 21:00:41
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

/**
 *
 * Bridge to store files to disk and save records to database
 * 
 * Usages:
 * 
 * storage::path("product_files", "product/{{ ref_id }}");
 * 
 * storage::save($_FILES['file']);
 * 
 * storage::db_table("product_files")->path("product/{{ ref_id }}")->save($_FILES['file'], 1);
 * 
 * storage::set(["ref_id" => 1, "ref_name" => "product"]);
 * 
 * or;
 * 
 * storage::set_ref_id(1);
 * 
 * 
 * 
 * To save files:
 * 
 * 
 * #Save data into table = product_files, and save table's column ref_id, ref_name
 * 
 * storage::db_table("product_files")->set(["ref_id" => 1, "ref_name" => "product"])->path("product/{{ ref_id }}");
 * 
 * or;
 * 
 * storage::db_table("product_files")->save(); // Save file to default location
 * 
 * storage::save();
 * 
 * or;
 * 
 * storage::save($_FILES['file']);
 * 
 * or;
 * 
 * storage::save($_FILES);
 * 
 * 
 * or;
 * 
 * storage::db_table("product_files")->save()->to($this->file_path . "/{{ userid }}/{{ tag }}-");
 * 
 * 
 * $this->storage = new storage("product_files", "product/{{ ref_id }}");
 * $this->storage->save((is_value($check) && is_numeric($check) ? $check : null))->to($this->file_path . "/{{ userid }}/{{ tag }}-");
 * 
 * 
 * 
 * To download file :
 * 
 * storage::db_table("product_files")->get($image_tag);
 * 
 * or;
 * 
 * storage::db_table("product_files")->get($file_id);
 *
 * or;
 * 
 * $this->storage->get($image_tag);
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
class storage
{
    private $store  = null;
    static $s_store = null;

    public function __construct($db_table = "", $storage_path = "")
    {
        $this->store = new store_files($db_table, $storage_path);
    }

    public function __call($name, $arguments)
    {
        if (is_null($this->store)) {
            $this->store = new store_files;
        }

        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        if (stripos($name, "set_") !== false) {
            $arguments = [str_replace("set_", "", $name), $arguments[0]];
            $name      = "set";
        } else {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        }

        return call_user_func_array(array($this->store, $name), $arguments);
        // return forward_static_call_array(array(store_files::class, $name), $arguments);
        // return forward_static_call_array( $name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        if (is_null(self::$s_store)) {
            self::$s_store = new store_files;
        }

        if (stripos($name, "set_") !== false) {
            $arguments = [str_replace("set_", "", $name), $arguments[0]];
            $name      = "set";
        } else {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        }

        return call_user_func_array(array(self::$s_store, $name), $arguments);
        // return forward_static_call_array(array(store_files::class, $name), $arguments);
        // return forward_static_call_array( $name, $arguments);
    }

    public function __set($name, $val)
    {
        return call_user_func_array(array($this->store, $name), [$val]);
    }
}
