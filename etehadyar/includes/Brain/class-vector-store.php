<?php
defined('ABSPATH') || exit;
class EAIW_Vector_Store {
    // ساده: embedding را به‌صورت JSON ذخیره می‌کنیم — در آینده VECTOR type MySQL 8
    public static function upsert($object_type, $object_id, $chunk_index, $content, $embedding=null){
        global $wpdb;
        $t = $wpdb->prefix.'eaiw_vectors';
        $hash = hash('sha256', $content);
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE object_type=%s AND object_id=%d AND chunk_index=%d", $object_type, $object_id, $chunk_index));
        $data = [
            'object_type'=>$object_type,
            'object_id'=>$object_id,
            'chunk_index'=>$chunk_index,
            'content'=>wp_kses_post($content),
            'content_hash'=>$hash,
            'embedding'=> $embedding ? wp_json_encode($embedding) : null,
            'tokens'=> self::count_tokens($content),
        ];
        if ($exists) $wpdb->update($t, $data, ['id'=>$exists]);
        else $wpdb->insert($t, $data);
    }
    public static function search($query_embedding, $top_k=6){
        global $wpdb; $t=$wpdb->prefix.'eaiw_vectors';
        // اگر embedding واقعی نداریم — fallback به LIKE معنایی ساده
        if (!$query_embedding) {
            return [];
        }
        // Cosine similarity brute-force (برای ۱۰k چانک کافی است)
        $rows = $wpdb->get_results("SELECT * FROM $t WHERE embedding IS NOT NULL LIMIT 800", ARRAY_A);
        $scored=[];
        foreach($rows as $r){
            $emb = json_decode($r['embedding'], true);
            if (!$emb) continue;
            $score = self::cosine($query_embedding, $emb);
            $scored[] = ['score'=>$score, 'row'=>$r];
        }
        usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
        return array_slice($scored,0,$top_k);
    }
    public static function cosine($a,$b){
        $dot=0; $na=0; $nb=0;
        $len = min(count($a), count($b));
        for($i=0;$i<$len;$i++){ $dot+=$a[$i]*$b[$i]; $na+=$a[$i]*$a[$i]; $nb+=$b[$i]*$b[$i]; }
        return $dot / (sqrt($na)*sqrt($nb) + 1e-9);
    }
    public static function count_tokens($text){
        return (int) (mb_strlen($text)/4);
    }
    public static function chunk_text($text, $size=900){
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/u',' ', $text);
        $chunks=[]; $len=mb_strlen($text);
        for($i=0;$i<$len;$i+=$size) $chunks[] = mb_substr($text,$i,$size);
        return $chunks;
    }
}
