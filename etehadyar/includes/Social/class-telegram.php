<?php
defined('ABSPATH') || exit;
/**
 * Telegram Publisher 6.5.2 — واقعی و عیب‌یاب
 * مشکل کاربر: توکن + آیدی عددی وارد شده ولی وصل نمیشه
 * دلایل رایج: ربات ادمین کانال نیست، آیدی بدون -100، فاصله اضافی
 */
class EAIW_Telegram {
    private static function url($method){
        $token=trim(get_option('eaiw_telegram_token',''));
        $proxy=trim(get_option('eaiw_telegram_proxy',''));
        $base="https://api.telegram.org/bot{$token}/{$method}";
        if($proxy){
            // اگر پروکسی با https شروع شود و شامل {url} نباشد، به‌صورت prefix
            // مثلاً https://proxy.example.com/ → https://proxy.example.com/https://api.telegram.org/...
            // یا اگر شامل ?url= باشد، جایگزین
            if(strpos($proxy,'{url}')!==false){
                return str_replace('{url}', urlencode($base), $proxy);
            }
            // ساده: proxy + base
            $proxy=rtrim($proxy,'/');
            // اگر proxy شامل api.telegram.org نباشد، به‌عنوان prefix
            if(strpos($proxy,'api.telegram.org')===false){
                // برای allorigins: https://api.allorigins.win/raw?url=
                if(strpos($proxy,'?url=')!==false) return $proxy . urlencode($base);
                return $proxy . '/' . $base;
            }
            return $base;
        }
        return $base;
    }

    public static function send($text, $image_url='', $args=[]){
        $token=trim(get_option('eaiw_telegram_token',''));
        $chat=trim(get_option('eaiw_telegram_chat',''));
        if(isset($args['chat_id'])) $chat=trim($args['chat_id']);
        // proxy handled via url()
        $token=preg_replace('/\s+/','',$token);
        if(!$token || !$chat) return new WP_Error('no_config','توکن یا آیدی کانال تنظیم نشده — تنظیمات → شبکه‌های اجتماعی → تلگرام');

        // اگر آیدی عددی بدون -100 و 10 رقمی بود، هشدار
        if(preg_match('/^\d{5,}$/',$chat) && strpos($chat,'-100')!==0){
            // شاید آیدی کانال خصوصی را بدون -100 داده — تلاش می‌کنیم با -100 هم تست کنیم
            // ولی فعلاً با همین می‌فرستیم و خطای دقیق را برمی‌گردانیم
        }

        $text=trim($text);
        if(!$text) return new WP_Error('empty','متن خالی');

        // لاگ برای عیب‌یابی
        EAIW_Logger::log('Telegram send', ['chat'=>$chat, 'has_image'=>!empty($image_url)]);

        if($image_url){
            // برای عکس: اگر URL وردپرس باشد، تلگرام باید بتواند دانلود کند — اگر لوکال باشد خطا می‌دهد
            $resp=wp_remote_post(self::url("sendPhoto"), [
                'body'=>['chat_id'=>$chat,'photo'=>$image_url,'caption'=>mb_substr($text,0,1000),'parse_mode'=>'HTML'],
                'timeout'=>25,
            ]);
        } else {
            $resp=wp_remote_post(self::url("sendMessage"), [
                'body'=>['chat_id'=>$chat,'text'=>$text,'parse_mode'=>'HTML','disable_web_page_preview'=>'true'],
                'timeout'=>25,
            ]);
        }
        if(is_wp_error($resp)){
            return new WP_Error('http_error','خطای اتصال به تلگرام: '.$resp->get_error_message().' — هاست به api.telegram.org دسترسی دارد؟');
        }
        $code=wp_remote_retrieve_response_code($resp);
        $body=wp_remote_retrieve_body($resp);
        $data=json_decode($body, true);
        if(empty($data['ok'])){
            $desc=$data['description'] ?? "خطای $code";
            // ترجمه خطاهای رایج
            $map=[
                'chat not found'=>'کانال/گروه پیدا نشد — آیدی را چک کن: برای کانال خصوصی باید -100... باشد، برای عمومی @username. ربات هم باید ادمین کانال باشد.',
                'bot is not a member'=>'ربات عضو کانال نیست — ربات را به کانال اضافه و ادمین کن.',
                'not enough rights'=>'ربات ادمین نیست یا دسترسی ارسال ندارد — ربات را ادمین با دسترسی Post messages کن.',
                'unauthorized'=>'توکن نامعتبر — از @BotFather دوباره کپی کن (فاصله اضافی نداشته باشد).',
                'wrong string length'=>'توکن اشتباه کپی شده — کامل کپی کن.',
            ];
            foreach($map as $en=>$fa){
                if(stripos($desc,$en)!==false) $desc.=" — $fa";
            }
            return new WP_Error('tg_error', $desc . " — پاسخ: $body");
        }
        return ['ok'=>true,'message_id'=>$data['result']['message_id'] ?? 0, 'chat'=> $data['result']['chat']['title'] ?? $chat];
    }

    public static function test(){
        $token=trim(get_option('eaiw_telegram_token',''));
        $chat=trim(get_option('eaiw_telegram_chat',''));
        $token=preg_replace('/\s+/','',$token);
        if(!$token) return new WP_Error('no_token','توکن وارد نشده — از @BotFather بگیر و بدون فاصله پیست کن');
        if(strlen($token) < 30) return new WP_Error('bad','توکن خیلی کوتاه است — کامل کپی کن (مثل 123456:ABC...)');

        // 1. getMe
        $resp=wp_remote_get(self::url("getMe"), ['timeout'=>12]);
        if(is_wp_error($resp)) return new WP_Error('http','خطای اتصال به api.telegram.org: '.$resp->get_error_message().' — فایروال هاست را چک کن');
        $data=json_decode(wp_remote_retrieve_body($resp), true);
        if(empty($data['ok'])) return new WP_Error('bad','توکن نامعتبر: '.($data['description']??'').' — دوباره از @BotFather بگیر');
        $bot=$data['result']['username'] ?? '';
        $bot_id=$data['result']['id'] ?? '';

        if(!$chat) return ['ok'=>true,'name'=>$bot,'id'=>$bot_id,'note'=>'توکن درست است ✅ — حالا آیدی کانال را وارد کن (برای تست کامل)'];

        // 2. getChat — آیا ربات به کانال دسترسی دارد؟
        $resp2=wp_remote_post(self::url("getChat"), ['body'=>['chat_id'=>$chat],'timeout'=>12]);
        if(is_wp_error($resp2)) return new WP_Error('chat_http','توکن درست ولی اتصال به کانال ناموفق: '.$resp2->get_error_message());
        $data2=json_decode(wp_remote_retrieve_body($resp2), true);
        if(empty($data2['ok'])){
            $desc=$data2['description'] ?? '';
            if(stripos($desc,'chat not found')!==false) return new WP_Error('chat_not_found',"توکن درسته (@$bot) ولی کانال پیدا نشد — آیدی: $chat — برای کانال خصوصی باید -100... باشد (از @getidsbot بگیر)، برای عمومی @username — و ربات باید ادمین باشد");
            if(stripos($desc,'not found')!==false) return new WP_Error('chat_not_found',"کانال $chat پیدا نشد — $desc");
            return new WP_Error('chat_error', "توکن درسته ولی کانال خطا داد: $desc");
        }
        $title=$data2['result']['title'] ?? $chat;
        $type=$data2['result']['type'] ?? '';

        // 3. getChatMember — آیا ربات ادمین است؟
        $resp3=wp_remote_post(self::url("getChatMember"), ['body'=>['chat_id'=>$chat,'user_id'=>$bot_id],'timeout'=>12]);
        $admin='نامشخص';
        if(!is_wp_error($resp3)){
            $d3=json_decode(wp_remote_retrieve_body($resp3), true);
            if(!empty($d3['ok'])) $admin=$d3['result']['status'] ?? 'member';
        }

        return ['ok'=>true,'name'=>$bot,'id'=>$bot_id,'chat_title'=>$title,'chat_type'=>$type,'admin_status'=>$admin,'note'=> $admin==='administrator' || $admin==='creator' ? 'همه چیز آماده ✅ — ربات ادمین است' : 'توکن و کانال درسته ولی ربات ادمین نیست — برو کانال → مدیران → افزودن ربات به‌عنوان ادمین (Post messages)'];
    }

    public static function send_order($order_id){
        if(!get_option('eaiw_telegram_order_enabled')) return;
        $chat=trim(get_option('eaiw_telegram_order_chat','')) ?: trim(get_option('eaiw_telegram_chat',''));
        if(!$chat) return;
        $order=wc_get_order($order_id);
        if(!$order) return;
        $items=[];
        foreach($order->get_items() as $it) $items[]=$it->get_name().' ×'.$it->get_quantity();
        $text="🛒 سفارش جدید #{$order_id}\n".
              "👤 ".$order->get_billing_first_name().' '.$order->get_billing_last_name()."\n".
              "💰 ".strip_tags(wc_price($order->get_total()))." — ".$order->get_payment_method_title()."\n".
              "📦 ".implode('، ', array_slice($items,0,3))."\n".
              "🔗 ".admin_url('post.php?post='.$order_id.'&action=edit');
        return self::send($text,'',['chat_id'=>$chat]);
    }

    // برای عیب‌یابی سریع — لاگ آخرین خطا
    public static function last_error(){
        return get_option('eaiw_telegram_last_error','');
    }
}
