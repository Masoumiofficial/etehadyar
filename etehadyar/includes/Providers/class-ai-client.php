<?php
defined('ABSPATH') || exit;
/**
 * EAIW AI Client 6.7.3 — با انتخاب هوش و فال‌بک خودکار
 * اگر OpenAI اعتبار نداشت → خودکار GapGPT
 */
class EAIW_AI_Client {
    public static function providers(){
        // لیست با وضعیت
        $list=[
            'gapgpt'=>['label'=>'GapGPT (ایران) 🇮🇷','key'=>EAIW_Vault::get_key('gapgpt'),'recommended'=>true],
            'openai'=>['label'=>'OpenAI','key'=>EAIW_Vault::get_key('openai'),'recommended'=>false],
            'gemini'=>['label'=>'Gemini','key'=>EAIW_Vault::get_key('gemini'),'recommended'=>false],
            'claude'=>['label'=>'Claude','key'=>EAIW_Vault::get_key('claude'),'recommended'=>false],
        ];
        foreach($list as $k=>&$v){
            $v['has_key']=!empty($v['key']);
            $v['active']=!empty($v['key']);
        }
        return $list;
    }

    public static function complete($prompt, $system='', $args=[]){
        $args = wp_parse_args($args, [
            'provider' => '', // '' = auto
            'model' => '',
            'temperature' => 0.7,
            'max_tokens' => 4000,
        ]);
        $requested = $args['provider'];
        // اگر کاربر انتخاب کرده، همونو امتحان کن، اگر نشد فال‌بک
        $try_order=[];
        if($requested && isset(self::providers()[$requested]) && self::providers()[$requested]['has_key']){
            $try_order[]=$requested;
        }
        // بعد بقیه به ترتیب اولویت (GapGPT اول برای ایران)
        foreach(['gapgpt','openai','gemini','claude'] as $p){
            if(!in_array($p,$try_order) && self::providers()[$p]['has_key']) $try_order[]=$p;
        }
        if(empty($try_order)){
            return new WP_Error('no_key','هیچ کلید فعالی نیست — به تنظیمات → کلیدها برو و GapGPT (پیشنهادی برای ایران) را وارد کن');
        }

        $last_error=null;
        foreach($try_order as $provider){
            $res=self::call_one($provider,$prompt,$system,$args);
            if(!is_wp_error($res)) return $res;
            $msg=$res->get_error_message();
            // اگر خطای اعتبار بود، ادامه بده به بعدی
            if(stripos($msg,'no credits')!==false || stripos($msg,'billing')!==false || stripos($msg,'insufficient_quota')!==false || stripos($msg,'You have no credits')!==false){
                $last_error=new WP_Error('credits','اعتبار '.$provider.' تمام شده — دارم با بعدی امتحان می‌کنم... ('.$msg.')');
                continue;
            }
            // اگر خطای Rate limit یا 429، هم فال‌بک
            if(stripos($msg,'429')!==false || stripos($msg,'rate')!==false){
                $last_error=$res; continue;
            }
            // بقیه خطاها را مستقیم برگردان
            return $res;
        }
        // همه را امتحان کردیم و نشد
        if($last_error){
            // اگر GapGPT هم نبود، پیام فارسی دوستانه
            if(!self::providers()['gapgpt']['has_key']){
                return new WP_Error('no_credits','اعتبار OpenAI تمام شده — لطفاً از GapGPT استفاده کن (برای ایران بدون تحریم، ارزان‌تر). به تنظیمات → GapGPT برو و کلید بگیر: gapgpt.app');
            }
            return new WP_Error('all_failed','همه هوش‌ها خطا دادند — آخرین خطا: '.$last_error->get_error_message());
        }
        return new WP_Error('all_failed','هیچ هوشی جواب نداد');
    }

    private static function call_one($provider,$prompt,$system,$args){
        $args['provider']=$provider;
        switch($provider){
            case 'openai': return self::openai($prompt,$system,$args,false);
            case 'gapgpt': return self::openai($prompt,$system,$args,true);
            case 'gemini': return self::gemini($prompt,$system,$args);
            case 'claude': return self::claude($prompt,$system,$args);
            default: return new WP_Error('bad','پرووایدر ناشناخته');
        }
    }

    private static function pick_provider(){
        foreach(['gapgpt','openai','gemini','claude'] as $p) if(self::providers()[$p]['has_key']) return $p;
        return '';
    }

    private static function openai($prompt,$system,$args,$is_gapgpt){
        $key=$is_gapgpt?EAIW_Vault::get_key('gapgpt'):EAIW_Vault::get_key('openai');
        $endpoint=$is_gapgpt?'https://api.gapgpt.app/api/v1/chat/completions':'https://api.openai.com/v1/chat/completions';
        $model=$args['model'] ?: ($is_gapgpt?'gpt-4o-mini':'gpt-4o-mini');
        $messages=[]; if($system) $messages[]=['role'=>'system','content'=>$system];
        $messages[]=['role'=>'user','content'=>$prompt];
        $body=['model'=>$model,'messages'=>$messages,'temperature'=>(float)$args['temperature'],'max_tokens'=>(int)$args['max_tokens']];
        $resp=wp_remote_post($endpoint,['headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'],'body'=>wp_json_encode($body,JSON_UNESCAPED_UNICODE),'timeout'=> (int)get_option('eaiw_timeout',45)]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        $data=json_decode(wp_remote_retrieve_body($resp),true);
        if($code!==200){
            $msg=$data['error']['message'] ?? "خطای $code از ".($is_gapgpt?'GapGPT':'OpenAI');
            // پیام دقیق برای اعتبار
            if(stripos($msg,'no credits')!==false || stripos($msg,'billing')!==false || stripos($msg,'insufficient')!==false){
                return new WP_Error('no_credits', $msg.' — اعتبار تمام شده');
            }
            return new WP_Error('api_error',$msg);
        }
        $text=$data['choices'][0]['message']['content'] ?? '';
        if(!$text) return new WP_Error('empty','پاسخ خالی');
        return trim($text);
    }
    private static function gemini($prompt,$system,$args){
        $key=EAIW_Vault::get_key('gemini');
        $model=$args['model'] ?: 'gemini-1.5-flash';
        $endpoint="https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $full=$system ? $system."\n\n".$prompt : $prompt;
        $body=['contents'=>[['parts'=>[['text'=>$full]]]],'generationConfig'=>['temperature'=>(float)$args['temperature'],'maxOutputTokens'=>(int)$args['max_tokens']]];
        $resp=wp_remote_post($endpoint,['headers'=>['Content-Type'=>'application/json'],'body'=>wp_json_encode($body,JSON_UNESCAPED_UNICODE),'timeout'=>45]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        $data=json_decode(wp_remote_retrieve_body($resp),true);
        if($code!==200) return new WP_Error('api_error',$data['error']['message'] ?? "خطای $code از Gemini");
        $text=$data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $text?trim($text):new WP_Error('empty','پاسخ خالی');
    }
    private static function claude($prompt,$system,$args){
        $key=EAIW_Vault::get_key('claude');
        $model=$args['model'] ?: 'claude-3-5-sonnet-20241022';
        $body=['model'=>$model,'max_tokens'=>(int)$args['max_tokens'],'temperature'=>(float)$args['temperature'],'system'=>$system?:'تو دستیار فارسی هستی','messages'=>[['role'=>'user','content'=>$prompt]]];
        $resp=wp_remote_post('https://api.anthropic.com/v1/messages',['headers'=>['x-api-key'=>$key,'anthropic-version'=>'2023-06-01','Content-Type'=>'application/json'],'body'=>wp_json_encode($body,JSON_UNESCAPED_UNICODE),'timeout'=>45]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        $data=json_decode(wp_remote_retrieve_body($resp),true);
        if($code!==200) return new WP_Error('api_error',$data['error']['message'] ?? "خطای $code از Claude");
        $text=$data['content'][0]['text'] ?? '';
        return $text?trim($text):new WP_Error('empty','پاسخ خالی');
    }

    public static function json_complete($prompt,$system,$args=[]){
        $raw=self::complete($prompt,$system,$args);
        if(is_wp_error($raw)) return $raw;
        $json=self::extract_json($raw);
        if(!$json) return new WP_Error('bad_json','خروجی JSON نبود: '.mb_substr($raw,0,250));
        return $json;
    }
    private static function extract_json($text){
        $d=json_decode(trim($text),true); if($d) return $d;
        if(preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i',$text,$m)){ $d=json_decode(trim($m[1]),true); if($d) return $d; }
        if(preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/',$text,$m)){ $d=json_decode(trim($m[0]),true); if($d) return $d; }
        return null;
    }
}
