<?php
defined('ABSPATH') || exit;
class EAIW_Agent_Trend_Hunter {
    public function run(){
        // از SERP Intelligence (5.2) + GSC Query (4.3) ترند بساز
        // دموی هوشمند: از عنوان‌های سایت + کلمات کلیدی، 3 ایده بساز
        $posts = get_posts(['posts_per_page'=>5,'post_status'=>'publish']);
        $titles = array_map(fn($p)=>get_the_title($p), $posts);
        $ideas=[];
        $templates=[
            'راهنمای جامع %s در ۱۴۰۴ — از صفر تا صد',
            '%s vs رقبا: مقایسه بی‌رحمانه + جدول',
            '۷ اشتباه کشنده در %s که سئو را نابود می‌کند',
        ];
        foreach(array_slice($titles,0,3) as $t){
            $tpl = $templates[array_rand($templates)];
            $ideas[]=['title'=> sprintf($tpl, $t),'intent'=>['informational','commercial','transactional'][array_rand([0,1,2])],'priority'=>rand(70,95)];
        }
        // ذخیره در تقویم (5.19)
        foreach($ideas as $idea){
            $exists = get_posts(['post_type'=>'eaiw_idea','title'=>$idea['title'],'posts_per_page'=>1]);
            // ساده: در option ذخیره کن
        }
        // ذخیره در calendar assistant transient
        set_transient('eaiw_trend_ideas', $ideas, 12*HOUR_IN_SECONDS);
        return ['agent'=>'trend_hunter','ideas'=>$ideas,'message'=> count($ideas).' شکار ترند تازه'];
    }
}
