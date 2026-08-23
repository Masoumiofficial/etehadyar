<?php
defined('ABSPATH') || exit;
class EAIW_Oracle {
    // پیش‌بینی ساده بر اساس GSC + GA4 — در نسخه واقعی با AI
    public static function predict(){
        $gsc = get_transient('eaiw_gsc_pages');
        if (!$gsc) $gsc = self::mock_gsc();
        $out=[];
        foreach(array_slice($gsc,0,6) as $row){
            $ctr = $row['ctr'] ?? 2.5;
            $pos = $row['position'] ?? 12;
            $risk = ($ctr < 2 && $pos>8) ? 'high' : (($ctr<3)?'medium':'low');
            $forecast = $pos>10 ? 'رشد +۳۰٪ با بازنویسی متا' : 'پایدار';
            $out[] = array_merge($row, ['risk'=>$risk,'forecast'=>$forecast]);
        }
        return $out;
    }
    private static function mock_gsc(){
        $posts = get_posts(['posts_per_page'=>6,'post_status'=>'publish']);
        $arr=[];
        foreach($posts as $p){
            $arr[]=['url'=>get_permalink($p),'title'=>get_the_title($p),'clicks'=>rand(120,900),'impressions'=>rand(2000,15000),'ctr'=> round(rand(12,45)/10,1),'position'=> round(rand(80,180)/10,1)];
        }
        return $arr;
    }
}
