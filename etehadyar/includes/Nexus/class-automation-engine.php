<?php
defined('ABSPATH') || exit;
/**
 * Automation Engine 6.3 — واقعی
 * Trigger → Condition → Action → Log
 */
class EAIW_Automation_Engine {
    // اجرای یک اتوماسیون
    public static function run($automation){
        $id = intval($automation['id']);
        $trigger = json_decode($automation['trigger_config'], true);
        $action = json_decode($automation['action_config'], true);
        $action_type = $automation['action_type'];
        $start = microtime(true);
        $result = null;
        $ok = false;
        $error = '';

        try {
            switch($action_type){
                case 'create_article_from_product':
                    $product_id = intval($action['product_id'] ?? $trigger['product_id'] ?? 0);
                    if(!$product_id){
                        // آخرین محصول
                        $p = get_posts(['post_type'=>'product','posts_per_page'=>1,'post_status'=>'publish']);
                        $product_id = $p[0]->ID ?? 0;
                    }
                    if($product_id){
                        $r = EAIW_Omnichannel_Factory::generate_full(['post_id'=>$product_id,'save_draft'=>1]);
                        if(is_wp_error($r)) throw new Exception($r->get_error_message());
                        $result = ['draft_id'=>$r['draft_id'],'title'=>$r['title']];
                        $ok=true;
                    } else $error='محصولی پیدا نشد';
                    break;

                case 'create_video_from_factory':
                    $last=get_transient('eaiw_factory_last_'.get_current_user_id());
                    if(!$last){
                        $last=get_transient('eaiw_factory_last_1');
                    }
                    if($last){
                        $res=EAIW_Video_Studio_Pro::build([
                            'title'=>$last['title'] ?? 'ویدیو',
                            'script'=>$last['video'] ?? [],
                            'images'=>array_map(fn($x)=>$x['url'],$last['images'] ?? []),
                            'audio_url'=>$last['podcast']['audio']['url'] ?? '',
                        ]);
                        if(is_wp_error($res)) throw new Exception($res->get_error_message());
                        $result=$res; $ok=true;
                    } else $error='خروجی کارخانه پیدا نشد';
                    break;

                case 'publish_telegram':
                    $last=get_transient('eaiw_factory_last_'.get_current_user_id()) ?: get_transient('eaiw_factory_last_1');
                    if($last){
                        $text = $last['title']."\n\n".wp_trim_words(strip_tags($last['article']['html']??''),40)."\n\n".$last['hashtags'];
                        $img=$last['images'][0]['url'] ?? '';
                        $r=EAIW_Telegram::send($text,$img);
                        if(is_wp_error($r)) throw new Exception($r->get_error_message());
                        $result=$r; $ok=true;
                    } else $error='محتوایی برای ارسال نیست';
                    break;

                case 'enhance_product':
                    $pid=intval($action['product_id'] ?? 0);
                    if($pid){
                        $r=EAIW_Woo_Autopilot::enhance_one($pid);
                        if(is_wp_error($r)) throw new Exception($r->get_error_message());
                        $result=$r; $ok=true;
                    }
                    break;

                case 'trend_hunter':
                    $r=EAIW_Agent_Trend_Hunter::class; // run via manager
                    $res=EAIW_Agent_Manager::run_now('trend_hunter');
                    $result=$res; $ok=true;
                    break;

                case 'rewrite_meta':
                    $res=EAIW_Agent_Manager::run_now('seo_watcher');
                    $result=$res; $ok=true;
                    break;

                default:
                    $error='اکشن ناشناخته: '.$action_type;
            }
        } catch(Exception $e){ $error=$e->getMessage(); }

        $elapsed=round(microtime(true)-$start,2);
        self::log($id, $ok, $result, $error, $elapsed);
        // update automation
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        $wpdb->update($t, [
            'run_count'=> intval($automation['run_count'])+1,
            'last_run'=> current_time('mysql'),
        ], ['id'=>$id]);

        return ['ok'=>$ok,'result'=>$result,'error'=>$error,'elapsed'=>$elapsed];
    }

    public static function log($automation_id,$ok,$result,$error,$elapsed){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automation_runs';
        if($wpdb->get_var("SHOW TABLES LIKE '$t'") != $t) return;
        $wpdb->insert($t,[
            'automation_id'=>$automation_id,
            'status'=>$ok?'success':'failed',
            'result'=> wp_json_encode($result, JSON_UNESCAPED_UNICODE),
            'error_text'=>$error,
            'elapsed'=>$elapsed,
            'created_at'=> current_time('mysql'),
        ]);
    }

    public static function recent_runs($limit=12){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automation_runs';
        if($wpdb->get_var("SHOW TABLES LIKE '$t'") != $t) return [];
        return $wpdb->get_results("SELECT r.*, a.title FROM $t r LEFT JOIN {$wpdb->prefix}eaiw_automations a ON a.id=r.automation_id ORDER BY r.id DESC LIMIT $limit", ARRAY_A);
    }

    // Hook: وقتی محصول جدید منتشر شد
    public static function on_product_publish($post_id){
        if(get_post_type($post_id)!=='product') return;
        if(get_post_status($post_id)!=='publish') return;
        // جلوگیری از لوپ
        if(get_post_meta($post_id,'_eaiw_auto_done',true)) return;
        $autos=self::due_for_trigger('new_product');
        foreach($autos as $a){
            // فقط آنهایی که trigger new_product
            $res=self::run(array_merge($a, ['trigger_config'=>wp_json_encode(['product_id'=>$post_id], JSON_UNESCAPED_UNICODE)]));
            update_post_meta($post_id,'_eaiw_auto_done',1);
            update_post_meta($post_id,'_eaiw_auto_result', wp_json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    }

    // Hook: پست جدید
    public static function on_post_publish($post_id){
        if(!in_array(get_post_type($post_id),['post','page'])) return;
        if(get_post_status($post_id)!=='publish') return;
        $autos=self::due_for_trigger('new_post');
        foreach($autos as $a){
            self::run($a);
        }
    }

    private static function due_for_trigger($type){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        if($wpdb->get_var("SHOW TABLES LIKE '$t'") != $t) return [];
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE is_active=1 AND trigger_type=%s",$type), ARRAY_A);
    }

    // Cron: اجرای زمان‌بندی شده‌ها
    public static function cron_tick(){
        $autos=EAIW_Nexus::all_automations();
        foreach($autos as $a){
            if(!$a['is_active']) continue;
            $trig=json_decode($a['trigger_config'],true);
            $type=$a['trigger_type'];
            $should=false;
            if($type==='schedule'){
                $interval=$trig['interval'] ?? 'daily';
                $last=strtotime($a['last_run'] ?? '2000-01-01');
                $now=time();
                if($interval==='hourly' && $now-$last>3600) $should=true;
                if($interval==='daily' && $now-$last>86400) $should=true;
                if($interval==='weekly' && $now-$last>604800) $should=true;
            }
            if($type==='gsc_ctr_drop'){
                // هر روز یک بار چک
                $last=strtotime($a['last_run'] ?? '2000-01-01');
                if(time()-$last>86400) $should=true;
            }
            if($should) self::run($a);
        }
    }
}
