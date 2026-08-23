<?php
defined('ABSPATH') || exit;
class EAIW_Logger {
    public static function log($msg, $ctx=[]){
        $trace = wp_generate_uuid4();
        $line = sprintf("[EAIW:%s] %s %s\n", $trace, $msg, $ctx?wp_json_encode($ctx, JSON_UNESCAPED_UNICODE):'');
        error_log($line);
        // also DB jobs table if available
        return $trace;
    }
}
