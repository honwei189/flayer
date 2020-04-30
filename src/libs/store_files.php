<?php
/*
 * @version           : "1.0.0"
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 15/04/2020 11:02:52
 * @last modified     : 30/04/2020 16:58:13
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

use \honwei189\flayer as flayer;

/**
 *
 * Store files to disk and save records to database
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
class store_files
{
    private $called                   = [];
    private $default_storage_path     = null;
    private $db                       = null;
    private $db_table                 = null;
    private $file                     = null;
    private $id                       = null;
    private $storage_path             = null;
    private $use_default_storage_path = false;
    private $temp_file                = null;
    private $temp_path                = null;
    private $userid                   = null;

    public function __call($name, $arguments)
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        if ($name == "set") {
            if (count($arguments) !== count($arguments, COUNT_RECURSIVE)) {
                foreach ($arguments as $v) {
                    foreach ($v as $k => $val) {
                        $this->db->{$k} = $val;
                        $this->{$k}     = $val;
                    }
                }
            } else {
                $this->{$arguments[0]}     = $arguments[1];
                $this->db->{$arguments[0]} = $arguments[1];
            }
        }

        $this->called[] = $name;
        return $this;
    }

    public function __construct($db_table = "", $storage_path = "", $use_default_storage_path = false)
    {
        parent::__construct();
        flayer::fdo()->set_table($this->table);
        $this->db = flayer::fdo();
        // $this->db->set_encrypt_data(true);
        $this->db->set_encrypt_id(true);

        $this->temp_path                = sys_get_temp_dir();
        $this->default_storage_path     = $_SERVER['DOCUMENT_ROOT'] . "/files/" . $this->_user . "/{{ tag }}";
        $this->use_default_storage_path = $use_default_storage_path;

        if (is_value($db_table)) {
            $this->db_table = $db_table;
        }

        if (is_value($storage_path)) {
            $this->storage_path = $storage_path;
        }

        $this->userid = $this->_user;
    }

    public function __destruct()
    {
        $this->called       = null;
        $this->db_table     = null;
        $this->file         = null;
        $this->storage_path = null;
        $this->tag          = null;
        $this->temp_file    = null;
        $this->temp_path    = null;
        $this->ref_id       = null;
    }

    /**
     * Declare save file records to which database table
     *
     * @param string $table_name Database table name.  e.g:  my_files
     * @return store_files
     */
    public function db_table($table_name)
    {
        $this->called[] = 'db_table';
        $this->db_table = $table_name;
        $this->set_table($table_name);
        return $this;
    }

    public function delete($file)
    {

    }

    public function dir($dir)
    {

    }

    /**
     * To get stored files.
     *
     * Usage:
     *
     * storage::get("FILE_TAG"); //by file tag.  Can get it from database table, column name = tag
     * storage::get(123); //by file id
     *
     * $store->get("FILE_TAG");
     * $store->get(122);
     *
     * @param string $file File ID or file tag
     * @return stream|json
     */
    public function get($file)
    {
        $this->called[] = 'get';

        if (is_value($this->db_table)) {
            $id = $this->crypt_decrypt($file);

            if (is_numeric($id)) {
                $this->where("id", $id);
            } else {
                if (is_numeric($file)) {
                    $this->where("id", (int) $file);
                } else {
                    // $this->where("(tag = '$file' or name = '$file')");
                    $this->where("tag = '$file'");
                }
            }

            unset($id);

            // $this->debug();
            $this->cols([
                "id",
                "name",
                "tag",
                "md5",
                "size",
                "type",
                "path",
            ]);
            // $check = $this->where("userid", $this->_user)->get_row();
            $get = $this->get_row();

            if (is_array($get) && count($get) > 0) {
                $this->open_file($get['path'] . "/" . $get['name']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(["File not found"]);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(["File not found"]);
        }
    }

    /**
     * Get file's mdsum
     *
     * @param string $file Full path file name.  e.g: /path/to/abc.jpg
     * @return string
     */
    public function md5($file)
    {
        return md5_file($file);
    }

    /**
     * Define save file to specified directory
     *
     * Can use template code
     *
     * e.g:
     *
     * /path/to/{{ userid }}/{{ tag }}    // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8/abc.jpg
     * /path/to/{{ userid }}/{{ tag }}-   // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8-abc.jpg
     * /path/to/{{ userid }}/{{ tag }}_   // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8_abc.jpg
     *
     * Usage :
     *
     * storage::path("/path/to/{{ userid }}/{{ tag }}")->save();
     *
     *
     * @param string $save_to_dir_path Full path name of directory.  e.g: /path/to/
     * @return store_files
     */
    public function path($save_to_dir_path)
    {
        $this->called[] = 'path';

        if (is_value($save_to_dir_path)) {
            $this->storage_path = $save_to_dir_path;
        }

        return $this;
    }

    public function put($file, $save_as = null)
    {
        $this->called[] = 'put';
        $save_as        = $this->translate_tpl_code($save_as);
        $path           = dirname($save_as);

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        if (is_value($this->db_table) && !$this->check_table_exists()) {
            // return $this->return(0, "DB table not exist");
            $this->create_table();
        }

        if (move_uploaded_file($file, $save_as)) {
            if (is_value($this->db_table)) {
                $this->db->name  = $this->file['name'];
                $this->db->size  = $this->file['size'];
                $this->db->type  = $this->file['type'];
                $this->db->md5   = $this->file['md5'];
                $this->db->tag   = $this->tag;
                $this->db->path  = $path;
                $this->db->crdt  = "now()";
                $this->db->crby  = $this->_user;
                $this->db->lupdt = "now()";
                $this->db->lupby = $this->_user;

                unset($this->file);

                $tag = $this->tag;

                if ((int) $this->id > 0) {
                    // $this->debug();
                    $this->by_id($this->id);
                    $get = parent::get("path, name");
                    if (is_array($get) && count($get) > 0) {
                        unlink($get['path'] . "/" . $get['name']);
                    }
                }

                // $this->debug();
                if (parent::save(((int) $this->id > 0 ? $this->id : null))) {
                    // $this->id = $this->_id;
                    return $this->return(1, ["file_id" => $this->_id, "tag" => $tag]);
                } else {
                    return $this->return(0);
                }

            }
        } else {
            return $this->return(0);
        }
    }

    /**
     * Get file size
     *
     * @param string $file Full path file name.  e.g: /path/to/file.txt
     * @return integer
     */
    private function size($file)
    {
        return filesize($file);
    }

    /**
     * Save file
     *
     * Usage :
     *
     * storage::save(); //Save $_FILES to specified location and add new record to database
     * storage::save($_FILES); //Save $_FILES to specified location and add new record to database
     * storage::save($_FILES['file']); //Save $_FILES['file'] to specified location and add new record to database
     * storage::save($_FILES['file'], 12); //Update $_FILES['file'] to specified location and add new record to database
     * storage::save("/path/to/file.txt"); //Save /path/to/file.txt to specified location and add new record to database
     * storage::save(12); //Update $_FILES to database table, id = 12.  And replace old file from directory to latest one
     *
     * $store = new storage;
     * $store->path("/path/to/");
     * $store->db_table("files");
     * $store->save();
     * $store->save($_FILES);
     * $store->save($_FILES['file']);
     * $store->save($_FILES['file'], 12);
     * $store->save("/path/to/file.txt");
     * $store->save(12);
     *
     * or;
     *
     * $store->db_table("files")->path("/path/to/")->save();
     *
     * @param string|integer|array|null $file Can be full path file name or $_FILES or $_FILES['ANY_NAME'] or null.  If $file = null, auto get from $_FILES.  If $file = number (file id), auto get from $_FILES and update file to DB record instead of add new record
     * @param integer $id File ID
     * @return array
     */
    public function save($file = null, $id = null)
    {
        $this->called[] = 'save';

        if (is_numeric($file) && (int) $file > 0) {
            $id   = $file;
            $file = null;
        }

        if (is_file($file)) {
            $file = [
                'name'     => basename($file),
                'size'     => filesize($file),
                'tmp_name' => $file,
                'type'     => mime_content_type($file),
                'error'    => 0,
                'md5'      => $this->md5($file),
            ];
        } elseif (is_null($file)) {
            if (is_array($_FILES) && count($_FILES) > 0) {
                $file = null;
                foreach ($_FILES as $k => $v) {
                    if (isset($_FILES[$k]['error'])) {

                        if (is_array($_FILES[$k]['error']) && count($_FILES[$k]['error']) > 0) {
                            foreach ($_FILES[$k]['error'] as $_k => $_v) {
                                if ($_v != 4) {
                                    extract($_FILES[$k]);
                                    $file['name'][]     = $name[$_k];
                                    $file['size'][]     = $size[$_k];
                                    $file['tmp_name'][] = $tmp_name[$_k];
                                    $file['type'][]     = $type[$_k];
                                    $file['error'][]    = $error[$_k];
                                }
                            }

                            unset($name);
                            unset($size);
                            unset($tmp_name);
                            unset($type);
                            unset($error);
                        } else {
                            if ($v['error'] != 4) {
                                $file['name']     = $v['name'];
                                $file['size']     = $v['size'];
                                $file['tmp_name'] = $v['tmp_name'];
                                $file['type']     = $v['type'];
                                $file['error']    = $v['error'];
                            }
                        }
                    }

                }
            }
        }

        if (is_value($id)) {
            $this->id = $id;
        }

        if (is_null($file)) {
            return $this->return(1);
        } elseif (count($file) === count($file, COUNT_RECURSIVE)) {
            //is single file
            if (isset($file['error']) && $file['error'] == 4) {
                //if file not uploaded, skip upload process
                return $this->return(1);
            }
        }

        if (!is_value($this->storage_path)) {
            $this->temp_file = $file;
        }

        return $this->save_process($file);
    }

    public function save_from_tmp($temp_key)
    {

    }

    public function save_to_tmp($file, $temp_key = null)
    {

    }

    /**
     * Define save file to specified directory
     *
     * Can use template code
     *
     * e.g:
     *
     * /path/to/{{ userid }}/{{ tag }}    // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8/abc.jpg
     * /path/to/{{ userid }}/{{ tag }}-   // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8-abc.jpg
     * /path/to/{{ userid }}/{{ tag }}_   // will save to /path/to/my_userid/a3588df3f950ebc5d0ed9c82e9e81ab8_abc.jpg
     *
     * Usage:
     *
     * storage::to("/path/to/{{ userid }}/{{ tag }}")->save();
     * storage::save()->to("/path/to/{{ userid }}/{{ tag }}");
     *
     * @param string $save_to_dir_path Full path name of directory.  e.g: /path/to/
     * @return store_files
     */
    public function to($save_to_dir_path)
    {
        $this->called[]     = 'to';
        $this->storage_path = $save_to_dir_path;

        if (!is_null($this->temp_file)) {
            return $this->save_process($this->temp_file);
        } else {
            if (!in_array("save", $this->called)) {
                return $this;
            } else {
                return $this->return(1);
            }
        }
    }

    /**
     * Declare to use default storage path
     *
     * @param bool $bool Default is true
     * @return store_files
     */
    public function use_default($bool = true)
    {
        $this->called[]                 = 'use_default';
        $this->use_default_storage_path = $bool;
        return $this;
    }

    /**
     * Create data table to store files records
     *
     * @return bool
     */
    private function create_table()
    {
        $sql = "CREATE TABLE `" . $this->db_table . "` (
            `id` INT(18) NOT NULL AUTO_INCREMENT,
            `ref_id` INT(18) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `size` INT(18),
            `type` VARCHAR(30),
            `md5` VARCHAR(50),
            `tag` VARCHAR(100),
            `path` VARCHAR(300),
            `status` VARCHAR(2) NOT NULL DEFAULT 'A',
            `crdt` DATETIME NOT NULL,
            `crby` VARCHAR(150) NOT NULL,
            `lupdate` DATETIME,
            `lupby` VARCHAR(150),
            PRIMARY KEY (`id`),
            INDEX `" . $this->db_table . "_idx` (`id`, `ref_id`),
            INDEX `" . $this->db_table . "_tag` (`tag`)
        ) ENGINE = InnoDB;";

        $this->db->execute($sql);

        return !$this->db->is_error;
    }

    /**
     * Check is data table exist
     *
     * @return bool
     */
    private function check_table_exists()
    {
        // $db = $this->_db->dbconfig[1];
        $check = $this->db->read_one_sql("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $this->db_table . "'");

        // $check = $this->sql_find("SHOW TABLES LIKE '".$this->db_table."'");

        if (is_array($check) && count($check) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Open file and output to browser
     *
     * @param string $file Full path file name.  e.g: /abc/ddd/abc123.jpg
     */
    private function open_file($file)
    {
        if (!is_file($file)) {
            header('Content-Type: application/json');
            echo json_encode(["File not found"]);
        }

        $size     = filesize($file);
        $fileinfo = pathinfo($file);

        //workaround for IE filename bug with multiple periods / multiple dots in filename
        //that adds square brackets to filename - eg. setup.abc.exe becomes setup[1].abc.exe
        $filename = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ?
        preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1) :
        $fileinfo['basename'];

        $file_extension = strtolower($fileinfo['extension']);

        //This will set the Content-Type to the appropriate setting for the file
        switch ($file_extension) {
            /*Case 'exe': $Ctype='application/octet-stream'; break;
            Case 'zip': $Ctype='application/zip'; break;
            Case 'mp3': $Ctype='audio/mpeg'; break;
            Case 'mpg': $Ctype='video/mpeg'; break;
            Case 'avi': $Ctype='video/x-msvideo'; break;*/
            case "asf":
                $Ctype = "video/x-ms-asf";
                break;
            case "avi":
                $Ctype = "video/x-msvideo";
                break;
            case "exe":
                $Ctype = "application/octet-stream";
                break;
            case "jpg":
                $Ctype = "image/jpeg";
                break;
            case "mov":
                $Ctype = "video/quicktime";
                break;
            case "mp3":
                $Ctype = "audio/mpeg";
                break;
            case "mpg":
                $Ctype = "video/mpeg";
                break;
            case "mp4":
                $Ctype = "video/mp4";
                break;
            case "mpeg":
                $Ctype = "video/mpeg";
                break;
            case "rar":
                $Ctype = "encoding/x-compress";
                break;
            case "txt":
                $Ctype = "text/plain";
                break;
            case "wav":
                $Ctype = "audio/wav";
                break;
            case "wma":
                $Ctype = "audio/x-ms-wma";
                break;
            case "wmv":
                $Ctype = "video/x-ms-wmv";
                break;
            case "zip":
                $Ctype = "application/x-zip-compressed";
                break;
            default:
                $Ctype = 'application/force-download';
        }

        header("Content-type: $Ctype");
        //header('Content-Disposition: inline; filename="' . $fileinfo['basename'] . '"'); //use inline for browser cache to improve loading speed
        header('Content-Disposition: inline; filename="output.' . $file_extension . '"'); //use inline for browser cache to improve loading speed
        header("Content-Length: " . $size);

        set_time_limit(0);
        ob_end_clean(); // prevent file size too large and exceed memory_limit
        readfile($file);
        exit;
    }

    /**
     * Perform action to process and save files into specified directory
     *
     * @param string|array $file Can be full path file name or $_FILES or $_FILES['ANY_NAME']
     * @return array
     */
    private function save_process($file = null)
    {
        if ($this->use_default_storage_path) {
            $this->storage_path = $this->default_storage_path;
        }

        if (!is_value($this->storage_path)) {
            return $this;
        }

        $this->file    = $file;
        $upload        = null;
        $path          = $this->storage_path;
        $new_file_name = null;

        if (substr($path, -1) != "-" && substr($path, -1) != "_") {
            $path = $path . "/";
        } else {
            $_             = explode("/", $path);
            $new_file_name = end($_);
            $path          = str_replace($new_file_name, "", $path);
            unset($_);
        }

        if (isset($file['tmp_name']) && is_array($file['tmp_name']) && count($file['tmp_name']) > 0) {
            $i = 0;
            foreach ($file['tmp_name'] as $temp_name) {
                if (!empty($temp_name) && is_uploaded_file($temp_name)) {
                    $this->file = [
                        'name'     => $file['name'][$i],
                        'size'     => $file['size'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'type'     => $file['type'][$i],
                        'error'    => $file['error'][$i],
                        'md5'      => $this->md5($file['tmp_name'][$i]),
                    ];

                    $this->tag = md5(openssl_encrypt(gmdate("D, d M Y H:i:s", filemtime(__FILE__)), "aes128", $this->_user . ";" . $this->file['md5']));
                    $error     = $file['error'][$i];
                    $error     = $this->upload_error($error);

                    if (isset($error) && is_value($error)) {
                        return $this->return(0, $error);
                    }

                    if (is_value($new_file_name)) {
                        $this->file['name'] = $this->translate_tpl_code($new_file_name . $file['name']);
                    }

                    $upload[] = $this->put($temp_name, $path . $this->file['name']);
                }

                ++$i;
            }
        } else {
            $error = $this->upload_error($file['error']);

            if (isset($error) && is_value($error)) {
                return $this->return(0, $error);
            }

            if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                $this->file['md5'] = $this->md5($file['tmp_name']);
                $this->tag         = md5(openssl_encrypt(gmdate("D, d M Y H:i:s", filemtime(__FILE__)), "aes128", $this->_user . ";" . $this->file['md5']));

                if (is_value($new_file_name)) {
                    $this->file['name'] = $this->translate_tpl_code($new_file_name . $file['name']);
                }

                return $this->put($file['tmp_name'], $path . $this->file['name']);
            }
        }

        return $upload;

    }

    /**
     * Convert template code to PHP value
     *
     * @param string $str Template code.  e.g:  {{ tag }}
     * @return string
     */
    private function translate_tpl_code($str)
    {
        preg_match_all("/\{\{(.*?)\}\}/si", $str, $reg);

        if (isset($reg[1]) && count($reg[1]) > 0) {
            foreach ($reg[1] as $k => $v) {
                $v = trim($v);

                if (isset($this->{$v})) {
                    $str = str_replace($reg[0][$k], $this->{$v}, $str);
                } else {
                    $str = str_replace($reg[0][$k], "", $str);
                }
            }
        }

        unset($reg);
        return $str;
    }

    /**
     * Get error description
     *
     * @param integer $error_code Upload error code.  Can get it from $_FILES['ANY_NAME']['error']
     * @return string
     */
    private function upload_error($error_code)
    {
        $error_message = "";

        // List at: http://php.net/manual/en/features.file-upload.errors.php
        if ($error_code != UPLOAD_ERR_OK) {
            switch ($error_code) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                    break;

                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'The uploaded file was only partially uploaded.';
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No file was uploaded.';
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = 'Missing a temporary folder.';
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = 'Failed to write file to disk.';
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $error_message = 'A PHP extension interrupted the upload.';
                    break;

                default:
                    $error_message = 'Unknown error';
                    break;
            }

        }

        return $error_message;
    }
}