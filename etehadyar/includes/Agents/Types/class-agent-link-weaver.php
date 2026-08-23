<?php
defined('ABSPATH') || exit;
class EAIW_Agent_Link_Weaver {
    public function run(){
        // پیشنهاد لینک داخلی با Site Brain
        $posts = get_posts(['posts_per_page'=>3,'post_status'=>'publish','orderby'=>'rand']);
        $suggestions=[];
        foreach($posts as $p){
            $hits = EAIW_RAG::search($p->post_title, 3);
            $links=[];
            foreach($hits as $h){
                if ($h['id']==$p->ID) continue;
                $links[]=['from'=>$p->ID,'to'=>$h['id'],'anchor'=>mb_substr($h['title'],0,30),'score'=>$h['score'],'url'=>$h['url']];
            }
            if ($links) $suggestions[]=['post_id'=>$p->ID,'title'=>get_the_title($p),'links'=>$links];
        }
        return ['agent'=>'link_weaver','suggestions'=>$suggestions,'message'=> $suggestions? 'شبکه لینک پیشنهادی آماده است' : 'لینک جدیدی پیشنهاد نشد'];
    }
}
