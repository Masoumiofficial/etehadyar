<?php
defined('ABSPATH') || exit;
class EAIW_Deactivator {
    public static function deactivate(){
        wp_clear_scheduled_hook('eaiw_agents_cron');
        wp_clear_scheduled_hook('eaiw_guardian_cron');
        wp_clear_scheduled_hook('eaiw_automation_cron');
        flush_rewrite_rules();
    }
}
