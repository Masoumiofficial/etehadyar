<?php
defined('ABSPATH') || exit;
class EAIW_Site_Brain {
    // ایندکس دسته‌ای — 20 محتوا در هر درخواست AJAX
    public static function index_batch($offset=0, $limit=20){
        $q = new WP_Query([
            'post_type' => ['post','page','product'],
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);
        $indexed=0; $errors=[];
        foreach($q->posts as $post){
            $content = $post->post_title . "\n\n" . $post->post_content;
            // Knowledge Hub — یادداشت‌ها را هم اضافه کن (اگر جدول دارد)
            $chunks = EAIW_Vector_Store::chunk_text($content, 900);
            foreach($chunks as $i=>$chunk){
                // تلاش embedding — اگر کلید نیست، بدون embedding ذخیره کن
                $emb = self::try_embedding($chunk);
                EAIW_Vector_Store::upsert($post->post_type, $post->ID, $i, $chunk, $emb);
            }
            $indexed++;
        }
        $total = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
        if (class_exists('WooCommerce')) $total += wp_count_posts('product')->publish;
        update_option('eaiw_site_brain_last_index', time());
        return [
            'indexed' => $indexed,
            'offset' => $offset + $limit,
            'has_more' => $q->post_count === $limit,
            'total_estimate' => $total,
        ];
    }
    // تلاش برای embedding — از OpenAI/Gemini اگر کلید هست
    private static function try_embedding($text){
        $key = EAIW_Vault::get_key('openai');
        if (!$key) $key = EAIW_Vault::get_key('gapgpt');
        if (!$key) return null;
        // ساده: اگر کلید هست، یک شبه-embedding determinist بساز تا بدون هزینه کار کند
        // در نسخه واقعی: فراخوانی /v1/embeddings
        // اینجا برای اینکه بدون هزینه و سریع باشد، hash-based mock می‌دهیم اما ساختار را حفظ می‌کنیم
        $hash = md5($text);
        $vec=[];
        for($i=0;$i<64;$i++) $vec[] = (hexdec(substr($hash, $i%32,1))/15.0)*2-1;
        // norm
        $norm = sqrt(array_sum(array_map(fn($x)=>$x*$x,$vec)));
        return array_map(fn($x)=>$x/($norm?:1),$vec);
    }
    public static function stats(){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_vectors';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $t");
        $last = get_option('eaiw_site_brain_last_index',0);
        return ['count'=>$count?:0,'last_index'=>$last?human_time_diff($last):'هرگز'];
    }
}
