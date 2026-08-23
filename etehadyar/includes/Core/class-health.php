<?php
defined('ABSPATH') || exit;
class EAIW_Health {
    public static function check(){
        $res = [];
        $res['php'] = version_compare(PHP_VERSION,'7.4','>=');
        $res['openssl'] = extension_loaded('openssl');
        $res['mbstring'] = extension_loaded('mbstring');
        $res['curl'] = function_exists('curl_init');
        $res['memory'] = self::mem_ok();
        $res['vectors'] = self::table_exists('eaiw_vectors');
        $res['agents'] = self::table_exists('eaiw_agents');
        $res['cron'] = (bool)wp_next_scheduled('eaiw_agents_cron');
        $res['https'] = is_ssl();
        return $res;
    }
    private static function mem_ok(){
        $m = ini_get('memory_limit');
        if ($m == -1) return true;
        $bytes = wp_convert_hr_to_bytes($m);
        return $bytes >= 256*1024*1024;
    }
    private static function table_exists($tn){
        global $wpdb;
        $t = $wpdb->prefix . $tn;
        return $wpdb->get_var("SHOW TABLES LIKE '$t'") === $t;
    }
}
