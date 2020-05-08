<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 21/05/2019 10:06:39
 * @last modified     : 23/12/2019 21:54:10
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;

use honwei189\flayer as flayer;
use \honwei189\config as config;

/**
 *
 * JWT Authentication and generate JWT
 *
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 */
class jwt
{
    private $config;

    private $header = [
        'alg' => 'HS256', //Cryptographic Algorithm
        'typ' => 'JWT', //Type
    ];

    private $key = null;

    /**
     * @access private
     * @internal
     */
    public function __construct()
    {
        $this->config = config::get("flayer", "jwt");
        $this->key    = (isset($this->config['key']) && is_value($this->config['key']) ? $this->config['key'] : "");
    }

    /**
     * Get new JWT
     *
     * @param string $user_uid User table primary ID for the user
     * @param string $user_id User name
     * @return string
     */
    public function generate_token($user_uid = null, $user_id = null)
    {
        $tokenId    = flayer::crypto()->encrypt(openssl_random_pseudo_bytes($this->config['token_length']));
        $issuedAt   = time();
        $notBefore  = $issuedAt + (isset($this->config['issue_time_after']) && is_value($this->config['issue_time_after']) ? (int) $this->config['issue_time_after'] : 10); //Adding 10 seconds
        $expire     = $notBefore + (isset($this->config['expiry_time_after']) && is_value($this->config['expiry_time_after']) ? (int) $this->config['expiry_time_after'] : 60); // Adding 60 seconds
        $serverName = (isset($this->config['issuer']) && is_value($this->config['issuer']) ? $this->config['issuer'] : "");

        $alg_config = [
            'HS256' => (isset($this->config['algorithm'][$this->header['alg']]) && is_value($this->config['algorithm'][$this->header['alg']]) ? $this->config['algorithm'][$this->header['alg']] : "sha256"),
        ];

        /*
         * Create the token as an array
         */
        $payload = [
            'iat'  => $issuedAt, // Issued at: time when the token was generated
            'jti'  => $tokenId, // Json Token Id: an unique identifier for the token
            'iss'  => $serverName, // Issuer
            'nbf'  => $notBefore, // Not before
            'exp'  => $expire, // Expire
            'data' => [ // Data related to the signer user
                'api_key'  => (isset($this->config['api']) && is_value($this->config['api']) ? $this->config['api'] : ""),
                'user_uid' => $user_uid, // userid from the users table
                'user_id'  => $user_id, // User name
                'ip'       => get_ip(),
            ],
        ];

        $base64header  = $this->encode(json_encode($this->header, JSON_UNESCAPED_UNICODE));
        $base64payload = $this->encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $token         = $base64header . '.' . $base64payload . '.' . $this->signature($base64header . '.' . $base64payload, $this->key, $alg_config[$this->header['alg']]);
        // $token         = $base64header . '.' . $base64payload . '.' . hash_hmac($alg_config[$this->header['alg']], "$base64header.$base64payload", $this->key, true);
        // return $this->encode($token);
        return $token;
    }

    /**
     * Verity JWT valid or not valid
     *
     * @param string $token
     * @return bool|array
     */
    public function verify_token($token)
    {
        // $tokens = explode('.', flayer::crypto()->decrypt($token));
        $tokens = explode('.', $token);

        if (count($tokens) != 3) {
            return false;
        }

        list($base64header, $base64payload, $sign) = $tokens;

        // $base64decodeheader = json_decode(flayer::crypto()->decrypt($base64header), JSON_OBJECT_AS_ARRAY);
        $base64decodeheader = json_decode($this->decode($base64header), JSON_OBJECT_AS_ARRAY);

        if (empty($base64decodeheader['alg'])) {
            return false;
        }

        // $alg_config = [
        //     'HS256' => 'sha256',
        // ];

        if ($this->signature($base64header . '.' . $base64payload, $this->key, $base64decodeheader['alg']) !== $sign) {
            return false;
        }

        // if (hash_hmac($alg_config[$this->header['alg']], $base64header . '.' . $base64payload, $this->key, $base64decodeheader['alg']) !== $sign) {
        //     return false;
        // }

        // $payload = json_decode(flayer::crypto()->decrypt($base64payload), JSON_OBJECT_AS_ARRAY);
        $payload = json_decode($this->decode($base64payload), JSON_OBJECT_AS_ARRAY);
        $api     = (isset($this->config['api_key']) && is_value($this->config['api_key']) ? $this->config['api_key'] : "");

        // If api key is not same
        if (isset($payload['data']['api_key']) && ($payload['data']['api_key'] != $api)) {
            return false;
        }

        //If issue time is greater than current server time
        if (isset($payload['iat']) && $payload['iat'] > time()) {
            return false;
        }

        // If expiry time is smaller than server time
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        // Deny access if current time is greater than "before expiry time"
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }

        // If user's IP is not same
        if (isset($payload['data']['ip']) && ($payload['data']['ip'] != get_ip())) {
            return false;
        }

        return $payload;
    }

    /**
     * encode   https://jwt.io/
     * @param string $input text string to encode
     * @return string
     */
    private function encode(string $input)
    {
        if ($this->config['token_type'] == "flayer") {
            return flayer::crypto()->encrypt($input);
        } else {
            return str_replace('=', '', strtr($this->encode($input), '+/', '-_'));
        }
    }

    /**
     * encode  https://jwt.io/
     * @param string $input text string to decode
     * @return bool|string
     */
    private function decode(string $input)
    {
        if ($this->config['token_type'] == "flayer") {
            return flayer::crypto()->encrypt($input);
        } else {
            $remainder = strlen($input) % 4;
            if ($remainder) {
                $addlen = 4 - $remainder;
                $input .= str_repeat('=', $addlen);
            }
            return $this->decode(strtr($input, '-_', '+/'));
        }
    }

    /**
     * HMACSHA256   https://jwt.io/
     * @param string $input text string to encode
     * @param string $key
     * @param string $alg   algorithm
     * @return string
     */
    private function signature(string $input, string $key, string $alg = 'HS256')
    {
        $alg_config = array(
            'HS256' => 'sha256',
        );
        return $this->encode(hash_hmac($alg_config[$alg], $input, $key, true));
    }
}
