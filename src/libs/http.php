<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 06/05/2019 18:53:39
 * @last modified     : 22/03/2020 21:59:59
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

use \honwei189\config as config;

/**
 *
 * Sanitize $_POST, $_GET, $_FILES
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
class http
{
    public $action;
    public $error;
    public $header;
    public $is_jwt_auth         = false;
    public $is_jwt_auth_success = false;
    public $type                = "html";
    public $_files              = [];
    public $_get                = [];
    public $_json               = [];
    public $_jwt                = [];
    public $_jwt_auth           = null;
    public $_post               = [];
    public $_request            = [];
    public $_raws               = null;

    /**
     * @access private
     * @internal
     */
    public function __construct()
    {
        $this->action = "get";
        $this->_get   = $_GET;
        $this->_get   = preg_replace("'<script[^>]*?" . "" . ">.*?</script>'si", "", $this->_get);
        $this->_get   = filter_var_array($this->_get, FILTER_SANITIZE_STRING);

        $this->_post = $_POST;
        $this->_post = filter_var(
            $this->_post,
            FILTER_CALLBACK,
            array("options" => array($this, "sanitize_strip_script"))
        );

        $this->_post = filter_var_array($this->_post, FILTER_SANITIZE_STRING);
        $this->_post = filter_var_array($this->_post, FILTER_SANITIZE_MAGIC_QUOTES);
        $this->_post = filter_var(
            $this->_post,
            FILTER_CALLBACK,
            array("options" => array($this, "sanitize_min_clean_array"))
        );

        if (count($this->_post) > 0) {
            $this->action = "post";
        }

        $this->_raws = file_get_contents("php://input");

        if (strlen($this->_raws) > 0 && $this->isValidJSON($this->_raws)) {
            $this->type  = "json";
            $this->_json = $this->sanitize_object(json_decode($this->_raws));

            if (!is_array($this->_json)) {
                $this->_json = [$this->_json];
            }

            $this->_post = array_merge($this->_post, $this->_json);
        } else {
            $this->action = trim(strtolower($_SERVER['REQUEST_METHOD']));
            if ($this->action == "put") {
                $chunk = 8192;

                if (!is_null($this->_raws) && is_array($this->_raws)) {
                    try {
                        // if (!($putData = fopen("php://input", "r"))) {
                        //     throw new \Exception("Can't get PUT data.");
                        // }

                        // now the params can be used like any other variable
                        // see below after input has finished

                        $tot_write   = 0;
                        $tmp_dir     = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                        $tmpFileName = tempnam($tmp_dir, 'tmp');
                        // Create a temp file
                        if (!is_file($tmpFileName)) {
                            fclose(fopen($tmpFileName, "x")); //create the file and close it
                            // Open the file for writing
                            if (!($fp = fopen($tmpFileName, "w"))) {
                                throw new \Exception("Can't write to tmp file");
                                die("Can't write to tmp file");
                                exit;

                            }

                            // Read the data a chunk at a time and write to the file
                            while ($data = fread($this->_raws, $chunk)) {
                                $chunk_read = strlen($data);
                                if (($block_write = fwrite($fp, $data)) != $chunk_read) {
                                    throw new \Exception("Can't write more to tmp file");
                                    die("Can't write more to tmp file");
                                    exit;
                                }

                                $tot_write += $block_write;
                            }

                            if (!fclose($fp)) {
                                throw new \Exception("Can't close tmp file");
                                die("Can't close tmp file");
                                exit;
                            }

                            // unset($this->_raws);
                        } else {
                            // Open the file for writing
                            if (!($fp = fopen($tmpFileName, "a"))) {
                                throw new \Exception("Can't write to tmp file");
                                die("Can't write to tmp file");
                                exit;
                            }

                            // Read the data a chunk at a time and write to the file
                            while ($data = fread($this->_raws, $chunk)) {
                                $chunk_read = strlen($data);
                                if (($block_write = fwrite($fp, $data)) != $chunk_read) {
                                    throw new \Exception("Can't write more to tmp file");
                                    die("Can't write more to tmp file");
                                    exit;
                                }

                                $tot_write += $block_write;
                            }

                            if (!fclose($fp)) {
                                throw new \Exception("Can't close tmp file");
                                die("Can't close tmp file");
                                exit;
                            }

                            // unset($this->_raws);
                        }

                        $file_size = filesize($tmpFileName);

                        // Check file length and MD5
                        // if ($tot_write != $_SERVER['CONTENT_LENGTH']) {
                        if ($tot_write != $file_size) {
                            throw new \Exception("Wrong file size");
                            die("Wrong file size");
                            exit;
                        }

                        // $md5_arr = explode(' ', exec("md5sum $tmpFileName"));
                        // $md5     = $md5_arr[0];
                        // if ($md5 != $md5sum) {
                        //     throw new \Exception("Wrong md5");
                        // }

                        $this->_files['file']['tmp_name'] = $tmpFileName;
                        $this->_files['file']['name']     = (is_value($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : uniqid(rand(), true) . $this->mime2ext(mime_content_type($tmpFileName)));
                        $this->_files['file']['size']     = $file_size;
                        $this->_files['file']['type']     = mime_content_type($tmpFileName);

                        unset($file_size);
                        unset($tmpFileName);
                        unset($tmp_dir);
                        unset($tot_write);
                    } catch (\Exception $e) {
                        echo '', $e->getMessage(), "\n";
                        exit;
                    }
                } else {
                    throw new \Exception("Can't get PUT data.");
                    die("Can't get PUT data.");
                    exit;
                }
            }

            if (isset($this->_post['header'])) {
                $this->action = $this->_post['header'];
                unset($this->_post['header']);
            } else if (isset($this->_post['_header'])) {
                $this->action = $this->_post['_header'];
                unset($this->_post['_header']);
            } else if (isset($this->_post['method'])) {
                $this->action = $this->_post['method'];
                unset($this->_post['method']);
            } else if (isset($this->_post['_method'])) {
                $this->action = $this->_post['_method'];
                unset($this->_post['_method']);
            }

            $this->action = trim(strtolower($this->action));

            if ($this->action != "post" && $this->action != "get") {
                if (is_value($_SERVER['REQUEST_URI'])) {
                    $_ = explode("&", substr($_SERVER['REQUEST_URI'], 1));

                    if (is_array($_) && count($_) > 0) {
                        foreach ($_ as $v) {
                            list($key, $val)   = explode("/", $v);
                            $this->_post[$key] = $val;
                        }
                    }
                }

                if (is_value($this->_raws)) {
                    parse_str($this->_raws, $get_array);
                    $this->_post = array_merge($this->_post, $get_array);
                    unset($get_array);
                }

                $this->_post = filter_var_array($this->_post, FILTER_SANITIZE_STRING);
                $this->_post = filter_var_array($this->_post, FILTER_SANITIZE_MAGIC_QUOTES);
                $this->_post = filter_var(
                    $this->_post,
                    FILTER_CALLBACK,
                    array("options" => array($this, "sanitize_min_clean_array"))
                );
            }
        }

        $this->_request = $_REQUEST;

        $this->_request = filter_var_array($this->_request, FILTER_SANITIZE_STRING);
        $this->_request = filter_var_array($this->_request, FILTER_SANITIZE_MAGIC_QUOTES);
        $this->_request = filter_var(
            $this->_request,
            FILTER_CALLBACK,
            array("options" => array($this, "sanitize_min_clean_array"))
        );

        if ($this->action != "put") {
            $this->_files = $_FILES;
        }

        if ($this->_files) {
            if (is_array($this->_files)) {
                foreach ($this->_files as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $_k => $_v) {
                            if ($_k == "name") {
                                if (is_array($_v)) {
                                    foreach ($_v as $__k => $__v) {
                                        $this->_files[$k][$_k][$__k] = substr($this->sanitize($__v), 0, 250);
                                    }
                                } else {
                                    $this->_files[$k][$_k] = $this->sanitize($_v);
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->header = $this->get_headers();

        if(isset($this->header['Authorization'])){
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $this->header['user'] = $_SERVER['PHP_AUTH_USER'];
            }

            if (isset($_SERVER['PHP_AUTH_PW'])) {
                $this->header['password'] = $_SERVER['PHP_AUTH_PW'];
            }
        }

        if ($this->type == "json" && isset($this->header['Content-Type']) && trim(strtolower($this->header['Content-Type'])) == "application/json") {
            $this->is_jwt_auth = (isset(config::get("flayer", "jwt")['enabled']) ? (bool) config::get("flayer", "jwt")['enabled'] : "false");

            if (is_array($this->_post) && count($this->_post)) {
                $this->action = "post";
                $this->_post  = [];
            }

            foreach ($this->header as $header => $value) {
                if (strtolower($header) == "authorization") {
                    // if (stripos($value, "Bearer") !== false) {

                    // }
                    $this->_jwt_auth = str_ireplace("Bearer ", "", $value);
                }
            }

            if ($this->is_jwt_auth && is_value($this->_jwt_auth)) {
                $this->_jwt = flayer::jwt()->verify_token($this->_jwt_auth);
                if (!$this->_jwt) {
                    // header('HTTP/1.0 401 Unauthorized');
                    // exit;
                    $this->error               = 401;
                    $this->is_jwt_auth_success = false;
                } else {
                    $this->is_jwt_auth_success = true;
                }
            } else if ($this->is_jwt_auth && !is_value($this->_jwt_auth)) {
                $this->error               = 401;
                $this->is_jwt_auth_success = false;
            }
        }
    }

    public function __get($arg)
    {
        if (isset($this->_post[$arg])) {
            return $this->_post[$arg];
        } else if (isset($this->_get[$arg])) {
            return $this->_get[$arg];
        } else if (isset($this->_files[$arg])) {
            return $this->_files[$arg];
        } else if (is_array($this->_json[$arg]) && isset($this->_json[$arg])) {
            return $this->_json[$arg];
        }
    }

    public function __isset($name)
    {
        if (isset($this->_post[$name])) {
            return true;

        } else if (isset($this->_get[$name])) {
            return true;
        } else if (isset($this->_files[$name])) {
            return true;
        } else if (isset($this->_json[$name])) {
            return true;
        }

        return false;
    }

    public function __set($name, $val)
    {}

    /**
     * Return sanitize's $_FILES
     *
     */
    public function files()
    {
        return $this->_files;
    }

    /**
     * Return sanitize's $_GET
     *
     */
    public function get()
    {
        return $this->_get;
    }

    /**
     * Get value from $this->_post or $this->_get
     *
     * @param string $name
     * @param string $type Value type.  e.g: crypt, number, date
     * @return string
     */
    public function inputs($name, $type = "")
    {
        $value = "";

        if (isset($this->_post[$name])) {
            $value = $this->_post[$name];
        } else if (isset($this->_get[$name])) {
            $value = $this->_get[$name];
        }

        return value_format($value, $type);
    }

    /**
     * Return sanitize's JSON
     *
     */
    public function json()
    {
        return $this->_json;
    }

    /**
     * Add post value without using $this->_post
     *
     * If $key and $value is null or empty, return sanitize's $_POST
     *
     * @param string $key
     * @param string $value
     */
    public function post($key = null, $value = null)
    {
        if (is_null($key) && is_null($value)) {
            return $this->_post;
        }

        $this->_post[$key] = $this->sanitize($value);
    }

    private function get_headers()
    {
        $this->header = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) != 'HTTP_') {
                continue;
            }
            $header                = trim(str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5))))));
            $this->header[$header] = $value;
        }
        return $this->header;
    }

    private function isValidJSON($requests)
    {
        json_decode($requests);
        return json_last_error() == JSON_ERROR_NONE;
    }

    private function mime2ext($mime)
    {
        /** Function from https: //gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c */
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return isset($mime_map[$mime]) === true ? "." . $mime_map[$mime] : "";
    }

    private function sanitize($string)
    {
        $string = str_replace(array('[\', \']', '/', '\\'), '', $string);
        $string = preg_replace('/\[.*\]/U', '', $string);
        $string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $string);
        $string = htmlentities($string, ENT_COMPAT, 'utf-8');
        $string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string);
        $string = preg_replace('/[^a-zA-Z0-9_.\/]/', '-', $string);
        // return strtolower(trim($string, '-'));
        return $string;
    }

    private function sanitize_object($data)
    {
        foreach ($data as &$value) {
            if (is_scalar($value)) {
                $value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                continue;
            }

            $value = $this->sanitize_object($value);
        }

        return $data;
    }

    private function sanitize_strip_script($string)
    {
        return preg_replace("'<script[^>]*?" . "" . ">.*?</script>'si", "", $string);
    }

    /**
     * Sanitize Array value
     *
     * @param array $array Array to santize
     * @param bool $full Full clean or partial
     * @return array
     */
    private function sanitize_clean_array($array, $full = true)
    {
        if ($full) {
            $search = array(
                "'<script[^>]*?" . "" . ">.*?</script>'si", // Strip out javascript
                "'<[\/\!]*?[^<>]*?" . "" . ">'si", // Strip out html tags
                "'^\s+|\s+$'", //trim whitespace from beginning and end
                "'&(quot|#10);'i", // Replace br
                "'&(quot|#34);'i", // Replace html entities
                "'&(amp|#38);'i",
                "'&(lt|#60);'i",
                "'&(gt|#62);'i",
                "'&(nbsp|#160);'i",
                "'&(iexcl|#161);'i",
                "'&(cent|#162);'i",
                "'&(pound|#163);'i",
                "'&(copy|#169);'i",
                //"'&#(\d+);'e");                    // evaluate as php
                "'&#(\d+);'i",
            ); // evaluate as php

            $replace = array(
                "",
                "",
                "",
                PHP_EOL,
                "\"",
                "&",
                "<",
                ">",
                " ",
                chr(161),
                chr(162),
                chr(163),
                chr(169),
                "chr(\\1)",
            );
        } else {
            $search = array(
                "'<script[^>]*?" . "" . ">.*?</script>'si", // Strip out javascript
                // "'<[\/\!]*?[^<>]*?" . "" . ">'si", // Strip out html tags
                "'^\s+|\s+$'", //trim whitespace from beginning and end
                "'&(quot|#10);'i", // Replace br
                "'&(amp|#38);'i",
                // "'&(lt|#60);'i",
                // "'&(gt|#62);'i",
                "'&(copy|#169);'i",
            ); // evaluate as php

            $replace = array(
                "",
                // "",
                "",
                PHP_EOL,
                "&",
                // "<",
                // ">",
                chr(169),
            );
        }

        return preg_replace($search, $replace, $array);
    }

    /**
     * Sanitize Array value
     *
     * @param array $array Array to santize
     * @return array
     */
    private function sanitize_min_clean_array($array)
    {
        $search = array(
            "'^\s+|\s+$'", //trim whitespace from beginning and end
            "'&(quot|#10);'i", // Replace br
            "'&(quot|#60);br&(quot|#62);'i", // Replace br
            "'&(amp|#38);'i",
            "'&(copy|#169);'i",
        );

        $replace = array(
            "",
            "",
            PHP_EOL,
            PHP_EOL,
            "&",
            chr(169),
        );

        return preg_replace($search, $replace, $array);
    }
}
