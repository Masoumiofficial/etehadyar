<?php
defined('ABSPATH') || exit;
class EAIW_Guardian {
    public static function scan(){
        $issues=[];
        // 1. مقالات بدون تصویر شاخص
        $q = new WP_Query(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>30,'meta_query'=>[['key'=>'_thumbnail_id','compare'=>'NOT EXISTS']]]);
        if ($q->found_posts) $issues[]=['type'=>'no_thumb','count'=>$q->found_posts,'fix'=>'افزودن تصویر با Vision Studio','severity'=>'medium'];
        // 2. لینک شکسته ساده — چک URL داخلی
        $posts = get_posts(['posts_per_page'=>10,'post_status'=>'publish']);
        $broken=0;
        foreach($posts as $p){ if (strpos($p->post_content,'href=""')!==false) $broken++; }
        if ($broken) $issues[]=['type'=>'broken_link','count'=>$broken,'fix'=>'ترمیم خودکار لینک','severity'=>'high'];
        // 3. اسکیما ناقص
        $no_schema = 0;
        foreach($posts as $p){ if (!get_post_meta($p->ID,'_yoast_wpseo_schema_article_type',true)) $no_schema++; }
        if ($no_schema>5) $issues[]=['type'=>'schema','count'=>$no_schema,'fix'=>'تزریق FAQ/Article Schema','severity'=>'low'];
        set_transient('eaiw_guardian_last_scan',['time'=>time(),'issues'=>$issues], 12*HOUR_IN_SECONDS);
        return $issues;
    }
    public static function last_scan(){
        return get_transient('eaiw_guardian_last_scan') ?: ['time'=>0,'issues'=>[]];
    }
}
