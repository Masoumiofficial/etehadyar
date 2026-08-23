<?php
defined('ABSPATH') || exit;
/**
 * ChatSoul 6.6 — باهوش، بامزه، فان + FAQ + GapGPT + شخصیت
 */
class EAIW_ChatSoul {
    public static function faqs(){
        $faqs=get_option('eaiw_chat_faqs',[]);
        if(!is_array($faqs)) $faqs=[];
        return $faqs;
    }
    public static function save_faqs($faqs){
        update_option('eaiw_chat_faqs', array_values(array_filter($faqs, fn($f)=>!empty($f['q']) && !empty($f['a']))), false);
    }

    public static function rest_chat($request){
        $msg = sanitize_textarea_field($request->get_param('message') ?? $request->get_param('q') ?? '');
        if (!$msg) return new WP_Error('empty','پیام خالی',['status'=>400]);
        $soul_name = get_option('eaiw_chatsoul_name','اتحادیار');
        if (!$soul_name) $soul_name='اتحادیار';
        // شخصیت فان
        $personality = "تو {$soul_name} هستی — دستیار باهوش، بامزه و فانِ سایت ".get_bloginfo('name').". لحنت صمیمی، شوخ، ولی حرفه‌ای و دقیق. کمی طنز ملایم بزن، ولی جواب را درست بده. فارسی صحبت کن، کوتاه و مفید، با ایموجی کم.";
        // FAQ اول
        $faqs=self::faqs();
        $faq_hit=null;
        foreach($faqs as $f){
            if(mb_stripos($msg, mb_substr($f['q'],0,12))!==false || similar_text(mb_strtolower($msg), mb_strtolower($f['q'])) > 60){
                $faq_hit=$f;
                break;
            }
            // کلیدواژه
            $keys=explode(' ', $f['q']);
            $match=0; foreach($keys as $k) if(mb_strlen($k)>2 && mb_stripos($msg,$k)!==false) $match++;
            if($match>=2) { $faq_hit=$f; break; }
        }
        if($faq_hit){
            $answer="😎 سوال خوبیه!\n\n".$faq_hit['a']."\n\n— {$soul_name} ✨";
            $sources=[['title'=>$faq_hit['q'],'url'=>'','snippet'=>'FAQ']];
            self::log($request,$msg,$answer,$sources);
            return rest_ensure_response(['answer'=>$answer,'sources'=>$sources,'session_id'=>sanitize_text_field($request->get_param('session_id')?:'faq'),'name'=>$soul_name,'type'=>'faq']);
        }

        // RAG
        $ctx = EAIW_RAG::context_for_prompt($msg, 3);
        $has_ctx = trim($ctx) !== '';
        // اگر سوال عمومی و بی‌ربط به سایت (سلام، چطوری، جوک...) → GapGPT مستقیم
        $is_smalltalk = preg_match('/^(سلام|درود|هی|هلو|چطوری|خوبی|جوک|لطیفه|شوخی|بگو|help)/iu', trim($msg)) || mb_strlen($msg)<12;
        $system = $personality . "\n";
        if($has_ctx) $system .= "دانش سایت:\n$ctx\n\nاز این دانش استفاده کن، اگر ربط نداشت نادیده بگیر.\n";
        $system .= "قوانین: 1) فارسی، 2) کوتاه (زیر 120 کلمه) مگر کاربر خواست، 3) اگر نمی‌دانی بگو نمی‌دانم ولی بامزه، 4) لینک مرتبط اگر داری بده.";

        // اول GapGPT / OpenAI را امتحان کن (برای صحبت عادی)
        $key = EAIW_Vault::get_key('gapgpt') ?: EAIW_Vault::get_key('openai');
        // اگر GapGPT هست، اولویت با او (برای فارسی و فان بهتره)
        $gap_key = EAIW_Vault::get_key('gapgpt');
        $use_key = $gap_key ?: $key;
        $answer='';
        if($use_key){
            $real=self::call_llm($system,$msg,$use_key);
            if(!is_wp_error($real) && $real) $answer=$real;
        }
        if(!$answer){
            if($has_ctx){
                $answer="سلام! من {$soul_name} هستم 😎\nبرای «".mb_substr($msg,0,40)."» اینو از خودِ سایت پیدا کردم:\n\n".wp_trim_words(strip_tags($ctx), 70)."\n\nکلید GapGPT/OpenAI رو وصل کن تا جوابام بامزه‌تر و دقیق‌تر بشه!";
            } else {
                $answer="سلام! من {$soul_name} هستم — دستیار بامزه‌ات 😄\nبرای «$msg» هنوز چیزی تو سایت پیدا نکردم — ولی اگه GapGPT رو وصل کنی، هرچی بخوای (حتی جوک!) رو جواب می‌دم. اول برو حافظه هوشمند رو ایندکس کن!";
            }
        }
        $sources=EAIW_RAG::search($msg,3);
        self::log($request,$msg,$answer,$sources);
        return rest_ensure_response(['answer'=>$answer,'sources'=>$sources,'session_id'=>sanitize_text_field($request->get_param('session_id')?:'guest'),'name'=>$soul_name,'type'=>$has_ctx?'rag':'gapgpt']);
    }

    private static function log($request,$msg,$answer,$sources){
        global $wpdb;
        $t=$wpdb->prefix.'eaiw_chatsoul_logs';
        $sid=sanitize_text_field($request->get_param('session_id') ?: 'guest-'.substr(md5(($_SERVER['REMOTE_ADDR']??'0').($_SERVER['HTTP_USER_AGENT']??'')),0,8));
        if($wpdb->get_var("SHOW TABLES LIKE '$t'")==$t){
            $wpdb->insert($t,['session_id'=>$sid,'role'=>'user','message'=>$msg]);
            $wpdb->insert($t,['session_id'=>$sid,'role'=>'assistant','message'=>$answer,'sources'=>wp_json_encode($sources, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private static function call_llm($system,$user,$key){
        $is_gap = (strpos($key,'gapgpt')!==false) || EAIW_Vault::get_key('gapgpt')===$key;
        $endpoint=$is_gap ? 'https://api.gapgpt.app/api/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
        $model=$is_gap ? 'gpt-4o-mini' : 'gpt-4o-mini';
        // شخصیت فان را هم به system اضافه کردیم
        $resp=wp_remote_post($endpoint, [
            'headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'],
            'body'=>wp_json_encode([
                'model'=>$model,
                'messages'=>[['role'=>'system','content'=>$system],['role'=>'user','content'=>$user]],
                'temperature'=>0.85, // کمی فان
                'max_tokens'=>600,
            ], JSON_UNESCAPED_UNICODE),
            'timeout'=>28,
        ]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        $data=json_decode(wp_remote_retrieve_body($resp), true);
        if($code!==200) return new WP_Error('api_error', $data['error']['message'] ?? "خطای $code");
        $text=$data['choices'][0]['message']['content'] ?? '';
        return $text ? trim($text) : new WP_Error('empty','پاسخ خالی');
    }
}
