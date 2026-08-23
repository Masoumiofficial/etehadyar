<?php
defined('ABSPATH') || exit;

class EAIW_Activator {
    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Vectors — Site Brain (جدید 6.0)
        $table_vectors = $wpdb->prefix . 'eaiw_vectors';
        $sql1 = "CREATE TABLE $table_vectors (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(20) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            chunk_index int NOT NULL DEFAULT 0,
            content text NOT NULL,
            content_hash varchar(64) NOT NULL,
            embedding longtext NULL,
            tokens int unsigned DEFAULT 0,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_type (object_type, object_id),
            KEY content_hash (content_hash)
        ) $charset;";

        // 2. Agents — ارتش نامرئی
        $table_agents = $wpdb->prefix . 'eaiw_agents';
        $sql2 = "CREATE TABLE $table_agents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            agent_key varchar(32) NOT NULL,
            title varchar(120) NOT NULL,
            status enum('idle','running','paused','error') NOT NULL DEFAULT 'idle',
            is_enabled tinyint(1) NOT NULL DEFAULT 0,
            last_run datetime NULL,
            next_run datetime NULL,
            run_count int unsigned DEFAULT 0,
            config longtext NULL,
            last_result longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY agent_key (agent_key)
        ) $charset;";

        // 3. Jobs — صف ماورایی (مکمل Action Scheduler)
        $table_jobs = $wpdb->prefix . 'eaiw_jobs';
        $sql3 = "CREATE TABLE $table_jobs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_type varchar(40) NOT NULL,
            payload longtext NOT NULL,
            status enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            attempts tinyint unsigned DEFAULT 0,
            trace_id varchar(36) NOT NULL,
            provider varchar(20) NULL,
            model varchar(60) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            started_at datetime NULL,
            finished_at datetime NULL,
            error_text text NULL,
            PRIMARY KEY (id),
            KEY status (status, job_type),
            KEY trace_id (trace_id)
        ) $charset;";

        // 4. Automations — Nexus
        $table_automations = $wpdb->prefix . 'eaiw_automations';
        $sql4 = "CREATE TABLE $table_automations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(160) NOT NULL,
            trigger_type varchar(40) NOT NULL,
            trigger_config longtext NOT NULL,
            action_type varchar(40) NOT NULL,
            action_config longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            run_count int unsigned DEFAULT 0,
            last_run datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_type (trigger_type)
        ) $charset;";

        // 5. ChatSoul logs
        $table_chats = $wpdb->prefix . 'eaiw_chatsoul_logs';
        $sql5 = "CREATE TABLE $table_chats (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(48) NOT NULL,
            role enum('user','assistant') NOT NULL,
            message text NOT NULL,
            sources longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset;";

        // 6. Automation runs — لاگ اجرای اتوماسیون (6.3)
        $table_runs = $wpdb->prefix . 'eaiw_automation_runs';
        $sql6 = "CREATE TABLE $table_runs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            automation_id bigint(20) unsigned NOT NULL,
            status enum('success','failed') NOT NULL,
            result longtext NULL,
            error_text text NULL,
            elapsed float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY automation_id (automation_id),
            KEY created_at (created_at)
        ) $charset;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        dbDelta($sql5);
        dbDelta($sql6);

        // Seed Agents — نام‌های ساده و قابل فهم (6.0.1)
        $agents = [
            ['seo_watcher', 'بهبود سئو', 'رتبه و کلیک‌ها رو چک می‌کند و عنوان‌ها را بهتر می‌کند'],
            ['gardener', 'بروزرسان محتوا', 'مقاله‌های قدیمی یا کوتاه رو پیدا و تازه می‌کند'],
            ['link_weaver', 'لینک‌ساز هوشمند', 'بین مقاله‌ها لینک مرتبط و دقیق می‌سازد'],
            ['trend_hunter', 'ایده‌یاب', 'موضوعات داغ و پرجستجو رو پیدا و پیشنهاد می‌دهد'],
        ];
        foreach ($agents as $a) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE agent_key=%s", $a[0]));
            if (!$exists) {
                $wpdb->insert($table_agents, [
                    'agent_key' => $a[0],
                    'title' => $a[1],
                    'status' => 'idle',
                    'is_enabled' => 0,
                    'config' => wp_json_encode(['description' => $a[2]], JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        // Default options — حفظ سازگاری 5.23 + 6.0.1
        add_option('eaiw_db_version', EAIW_DB_VERSION);
        add_option('eaiw_supernatural_enabled', 1);
        add_option('eaiw_theme', 'dark');
        add_option('eaiw_portal_seen_count', 0);
        add_option('eaiw_site_brain_last_index', 0);
        add_option('eaiw_chatsoul_name', 'پشتیبان هوشمند');
        add_option('eaiw_chatsoul_greeting', 'سلام! من پشتیبان هوشمند هستم — هر سوالی داری بپرس، کل سایت رو بلدم ✨');
        add_option('eaiw_chatsoul_color', '#6d28ff');
        add_option('eaiw_chatsoul_enabled', 0);
        // بروزرسانی نام دستیاران قدیمی به نام ساده (اگر از 6.0 آمده)
        $map = ['seo_watcher'=>'بهبود سئو','gardener'=>'بروزرسان محتوا','link_weaver'=>'لینک‌ساز هوشمند','trend_hunter'=>'ایده‌یاب'];
        foreach($map as $k=>$newTitle){
            $wpdb->update($table_agents, ['title'=>$newTitle], ['agent_key'=>$k]);
        }

        // Cron — هر 15 دقیقه + اتوماسیون
        if (!wp_next_scheduled('eaiw_agents_cron')) {
            wp_schedule_event(time() + 900, 'fifteen_minutes', 'eaiw_agents_cron');
        }
        if (!wp_next_scheduled('eaiw_guardian_cron')) {
            wp_schedule_event(time() + 3600, 'hourly', 'eaiw_guardian_cron');
        }
        if (!wp_next_scheduled('eaiw_automation_cron')) {
            wp_schedule_event(time() + 900, 'fifteen_minutes', 'eaiw_automation_cron');
        }

        // Add 15-min schedule if missing
        add_filter('cron_schedules', function($schedules){
            if (!isset($schedules['fifteen_minutes'])) {
                $schedules['fifteen_minutes'] = ['interval'=>900, 'display'=>'هر ۱۵ دقیقه'];
            }
            return $schedules;
        });

        // Flush rewrite
        flush_rewrite_rules();
    }
}
