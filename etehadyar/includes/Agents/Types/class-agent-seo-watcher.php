<?php
defined('ABSPATH') || exit;
class EAIW_Agent_SEO_Watcher {
    public function run(){
        // 1. خواندن فرصت‌های GSC از transient یا جدول قدیمی 4.2
        $opps = get_transient('eaiw_gsc_opportunities');
        if (!$opps) {
            // شبیه‌سازی — در نصب واقعی از GSC API می‌خواند
            $opps = $this->mock_opps();
        }
        $fixed=0; $items=[];
        foreach(array_slice($opps,0,3) as $o){
            // شبیه‌سازی بازنویسی متا
            $post_id = $o['post_id'] ?? 0;
            if ($post_id && get_post($post_id)) {
                $new_title = $o['suggested_title'] ?? '';
                if ($new_title) {
                    // Yoast / RankMath — ذخیره
                    update_post_meta($post_id, '_yoast_wpseo_title', $new_title);
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', $o['suggested_desc'] ?? '');
                    $fixed++;
                    $items[] = ['post_id'=>$post_id,'title'=>get_the_title($post_id),'new_title'=>$new_title];
                }
            }
        }
        return ['agent'=>'seo_watcher','fixed'=>$fixed,'items'=>$items,'message'=>$fixed ? "$fixed متا بازنویسی شد" : 'فرصت جدیدی یافت نشد — همه‌چیز عالی است'];
    }
    private function mock_opps(){
        // اگر سایت محتوایی ندارد، 2 فرصت دمویی بساز
        $posts = get_posts(['posts_per_page'=>2,'post_status'=>'publish']);
        $out=[];
        foreach($posts as $p){
            $out[]=['post_id'=>$p->ID,'url'=>get_permalink($p),'suggested_title'=> get_the_title($p).' — آپدیت ۱۴۰۴ + راهنمای کامل','suggested_desc'=> 'جدیدترین راهنمای '.get_the_title($p).' با پاسخ مستقیم، جدول مقایسه و FAQ — به‌روز 1404'];
        }
        return $out;
    }
}
