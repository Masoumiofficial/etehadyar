<?php
defined('ABSPATH') || exit;
class EAIW_Nexus {
    public static function all_automations(){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$t'") != $t) return [];
        return $wpdb->get_results("SELECT * FROM $t ORDER BY id DESC", ARRAY_A);
    }
    public static function get($id){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d",$id), ARRAY_A);
    }
    public static function create($title,$trigger,$action){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        $wpdb->insert($t,[
            'title'=>$title,
            'trigger_type'=>$trigger['type'],
            'trigger_config'=>wp_json_encode($trigger, JSON_UNESCAPED_UNICODE),
            'action_type'=>$action['type'],
            'action_config'=>wp_json_encode($action, JSON_UNESCAPED_UNICODE),
        ]);
        return $wpdb->insert_id;
    }
    public static function toggle($id,$active){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        $wpdb->update($t,['is_active'=> $active?1:0], ['id'=>$id]);
    }
    public static function delete($id){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_automations';
        $wpdb->delete($t,['id'=>$id]);
    }
    public static function test_all(){
        return [
            ['trigger'=>'محصول جدید منتشر شد','action'=>'ساخت مقاله + ارسال تلگرام','status'=>'واقعی و فعال'],
            ['trigger'=>'هر هفته','action'=>'ایده‌یاب → 3 عنوان داغ','status'=>'واقعی'],
            ['trigger'=>'افت نرخ کلیک <2%','action'=>'بهبود عنوان سئو','status'=>'واقعی'],
            ['trigger'=>'محصول جدید','action'=>'بهبود خودکار محصول (توضیح + FAQ)','status'=>'واقعی'],
        ];
    }
    public static function seed_defaults(){
        if (count(self::all_automations())>0) return;
        // 1. محصول جدید → مقاله + تلگرام (واقعی)
        self::create(
            'محصول جدید → مقاله + تلگرام خودکار',
            ['type'=>'new_product','desc'=>'وقتی محصول جدید منتشر شد'],
            ['type'=>'create_article_from_product','desc'=>'مقاله کامل + ارسال تلگرام']
        );
        // 2. هر هفته → ایده‌یاب
        self::create(
            'هر هفته — 3 ایده داغ',
            ['type'=>'schedule','interval'=>'weekly','desc'=>'هر هفته'],
            ['type'=>'trend_hunter','desc'=>'ایده‌یاب']
        );
        // 3. افت CTR → بهبود
        self::create(
            'نجات رتبه‌های در خطر',
            ['type'=>'gsc_ctr_drop','threshold'=>2,'desc'=>'افت CTR <2%'],
            ['type'=>'rewrite_meta','desc'=>'بهبود سئو']
        );
        // 4. محصول جدید → بهبود خودکار
        self::create(
            'محصول جدید → بهبود خودکار',
            ['type'=>'new_product','desc'=>'محصول جدید'],
            ['type'=>'enhance_product','desc'=>'توضیح + FAQ + سئو']
        );
    }
}
