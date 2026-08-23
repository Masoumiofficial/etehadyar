<?php
defined('ABSPATH') || exit;
/**
 * Omnichannel Factory 6.1 — واقعی و قابل استفاده
 * یک ورودی → 7 خروجی واقعی با AI
 */
class EAIW_Omnichannel_Factory {
    // حالت قدیمی (برای سازگاری)
    public static function generate_from_post($post_id){
        return self::generate_full(['post_id'=>$post_id]);
    }

    /**
     * تولید کامل — واقعی
     * $input: ['prompt'=>string, 'post_id'=>int, 'tone'=>string, 'length'=>int]
     */
    public static function generate_full($input){
        $prompt = trim($input['prompt'] ?? '');
        $post_id = intval($input['post_id'] ?? 0);
        $tone = $input['tone'] ?? 'حرفه‌ای و صمیمی';
        $length = intval($input['length'] ?? 1200);
        $provider = sanitize_text_field($input['provider'] ?? ''); // 6.7.3 — انتخاب هوش

        if($post_id){
            $post=get_post($post_id);
            if(!$post) return new WP_Error('not_found','نوشته پیدا نشد');
            $prompt = get_the_title($post) . "\n\n" . wp_trim_words(strip_tags($post->post_content), 40);
            $title = get_the_title($post);
        } else {
            if(!$prompt) return new WP_Error('empty','لطفاً موضوع را بنویسید');
            $title = mb_substr($prompt,0,60);
        }

        $trace=EAIW_Logger::log('Factory generate', ['title'=>$title]);

        // 1. مقاله HTML (واقعی AI) — با هوش انتخابی
        $article = self::make_article($title, $prompt, $tone, $length, $provider);
        if(is_wp_error($article)){
            // اگر خطای اعتبار بود، پیام فارسی دوستانه
            $msg=$article->get_error_message();
            if(stripos($msg,'no credits')!==false || stripos($msg,'billing')!==false || stripos($msg,'اعتبار')!==false){
                return new WP_Error('credits','اعتبار هوش انتخابی تمام شده — با GapGPT (ایران) دوباره امتحان کن یا از تنظیمات کلید را شارژ کن. — '.$msg);
            }
            return $article;
        }

        // 2. استخراج 3 پرامپت تصویر از مقاله (AI)
        $image_prompts = self::make_image_prompts($title, $article['html'], $provider);
        $images=[];
        foreach(array_slice($image_prompts,0,3) as $ip){
            $img = EAIW_Flux_Client::generate($ip, 'photorealistic', '1792x1024');
            if(!is_wp_error($img)) $images[]=$img;
        }
        if(empty($images)){
            // fallback placeholder
            $ph=EAIW_Vision_Studio::generate("تصویر شاخص برای $title", 'photorealistic','1792x1024');
            if(!is_wp_error($ph)) $images[]=$ph;
        }

        // 3. ویدیو 60 ثانیه (سناریو واقعی AI)
        $video = self::make_video_script($title, $article['html'], $provider);

        // 4. پادکست متن (برای TTS)
        $podcast_text = self::make_podcast_text($title, $article['html']);
        $podcast_audio = null;
        // TTS را فعلاً فقط اگر کاربر بخواهد می‌سازیم (هزینه) — اینجا متن آماده می‌دهیم + دکمه ساخت
        // برای نسخه واقعی، اگر auto_tts=1 باشد بساز
        if(!empty($input['auto_tts'])){
            $tts=EAIW_TTS::synthesize(mb_substr($podcast_text,0,3500));
            if(!is_wp_error($tts)) $podcast_audio=$tts;
        }

        // 5. کاروسل اینستا (5 اسلاید)
        $carousel = self::make_carousel($title, $article['html'], $provider);

        // 6. کپشن‌ها
        $tweet = self::make_tweet($title);
        $email = ['subject'=>'🔥 '.$title, 'body'=> wp_trim_words(strip_tags($article['html']), 50) . "\n\n". ($post_id?get_permalink($post_id):home_url())];
        $hashtags = self::make_hashtags($title);

        // 7. ذخیره به‌عنوان پیش‌نویس (اختیاری)
        $draft_id=0;
        if(!empty($input['save_draft'])){
            $draft_id=wp_insert_post([
                'post_type'=>'post',
                'post_title'=>$article['title'] ?: $title,
                'post_content'=>$article['html'],
                'post_status'=>'draft',
                'post_excerpt'=> wp_trim_words(strip_tags($article['html']), 30),
            ]);
            if(!is_wp_error($draft_id)){
                if(!empty($images[0]['attachment_id'])) set_post_thumbnail($draft_id, $images[0]['attachment_id']);
                update_post_meta($draft_id,'_eaiw_factory_trace',$trace);
                // تگ و دسته اگر خواست
            }
        }

        return [
            'title'=>$title,
            'article'=>$article,
            'images'=>$images,
            'video'=>$video,
            'podcast'=>['text'=>$podcast_text,'audio'=>$podcast_audio],
            'carousel'=>$carousel,
            'tweet'=>$tweet,
            'email'=>$email,
            'hashtags'=>$hashtags,
            'draft_id'=>$draft_id,
            'draft_url'=> $draft_id?get_edit_post_link($draft_id,''):'',
            'trace'=>$trace,
        ];
    }

    private static function make_article($title,$prompt,$tone,$length,$provider=''){
        $system="تو یک نویسنده حرفه‌ای فارسی هستی. خروجی فقط HTML تمیز برای وردپرس بده — بدون Markdown، بدون **، بدون توضیح اضافی. از h2 و h3 و ul و p و strong استفاده کن. مقاله باید کامل، قابل انتشار و سئو شده باشد. حداقل 5 بخش h2، حداقل 2 تا h3، یک FAQ با 3 سوال، و یک جمع‌بندی داشته باش. لحن: $tone. طول: حدود $length کلمه.";
        $user="موضوع: $title\nتوضیح: $prompt\n\nحافظه سایت (برای دقت):\n".EAIW_RAG::context_for_prompt($title,3)."\n\nحالا مقاله HTML را بساز — عنوان را h1 نکن، از h2 شروع کن.";
        $res=EAIW_AI_Client::complete($user,$system,['provider'=>$provider,'temperature'=>0.7,'max_tokens'=>6000]);
        if(is_wp_error($res)) return $res;
        // پاکسازی
        $html=self::clean_html($res);
        // Quality Gate ساده
        if(mb_strlen(strip_tags($html)) < 400) return new WP_Error('short','مقاله خیلی کوتاه شد — دوباره تلاش کن یا طول را بیشتر کن');
        return ['title'=>$title,'html'=>$html,'words'=>str_word_count(strip_tags($html))];
    }

    private static function clean_html($html){
        // حذف ```html fence
        $html=preg_replace('/```(?:html)?\s*([\s\S]*?)\s*```/i','$1',$html);
        // حذف ستاره/هشتگ Markdown اگر مانده
        $html=preg_replace('/^#{1,6}\s+/m','',$html);
        $html=str_replace(['**','__'],'',$html);
        // حذف متن گفتگویی اول
        $html=preg_replace('/^(سلام|بسیار عالی|حتماً|در ادامه).*?\n/is','',$html);
        // تبدیل markdown heading به h
        $html=preg_replace_callback('/^###\s*(.+)$/m',fn($m)=>'<h3>'.esc_html(trim($m[1])).'</h3>',$html);
        $html=preg_replace_callback('/^##\s*(.+)$/m',fn($m)=>'<h2>'.esc_html(trim($m[1])).'</h2>',$html);
        return trim($html);
    }

    private static function make_image_prompts($title,$html,$provider=''){
        $system="تو متخصص پرامپت تصویر هستی. 3 پرامپت انگلیسی کوتاه و دقیق برای تصویر article بده — هر پرامپت یک خط، بدون شماره اضافه.";
        $user="عنوان: $title\nمقاله (خلاصه): ".mb_substr(strip_tags($html),0,1200)."\n\nسه پرامپت تصویر بساز: 1- تصویر شاخص 16:9 فوتورئال 2- اینفوگرافیک 3- جزئیات. فقط JSON آرایه 3 تایی برگردان: [\"prompt1\",\"prompt2\",\"prompt3\"]";
        $res=EAIW_AI_Client::json_complete($user,$system,['provider'=>$provider,'temperature'=>0.8,'max_tokens'=>800]);
        if(!is_wp_error($res) && is_array($res) && count($res)>=2) return array_slice($res,0,3);
        // fallback
        return [
            "Featured image for $title, photorealistic, studio lighting, 16:9, high detail",
            "Infographic about $title, clean minimal, icons",
            "Close-up detail of $title, macro, bokeh"
        ];
    }

    private static function make_video_script($title,$html,$provider=''){
        $system="تو سناریست ویدیو 60 ثانیه‌ای هستی. خروجی JSON آرایه 4 صحنه برگردان.";
        $user="عنوان: $title\nخلاصه مقاله: ".mb_substr(strip_tags($html),0,1000)."\n\nسناریو 60 ثانیه بساز — JSON:\n[{\"start\":\"0:00\",\"end\":\"0:07\",\"shot\":\"کلوزآپ\",\"vo\":\"...\"}, ...] 4 صحنه: Hook, توضیح, نکته طلایی, CTA. فقط JSON.";
        $res=EAIW_AI_Client::json_complete($user,$system,['provider'=>$provider,'temperature'=>0.7,'max_tokens'=>900]);
        if(!is_wp_error($res) && is_array($res)) return $res;
        // fallback
        return [
            ['start'=>'0:00','end'=>'0:07','shot'=>'Hook — کلوزآپ','vo'=>"آیا می‌دانستید $title می‌تواند همه‌چیز را عوض کند؟"],
            ['start'=>'0:07','end'=>'0:25','shot'=>'B-Roll','vo'=> wp_trim_words(strip_tags($html),25)],
            ['start'=>'0:25','end'=>'0:45','shot'=>'نکته طلایی','vo'=>'۳ نکته که ۹۰٪ افراد نمی‌دانند...'],
            ['start'=>'0:45','end'=>'0:60','shot'=>'CTA','vo'=>'مقاله کامل را در سایت بخوان — لینک در توضیحات'],
        ];
    }

    private static function make_podcast_text($title,$html){
        $txt="سلام، اینجا پادکست اتحاد — امروز درباره «$title» صحبت می‌کنیم.\n\n";
        $txt.= wp_trim_words(strip_tags($html), 180, '') . "\n\n";
        $txt.="این بود خلاصه امروز — برای جزئیات کامل به سایت سر بزن.";
        return $txt;
    }

    private static function make_carousel($title,$html,$provider=''){
        $system="تو متخصص محتوای اینستا هستی. 5 اسلاید کاروسل فارسی بساز — هر اسلاید یک جمله کوتاه و جذاب.";
        $user="عنوان: $title\nخلاصه: ".mb_substr(strip_tags($html),0,800)."\nJSON آرایه 5 تایی: [\"اسلاید 1...\",\"اسلاید 2...\",...]";
        $res=EAIW_AI_Client::json_complete($user,$system,['provider'=>$provider,'temperature'=>0.8,'max_tokens'=>700]);
        if(!is_wp_error($res) && is_array($res) && count($res)>=3) return array_slice($res,0,5);
        return [
            "$title — چیزی که باید بدانی",
            "مشکل چیست؟",
            "راهکار ۱: ...",
            "راهکار ۲: ...",
            "برای آموزش کامل → لینک در بایو",
        ];
    }

    private static function make_tweet($title){
        $t=mb_substr($title,0,70);
        return "🔥 $t\n\n۳ نکته طلایی که ۹۰٪ نمی‌دونن 👇\n\nبخون و ذخیره کن — لینک تو بایو";
    }
    private static function make_hashtags($title){
        $words=explode(' ', $title);
        $tags=array_map(fn($w)=>'#'.preg_replace('/[^\p{L}\p{N}]/u','',$w), array_slice($words,0,3));
        $tags[]='#اتحاد_وردپرس';
        return implode(' ', $tags);
    }
}
