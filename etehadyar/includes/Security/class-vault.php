<?php
defined('ABSPATH') || exit;
class EAIW_Vault {
    private function key(){
        // ترکیب AUTH_KEY + SECURE_AUTH_SALT — مثل 5.1.0
        $salt = defined('AUTH_KEY') ? AUTH_KEY : 'etehadwp';
        $salt2 = defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : 'supernatural';
        return hash('sha256', $salt.$salt2, true);
    }
    public function encrypt($plain){
        if ($plain === '' || $plain === null) return '';
        $iv = openssl_random_pseudo_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv.$tag.$cipher);
    }
    public function decrypt($enc){
        if (!$enc) return '';
        $raw = base64_decode($enc, true);
        if (!$raw || strlen($raw) < 28) return '';
        $iv = substr($raw,0,12);
        $tag = substr($raw,12,16);
        $cipher = substr($raw,28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }
    public function has($option_key){
        $v = get_option($option_key, '');
        return !empty($v);
    }
    // Helper — ذخیره امن provider key
    public static function save_key($provider, $key){
        $v = new self();
        update_option("eaiw_key_{$provider}", $v->encrypt($key), false);
    }
    public static function get_key($provider){
        $v = new self();
        $enc = get_option("eaiw_key_{$provider}", '');
        return $v->decrypt($enc);
    }
}
