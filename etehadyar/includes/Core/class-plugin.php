<?php
defined('ABSPATH') || exit;

class EAIW_Plugin {
    private static $instance = null;
    public static function instance(){
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    private function __construct(){
        $this->init_hooks();
    }
    private function init_hooks(){
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('eaiw_agents_cron', [$this, 'run_agents_cron']);
        add_action('eaiw_guardian_cron', [$this, 'run_guardian_cron']);
        // AJAX
        add_action('wp_ajax_eaiw_supernatural_toggle', [$this, 'ajax_toggle_supernatural']);
        add_action('wp_ajax_eaiw_theme_save', [$this, 'ajax_theme_save']);
        add_action('wp_ajax_eaiw_brain_index', [$this, 'ajax_brain_index']);
        add_action('wp_ajax_eaiw_brain_search', [$this, 'ajax_brain_search']);
        add_action('wp_ajax_eaiw_agent_toggle', [$this, 'ajax_agent_toggle']);
        add_action('wp_ajax_eaiw_agent_run', [$this, 'ajax_agent_run']);
        add_action('wp_ajax_eaiw_vision_generate', [$this, 'ajax_vision_generate']);
        add_action('wp_ajax_eaiw_flux_generate', [$this, 'ajax_flux_generate']);
        add_action('wp_ajax_eaiw_architect_generate', [$this, 'ajax_architect_generate']);
        add_action('wp_ajax_eaiw_nexus_test', [$this, 'ajax_nexus_test']);
        add_action('wp_ajax_eaiw_portal_seen', [$this, 'ajax_portal_seen']);
        add_action('wp_ajax_eaiw_soul_save_name', [$this, 'ajax_soul_save_name']);
        // 6.1 Factory + Social + TTS
        add_action('wp_ajax_eaiw_factory_generate', [$this, 'ajax_factory_generate']);
        add_action('wp_ajax_eaiw_factory_publish_telegram', [$this, 'ajax_factory_publish_telegram']);
        add_action('wp_ajax_eaiw_factory_publish_instagram', [$this, 'ajax_factory_publish_instagram']);
        add_action('wp_ajax_eaiw_tts_generate', [$this, 'ajax_tts_generate']);
        add_action('wp_ajax_eaiw_social_test_telegram', [$this, 'ajax_social_test_telegram']);
        add_action('wp_ajax_eaiw_social_save', [$this, 'ajax_social_save']);
        // 6.2 Video Pro + Woo Autopilot
        add_action('wp_ajax_eaiw_video_build', [$this, 'ajax_video_build']);
        add_action('wp_ajax_eaiw_woo_enhance_one', [$this, 'ajax_woo_enhance_one']);
        add_action('wp_ajax_eaiw_woo_bulk_enhance', [$this, 'ajax_woo_bulk_enhance']);
        add_action('wp_ajax_eaiw_woo_create_product', [$this, 'ajax_woo_create_product']);
        add_action('wp_ajax_eaiw_woo_find_weak', [$this, 'ajax_woo_find_weak']);
        // 6.3 Automation
        add_action('wp_ajax_eaiw_nexus_toggle', [$this, 'ajax_nexus_toggle']);
        add_action('wp_ajax_eaiw_nexus_run', [$this, 'ajax_nexus_run']);
        add_action('wp_ajax_eaiw_nexus_create', [$this, 'ajax_nexus_create']);
        add_action('wp_ajax_eaiw_nexus_delete', [$this, 'ajax_nexus_delete']);
        add_action('eaiw_automation_cron', [$this, 'run_automation_cron']);
        add_action('transition_post_status', [$this, 'on_post_status'], 10, 3);
        add_action('woocommerce_new_order', [$this, 'on_woocommerce_order'], 10, 1);
        add_action('woocommerce_checkout_order_processed', [$this, 'on_woocommerce_order'], 10, 1);
        add_action('eaiw_tg_order_event', [$this, 'handle_tg_order'], 10, 1);
        // 6.4 Reports
        add_action('wp_ajax_eaiw_report_pdf', [$this, 'ajax_report_pdf']);
        add_action('wp_ajax_eaiw_report_excel', [$this, 'ajax_report_excel']);
        // 6.6 AI test
        add_action('wp_ajax_eaiw_ai_test', [$this, 'ajax_ai_test']);
        add_action('rest_api_init', [$this, 'register_rest']);
    }

    public function init(){
        if (is_admin()) {
            new EAIW_Admin_Menu();
        }
        add_filter('cron_schedules', function($s){ if(!isset($s['fifteen_minutes'])) $s['fifteen_minutes']=['interval'=>900,'display'=>'هر ۱۵ دقیقه']; return $s; });
        // ensure defaults + upgrade 6.0 -> 6.0.1
        if (!get_option('eaiw_chatsoul_name')) update_option('eaiw_chatsoul_name','پشتیبان هوشمند');
        if (!get_option('eaiw_theme')) update_option('eaiw_theme','dark');
        if (!get_option('eaiw_chatsoul_greeting')) update_option('eaiw_chatsoul_greeting','سلام! من پشتیبان هوشمند هستم — هر سوالی داری بپرس، کل سایت رو بلدم ✨');
        if (!get_option('eaiw_chatsoul_color')) update_option('eaiw_chatsoul_color','#6d28ff');
        $dbv = (int)get_option('eaiw_db_version', 600);
        if ($dbv < 673) {
            global $wpdb;
            $t=$wpdb->prefix.'eaiw_agents';
            if($wpdb->get_var("SHOW TABLES LIKE '$t'")==$t){
                $wpdb->update($t, ['title'=>'بهبود سئو'], ['agent_key'=>'seo_watcher']);
                $wpdb->update($t, ['title'=>'بروزرسان محتوا'], ['agent_key'=>'gardener']);
                $wpdb->update($t, ['title'=>'لینک‌ساز هوشمند'], ['agent_key'=>'link_weaver']);
                $wpdb->update($t, ['title'=>'ایده‌یاب'], ['agent_key'=>'trend_hunter']);
            }
            add_option('eaiw_telegram_token','');
            add_option('eaiw_telegram_chat','');
            add_option('eaiw_instagram_token','');
            add_option('eaiw_instagram_user','');
            add_option('eaiw_flux_key','');
            $charset=$wpdb->get_charset_collate();
            $table_runs=$wpdb->prefix.'eaiw_automation_runs';
            $sql6="CREATE TABLE $table_runs (
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
            require_once ABSPATH.'wp-admin/includes/upgrade.php';
            dbDelta($sql6);
            if(!wp_next_scheduled('eaiw_automation_cron')){
                wp_schedule_event(time()+900,'fifteen_minutes','eaiw_automation_cron');
            }
            delete_transient('eaiw_weak_cache_6');
            delete_transient('eaiw_weak_cache_50');
            update_option('eaiw_db_version', 673);
        }
        // 6.6 chat defaults
        if(!get_option('eaiw_chatsoul_size')) update_option('eaiw_chatsoul_size','medium');
        if(get_option('eaiw_chatsoul_avatar')===false) update_option('eaiw_chatsoul_avatar','');
        if(!get_option('eaiw_chatsoul_position')) update_option('eaiw_chatsoul_position','bottom-right');
        if(get_option('eaiw_chatsoul_offset_x')===false) update_option('eaiw_chatsoul_offset_x',22);
        if(get_option('eaiw_chatsoul_offset_y')===false) update_option('eaiw_chatsoul_offset_y',22);
        if(get_option('eaiw_chatsoul_mobile')===false) update_option('eaiw_chatsoul_mobile',1);
        if(get_option('eaiw_chat_faqs')===false) update_option('eaiw_chat_faqs',[
            ['q'=>'هزینه ارسال چقدره؟','a'=>'ارسال تهران 1 روزه، شهرستان 2-3 روزه — بالای 1 میلیون رایگان! 😎'],
            ['q'=>'چطور سفارش رو پیگیری کنم؟','a'=>'کد پیگیری برات پیامک میشه — تو پنل سفارشات هم می‌بینی.'],
        ]);
        // تلگرام پیشرفته
        if(get_option('eaiw_telegram_proxy')===false) update_option('eaiw_telegram_proxy','');
        if(get_option('eaiw_telegram_order_chat')===false) update_option('eaiw_telegram_order_chat','');
        if(get_option('eaiw_telegram_order_enabled')===false) update_option('eaiw_telegram_order_enabled',0);
    }

    public function admin_assets($hook){
        if (strpos($hook, 'eaiw') === false && strpos($hook, 'etehadwp') === false) return;
        wp_enqueue_style('eaiw-nebula', EAIW_URL . 'assets/css/nebula.css', [], EAIW_VERSION);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('eaiw-nebula', EAIW_URL . 'assets/js/nebula.js', ['jquery','wp-color-picker'], EAIW_VERSION, true);
        wp_enqueue_script('eaiw-agents', EAIW_URL . 'assets/js/agents.js', ['jquery'], EAIW_VERSION, true);
        $soul_name = get_option('eaiw_chatsoul_name','پشتیبان هوشمند');
        wp_localize_script('eaiw-nebula', 'EAIW', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eaiw_nonce'),
            'rest' => esc_url_raw(rest_url('eaiw/v1/')),
            'supernatural' => (int)get_option('eaiw_supernatural_enabled',1),
            'theme' => get_option('eaiw_theme','dark'),
            'portalSeen' => (int)get_option('eaiw_portal_seen_count',0),
            'soulName' => $soul_name,
            'i18n' => [
                'portalTitle' => 'ورود به اتاق فرمان...',
                'jarvisHi' => 'سلام! من دستیار هوشمند اتحاد هستم — امروز چی بسازیم؟',
            ]
        ]);
        wp_add_inline_script('eaiw-nebula', 'window.EAIW_SUPERNATURAL=1;');
    }

    public function frontend_assets(){
        if (!get_option('eaiw_chatsoul_enabled', 0)) return;
        wp_enqueue_style('eaiw-chatsoul', EAIW_URL . 'assets/css/nebula.css', [], EAIW_VERSION);
        wp_enqueue_script('eaiw-chatsoul', EAIW_URL . 'assets/js/chatsoul.js', [], EAIW_VERSION, true);
        $soul_name = get_option('eaiw_chatsoul_name','اتحادیار');
        $soul_greeting = get_option('eaiw_chatsoul_greeting','سلام! من '.$soul_name.' هستم — دستیار باهوش و بامزه‌ات 😎 هر سوالی داری بپرس، حتی جوک!');
        wp_localize_script('eaiw-chatsoul', 'EAIW_SOUL', [
            'rest' => esc_url_raw(rest_url('eaiw/v1/chat')),
            'nonce' => wp_create_nonce('wp_rest'),
            'name' => $soul_name,
            'greeting' => $soul_greeting,
            'color' => get_option('eaiw_chatsoul_color','#6d28ff'),
            'size' => get_option('eaiw_chatsoul_size','medium'),
            'avatar' => get_option('eaiw_chatsoul_avatar',''),
            'position' => get_option('eaiw_chatsoul_position','bottom-right'),
            'offset_x' => get_option('eaiw_chatsoul_offset_x',22),
            'offset_y' => get_option('eaiw_chatsoul_offset_y',22),
            'mobile' => get_option('eaiw_chatsoul_mobile',1),
            'faqs' => array_slice(EAIW_ChatSoul::faqs(),0,4),
        ]);
    }

    public function register_rest(){
        register_rest_route('eaiw/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [EAIW_ChatSoul::class, 'rest_chat'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('eaiw/v1', '/brain/search', [
            'methods' => 'GET',
            'callback' => function($req){
                $q = sanitize_text_field($req->get_param('q'));
                $res = EAIW_RAG::search($q, 6);
                return rest_ensure_response($res);
            },
            'permission_callback' => function(){ return current_user_can('edit_posts'); }
        ]);
    }

    public function ajax_toggle_supernatural(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی غیرمجاز');
        $val = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;
        update_option('eaiw_supernatural_enabled', $val);
        wp_send_json_success(['enabled'=>$val]);
    }
    public function ajax_theme_save(){
        check_ajax_referer('eaiw_nonce');
        $t = sanitize_text_field($_POST['theme'] ?? 'dark');
        if (!in_array($t, ['dark','light'])) $t='dark';
        update_option('eaiw_theme', $t);
        // also for user meta
        update_user_meta(get_current_user_id(), 'eaiw_theme', $t);
        wp_send_json_success(['theme'=>$t]);
    }
    public function ajax_portal_seen(){
        check_ajax_referer('eaiw_nonce');
        $c = (int)get_option('eaiw_portal_seen_count',0);
        update_option('eaiw_portal_seen_count', $c+1);
        wp_send_json_success(['count'=>$c+1]);
    }
    public function ajax_brain_index(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $res = EAIW_Site_Brain::index_batch(isset($_POST['offset'])?intval($_POST['offset']):0, 20);
        wp_send_json_success($res);
    }
    public function ajax_brain_search(){
        check_ajax_referer('eaiw_nonce');
        $q = sanitize_text_field($_POST['q'] ?? '');
        wp_send_json_success(EAIW_RAG::search($q, 8));
    }
    public function ajax_agent_toggle(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $key = sanitize_key($_POST['agent'] ?? '');
        $enabled = (int)($_POST['enabled'] ?? 0);
        $r = EAIW_Agent_Manager::set_enabled($key, $enabled);
        wp_send_json_success($r);
    }
    public function ajax_agent_run(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $key = sanitize_key($_POST['agent'] ?? '');
        $r = EAIW_Agent_Manager::run_now($key);
        wp_send_json_success($r);
    }
    public function ajax_vision_generate(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('دسترسی');
        $prompt = sanitize_text_field($_POST['prompt'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'photorealistic');
        $size = sanitize_text_field($_POST['size'] ?? '1280x720');
        $res = EAIW_Vision_Studio::generate($prompt, $style, $size);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_architect_generate(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_pages')) wp_send_json_error('دسترسی');
        $brief = sanitize_textarea_field($_POST['brief'] ?? '');
        $res = EAIW_Architect::generate($brief);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_nexus_test(){
        check_ajax_referer('eaiw_nonce');
        wp_send_json_success(EAIW_Nexus::test_all());
    }
    public function ajax_soul_save_name(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $name = sanitize_text_field($_POST['name'] ?? '');
        if (!$name) $name='پشتیبان هوشمند';
        update_option('eaiw_chatsoul_name', $name);
        if (isset($_POST['greeting'])) update_option('eaiw_chatsoul_greeting', sanitize_textarea_field($_POST['greeting']));
        if (isset($_POST['color'])) update_option('eaiw_chatsoul_color', sanitize_hex_color($_POST['color']));
        wp_send_json_success(['name'=>$name]);
    }
    // 6.1 — Factory real — 6.7.3 with provider
    public function ajax_factory_generate(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        $tone = sanitize_text_field($_POST['tone'] ?? 'حرفه‌ای و صمیمی');
        $length = intval($_POST['length'] ?? 1200);
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $save_draft = !empty($_POST['save_draft']);
        $auto_tts = !empty($_POST['auto_tts']);
        $res = EAIW_Omnichannel_Factory::generate_full([
            'prompt'=>$prompt,
            'post_id'=>$post_id,
            'tone'=>$tone,
            'length'=>$length,
            'provider'=>$provider,
            'save_draft'=>$save_draft,
            'auto_tts'=>$auto_tts,
        ]);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        // ذخیره موقت برای انتشار
        set_transient('eaiw_factory_last_'.get_current_user_id(), $res, HOUR_IN_SECONDS);
        wp_send_json_success($res);
    }
    public function ajax_flux_generate(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('دسترسی');
        $prompt=sanitize_text_field($_POST['prompt']??'');
        $style=sanitize_text_field($_POST['style']??'photorealistic');
        $size=sanitize_text_field($_POST['size']??'1024x1024');
        $res=EAIW_Flux_Client::generate($prompt,$style,$size);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_tts_generate(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $text=sanitize_textarea_field($_POST['text']??'');
        $voice=sanitize_text_field($_POST['voice']??'alloy');
        $res=EAIW_TTS::synthesize($text,$voice);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_factory_publish_telegram(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $text=sanitize_textarea_field($_POST['text']??'');
        $image=sanitize_text_field($_POST['image']??'');
        $res=EAIW_Telegram::send($text,$image);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_factory_publish_instagram(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $caption=sanitize_textarea_field($_POST['caption']??'');
        $images = isset($_POST['images']) ? array_map('sanitize_text_field', (array)$_POST['images']) : [];
        // اگر از factory last استفاده شد
        if(empty($images)){
            $last=get_transient('eaiw_factory_last_'.get_current_user_id());
            if(!empty($last['images'])) $images=array_map(fn($x)=>$x['url'], $last['images']);
        }
        $res=EAIW_Instagram::publish_carousel($images,$caption);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_social_test_telegram(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $res=EAIW_Telegram::test();
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_social_save(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        if(isset($_POST['telegram_token'])) update_option('eaiw_telegram_token', sanitize_text_field($_POST['telegram_token']));
        if(isset($_POST['telegram_chat'])) update_option('eaiw_telegram_chat', sanitize_text_field($_POST['telegram_chat']));
        if(isset($_POST['instagram_token'])) update_option('eaiw_instagram_token', sanitize_text_field($_POST['instagram_token']));
        if(isset($_POST['instagram_user'])) update_option('eaiw_instagram_user', sanitize_text_field($_POST['instagram_user']));
        if(isset($_POST['flux_key'])){
            $k=sanitize_text_field($_POST['flux_key']);
            if($k && $k!=='••••••••') EAIW_Vault::save_key('flux',$k);
        }
        wp_send_json_success(['ok'=>true]);
    }

    // 6.2 Video Pro
    public function ajax_video_build(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $title=sanitize_text_field($_POST['title'] ?? 'ویدیو اتحاد');
        $script=json_decode(stripslashes($_POST['script'] ?? '[]'), true);
        $images=isset($_POST['images']) ? array_map('sanitize_text_field', (array)$_POST['images']) : [];
        $audio=sanitize_text_field($_POST['audio'] ?? '');
        if(empty($script) && !empty($_POST['factory_transient'])){
            $last=get_transient('eaiw_factory_last_'.get_current_user_id());
            if($last){
                $title=$last['title'] ?? $title;
                $script=$last['video'] ?? $script;
                $images=array_map(fn($x)=>$x['url'], $last['images'] ?? []);
                $audio=$last['podcast']['audio']['url'] ?? $audio;
                if(empty($audio) && !empty($last['podcast']['text'])){
                    // try TTS quick
                    $tts=EAIW_TTS::synthesize(mb_substr($last['podcast']['text'],0,3500));
                    if(!is_wp_error($tts)) $audio=$tts['url'];
                }
            }
        }
        $res=EAIW_Video_Studio_Pro::build(['title'=>$title,'script'=>$script,'images'=>$images,'audio_url'=>$audio,'duration'=>60]);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_woo_enhance_one(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_products') && !current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $id=intval($_POST['product_id'] ?? 0);
        $res=EAIW_Woo_Autopilot::enhance_one($id);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_woo_bulk_enhance(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_products') && !current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $ids=isset($_POST['ids']) ? array_map('intval',(array)$_POST['ids']) : [];
        if(empty($ids)) wp_send_json_error('محصولی انتخاب نشده');
        $res=EAIW_Woo_Autopilot::bulk_enhance($ids);
        wp_send_json_success($res);
    }
    public function ajax_woo_create_product(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('edit_products') && !current_user_can('edit_posts')) wp_send_json_error('دسترسی');
        $prompt=sanitize_textarea_field($_POST['prompt'] ?? '');
        $make_image=!empty($_POST['make_image']);
        $res=EAIW_Woo_Autopilot::create_product($prompt,['make_image'=>$make_image]);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success($res);
    }
    public function ajax_woo_find_weak(){
        check_ajax_referer('eaiw_nonce');
        $res=EAIW_Woo_Autopilot::find_weak(8);
        wp_send_json_success($res);
    }

    public function ajax_nexus_toggle(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $id=intval($_POST['id']??0); $active=intval($_POST['active']??0);
        EAIW_Nexus::toggle($id,$active);
        wp_send_json_success(['id'=>$id,'active'=>$active]);
    }
    public function ajax_nexus_run(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $id=intval($_POST['id']??0);
        $auto=EAIW_Nexus::get($id);
        if(!$auto) wp_send_json_error('یافت نشد');
        $res=EAIW_Automation_Engine::run($auto);
        wp_send_json_success($res);
    }
    public function ajax_nexus_create(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $title=sanitize_text_field($_POST['title']??'');
        $trigger=json_decode(stripslashes($_POST['trigger']??'{}'), true);
        $action=json_decode(stripslashes($_POST['action']??'{}'), true);
        if(!$title) wp_send_json_error('عنوان لازم است');
        $id=EAIW_Nexus::create($title,$trigger,$action);
        wp_send_json_success(['id'=>$id]);
    }
    public function ajax_nexus_delete(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $id=intval($_POST['id']??0);
        EAIW_Nexus::delete($id);
        wp_send_json_success(['deleted'=>$id]);
    }
    public function ajax_report_pdf(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $url=EAIW_Reports::pdf_url();
        wp_send_json_success(['url'=>$url, 'html'=>str_replace('.pdf','.html',$url)]);
    }
    public function ajax_report_excel(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $type=sanitize_text_field($_POST['type']??'weak');
        $url=EAIW_Reports::excel_url($type);
        wp_send_json_success(['url'=>$url]);
    }
    public function ajax_ai_test(){
        check_ajax_referer('eaiw_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی');
        $provider=sanitize_text_field($_POST['provider']??'');
        $key=EAIW_Vault::get_key($provider);
        if(!$key) wp_send_json_error('کلید وارد نشده');
        // تست سبک: یک درخواست کوتاه
        $res=EAIW_AI_Client::complete('سلام', 'تو دستیار فارسی هستی', ['provider'=>$provider,'max_tokens'=>20,'temperature'=>0.3]);
        if(is_wp_error($res)) wp_send_json_error($res->get_error_message());
        wp_send_json_success(['ok'=>true,'preview'=>mb_substr($res,0,80)]);
    }
    public function on_woocommerce_order($order_id){
        if(class_exists('EAIW_Telegram') && get_option('eaiw_telegram_order_enabled')){
            wp_schedule_single_event(time()+10, 'eaiw_tg_order_event', [$order_id]);
        }
    }
    public function handle_tg_order($order_id){
        if(class_exists('EAIW_Telegram')) EAIW_Telegram::send_order($order_id);
    }
    public function run_automation_cron(){
        if(class_exists('EAIW_Automation_Engine')) EAIW_Automation_Engine::cron_tick();
    }
    public function on_post_status($new,$old,$post){
        if($new!=='publish' || $old==='publish') return;
        if($post->post_type==='product'){
            if(class_exists('EAIW_Automation_Engine')) EAIW_Automation_Engine::on_product_publish($post->ID);
        } elseif(in_array($post->post_type,['post','page'])){
            if(class_exists('EAIW_Automation_Engine')) EAIW_Automation_Engine::on_post_publish($post->ID);
        }
    }

    public function run_agents_cron(){
        EAIW_Agent_Manager::run_due_agents();
    }
    public function run_guardian_cron(){
        if (class_exists('EAIW_Guardian')) EAIW_Guardian::scan();
    }
}
