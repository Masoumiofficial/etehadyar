<?php
defined('ABSPATH') || exit;
class EAIW_RAG {
    public static function search($query, $top_k=6){
        if (!$query) return [];
        // ابتدا embedding query
        $q_emb = self::embed_query($query);
        $hits = EAIW_Vector_Store::search($q_emb, $top_k);
        // اگر hits خالی، fallback LIKE
        if (empty($hits)) {
            global $wpdb;
            $t=$wpdb->prefix.'eaiw_vectors';
            $like='%'.$wpdb->esc_like($query).'%';
            $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE content LIKE %s LIMIT %d",$like,$top_k),ARRAY_A);
            $hits = array_map(fn($r)=>['score'=>0.5,'row'=>$r], $rows);
        }
        $out=[];
        foreach($hits as $h){
            $r=$h['row'];
            $post = get_post($r['object_id']);
            $out[]=[
                'score'=> round($h['score'],3),
                'type'=>$r['object_type'],
                'id'=>$r['object_id'],
                'title'=> $post ? get_the_title($post) : $r['object_type'].' #'.$r['object_id'],
                'url'=> $post ? get_permalink($post) : '',
                'snippet'=> mb_substr($r['content'],0,180).'…',
                'edit_url'=> $post ? get_edit_post_link($post->ID,'') : '',
            ];
        }
        return $out;
    }
    private static function embed_query($q){
        // mock determinist مشابه Site_Brain
        $hash=md5($q); $vec=[];
        for($i=0;$i<64;$i++) $vec[]=(hexdec(substr($hash,$i%32,1))/15.0)*2-1;
        $norm = sqrt(array_sum(array_map(fn($x)=>$x*$x,$vec)));
        return array_map(fn($x)=>$x/($norm?:1),$vec);
    }
    // تزریق به Prompt — برای Providerها
    public static function context_for_prompt($query, $k=4){
        $hits = self::search($query,$k);
        if (!$hits) return '';
        $ctx="منابع تأییدشده سایت (از Site Brain):\n";
        foreach($hits as $i=>$h){
            $ctx .= sprintf("%d. [%s] %s — %s\n%s\n", $i+1, $h['type'], $h['title'], $h['url'], $h['snippet']);
        }
        return $ctx;
    }
}
