<?php
defined('ABSPATH') || exit;
class EAIW_Agent_Gardener {
    public function run(){
        // مقالات قدیمی یا کم‌عمق — Health 3.4.0
        $q = new WP_Query([
            'post_type'=>'post','post_status'=>'publish','posts_per_page'=>5,
            'orderby'=>'modified','order'=>'ASC',
            'date_query'=>[['before'=>'6 months ago']]
        ]);
        $candidates=[];
        foreach($q->posts as $p){
            $words = str_word_count(strip_tags($p->post_content));
            if ($words < 800 || strtotime($p->post_modified) < strtotime('-6 months')){
                $candidates[]=['id'=>$p->ID,'title'=>get_the_title($p),'words'=>$words,'modified'=>$p->post_modified];
            }
        }
        // فقط گزارش — بازنویسی با تأیید کاربر (مثل 3.2.0)
        return ['agent'=>'gardener','candidates'=>$candidates,'count'=>count($candidates),'message'=> count($candidates) ? count($candidates).' مقاله نیاز به باغبانی دارد' : 'باغ محتوا سرسبز است'];
    }
}
