<?php
defined('ABSPATH') || exit;
class EAIW_Agent_Manager {
    private static $registry = [
        'seo_watcher' => ['class'=>'EAIW_Agent_SEO_Watcher','interval'=>900],
        'gardener'    => ['class'=>'EAIW_Agent_Gardener','interval'=>3600],
        'link_weaver' => ['class'=>'EAIW_Agent_Link_Weaver','interval'=>1800],
        'trend_hunter'=> ['class'=>'EAIW_Agent_Trend_Hunter','interval'=>7200],
    ];
    public static function all(){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_agents';
        $rows=$wpdb->get_results("SELECT * FROM $t ORDER BY id", ARRAY_A);
        // enrich
        foreach($rows as &$r){
            $r['config'] = json_decode($r['config'], true);
            $r['last_result'] = json_decode($r['last_result'], true);
        }
        return $rows;
    }
    public static function set_enabled($key,$enabled){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_agents';
        $wpdb->update($t, ['is_enabled'=>(int)$enabled], ['agent_key'=>$key]);
        if ($enabled) self::schedule_next($key);
        return ['key'=>$key,'enabled'=>(int)$enabled];
    }
    private static function schedule_next($key){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_agents';
        $interval = self::$registry[$key]['interval'] ?? 900;
        $next = gmdate('Y-m-d H:i:s', time()+$interval);
        $wpdb->update($t, ['next_run'=>$next], ['agent_key'=>$key]);
    }
    public static function run_now($key){
        $class = self::$registry[$key]['class'] ?? null;
        if (!$class || !class_exists($class)) return new WP_Error('not_found','Agent یافت نشد');
        $inst = new $class();
        $trace = EAIW_Logger::log("Agent run: $key");
        $start=microtime(true);
        $result = $inst->run();
        $elapsed = round(microtime(true)-$start,2);
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_agents';
        $wpdb->update($t, [
            'last_run'=>current_time('mysql'),
            'run_count'=> (int)$wpdb->get_var($wpdb->prepare("SELECT run_count FROM $t WHERE agent_key=%s",$key)) +1,
            'status'=> isset($result['error'])?'error':'idle',
            'last_result'=> wp_json_encode(array_merge($result,['elapsed'=>$elapsed,'trace'=>$trace]), JSON_UNESCAPED_UNICODE),
        ], ['agent_key'=>$key]);
        self::schedule_next($key);
        return $result;
    }
    public static function run_due_agents(){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_agents';
        $now=current_time('mysql');
        $due=$wpdb->get_results($wpdb->prepare("SELECT agent_key FROM $t WHERE is_enabled=1 AND (next_run IS NULL OR next_run <= %s)",$now), ARRAY_A);
        foreach($due as $r) self::run_now($r['agent_key']);
    }
}
