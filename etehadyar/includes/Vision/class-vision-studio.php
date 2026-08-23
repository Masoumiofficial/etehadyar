<?php
defined('ABSPATH') || exit;
class EAIW_Vision_Studio {
    /**
     * تولید تصویر واقعی — حالا کاملاً قابل استفاده است
     * اگر کلید OpenAI/GapGPT باشد → تصویر واقعی AI
     * اگر نباشد → تصویر SVG باکیفیت + راهنمای اتصال
     */
    public static function generate($prompt, $style='photorealistic', $size='1280x720'){
        $prompt = trim($prompt);
        if (!$prompt) return new WP_Error('empty','لطفاً توضیح تصویر را بنویسید — مثلاً: «تصویر شاخص برای مقاله قهوه اسپرسو»');
        if (mb_strlen($prompt) < 8) return new WP_Error('short','توضیح خیلی کوتاه است — کمی بیشتر توضیح بده');

        // استایل را به prompt اضافه کن
        $style_suffix = [
            'photorealistic' => ', photorealistic, ultra detailed, 8k, studio lighting',
            'minimal' => ', minimal, flat design, clean background, vector style',
            '3d' => ', 3D render, isometric, highly detailed',
            'illustration' => ', illustration, watercolor, artistic',
        ][$style] ?? '';

        $full_prompt = $prompt . $style_suffix;
        // فارسی → انگلیسی بهتر برای مدل‌ها — اما فارسی هم کار می‌کند، ما نگه می‌داریم

        $openai = EAIW_Vault::get_key('openai');
        $gapgpt = EAIW_Vault::get_key('gapgpt');
        $key = $openai ?: $gapgpt;
        $is_gapgpt = !$openai && $gapgpt;

        // اگر کلید هست → تلاش واقعی
        if ($key) {
            $result = self::call_openai_image($full_prompt, $key, $is_gapgpt, $size);
            if (!is_wp_error($result)) return $result;
            // اگر خطا داد → هم خطا را بگو هم fallback
            $err = $result->get_error_message();
            // fallback SVG اما با پیام خطای واقعی
            $svg = self::make_svg_placeholder($prompt, $style, "خطا در اتصال: $err — کلید را بررسی کن");
            $att_id = self::save_svg_to_media($svg, $prompt);
            return [
                'mode'=>'error_fallback',
                'attachment_id'=>$att_id,
                'url'=> wp_get_attachment_url($att_id),
                'prompt'=>$prompt,
                'style'=>$style,
                'error'=> $err,
                'note'=>'اتصال به سرویس تصویر ناموفق بود — یک تصویر موقت ساخته شد. پیام خطا: '.$err
            ];
        }

        // بدون کلید → SVG باکیفیت + راهنما (قابل استفاده واقعی)
        $svg = self::make_svg_placeholder($prompt, $style);
        $att_id = self::save_svg_to_media($svg, $prompt);
        return [
            'mode'=>'placeholder',
            'attachment_id'=>$att_id,
            'url'=> wp_get_attachment_url($att_id),
            'prompt'=>$prompt,
            'style'=>$style,
            'note'=>'کلید تصویر هنوز وارد نشده — این یک تصویر موقت باکیفیت است که همین حالا در کتابخانه ذخیره شد و قابل استفاده است. برای تصویر واقعی AI، کلید OpenAI یا GapGPT را در تنظیمات → کلیدها وارد کن.'
        ];
    }

    private static function call_openai_image($prompt, $key, $is_gapgpt=false, $size='1280x720'){
        $endpoint = $is_gapgpt ? 'https://api.gapgpt.app/api/v1/images/generations' : 'https://api.openai.com/v1/images/generations';
        // سایز OpenAI: 1024x1024, 1792x1024, 1024x1792
        $oai_size = '1024x1024';
        if ($size==='1280x720' || $size==='1792x1024') $oai_size='1792x1024';
        if ($size==='720x1280') $oai_size='1024x1792';

        $body = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $oai_size,
            'quality' => 'standard',
        ];
        $resp = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer '.$key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body, JSON_UNESCAPED_UNICODE),
            'timeout' => 45,
        ]);
        if (is_wp_error($resp)) return $resp;
        $code = wp_remote_retrieve_response_code($resp);
        $data = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code !== 200) {
            $msg = $data['error']['message'] ?? "خطای $code از سرویس تصویر";
            return new WP_Error('api_error', $msg);
        }
        $url = $data['data'][0]['url'] ?? '';
        if (!$url) return new WP_Error('no_url','سرویس تصویری URL برنگرداند');
        // دانلود و ذخیره در مدیا
        $att_id = self::download_to_media($url, $prompt);
        if (is_wp_error($att_id)) return $att_id;
        return [
            'mode'=>'openai',
            'attachment_id'=>$att_id,
            'url'=> wp_get_attachment_url($att_id),
            'prompt'=>$prompt,
            'style'=>'photorealistic',
            'note'=>'تصویر واقعی با هوش مصنوعی ساخته و در کتابخانه ذخیره شد ✨'
        ];
    }

    private static function download_to_media($url, $prompt){
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        $tmp = download_url($url, 40);
        if (is_wp_error($tmp)) return $tmp;
        $filename = sanitize_title(mb_substr($prompt,0,30)).'-'.time().'.png';
        $file = ['name'=>$filename, 'tmp_name'=>$tmp];
        $att_id = media_handle_sideload($file, 0, 'تصویر: '.mb_substr($prompt,0,60));
        if (is_wp_error($att_id)) @unlink($tmp);
        else {
            update_post_meta($att_id, '_wp_attachment_image_alt', $prompt);
            update_post_meta($att_id, '_eaiw_generated', 1);
        }
        return $att_id;
    }

    private static function make_svg_placeholder($prompt,$style,$subtitle=''){
        $bg = $style==='minimal' ? '#0f172a' : '#1a1033';
        $title = esc_html(mb_substr($prompt,0,46));
        $sub = $subtitle ? esc_html($subtitle) : 'تصویر موقت باکیفیت — قابل استفاده در سایت';
        $style_label = esc_html($style);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720" viewBox="0 0 1280 720">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6d28ff"/><stop offset="100%" stop-color="#00e5ff"/></linearGradient><filter id="sh"><feDropShadow dx="0" dy="8" stdDeviation="12" flood-opacity="0.35"/></filter></defs>
<rect width="1280" height="720" rx="24" fill="{$bg}"/>
<rect x="36" y="36" width="1208" height="648" rx="18" fill="none" stroke="url(#g)" stroke-width="2.5" stroke-dasharray="12 10" opacity="0.7"/>
<circle cx="640" cy="250" r="88" fill="none" stroke="url(#g)" stroke-width="3.5" opacity="0.95" filter="url(#sh)"/>
<circle cx="640" cy="250" r="44" fill="url(#g)" opacity="0.95"/>
<text x="640" y="380" text-anchor="middle" font-family="Vazirmatn, Tahoma" font-size="30" font-weight="800" fill="white">{$title}</text>
<text x="640" y="416" text-anchor="middle" font-family="Tahoma" font-size="13" fill="#C2C8E6" letter-spacing="1.2">ETE HAD WP • تصویرساز هوشمند • {$style_label}</text>
<text x="640" y="456" text-anchor="middle" font-family="Tahoma" font-size="12.5" fill="#A78BFA">{$sub}</text>
</svg>
SVG;
    }
    private static function save_svg_to_media($svg,$prompt){
        $filename = 'eaiw-vision-'.sanitize_title(mb_substr($prompt,0,30)).'-'.time().'.svg';
        $upload = wp_upload_bits($filename, null, $svg);
        if ($upload['error']) return 0;
        $att = [
            'post_mime_type'=>'image/svg+xml',
            'post_title'=> 'تصویر: '.mb_substr($prompt,0,60),
            'post_content'=>'',
            'post_status'=>'inherit',
        ];
        $att_id = wp_insert_attachment($att, $upload['file']);
        if (!is_wp_error($att_id)) {
            require_once ABSPATH.'wp-admin/includes/image.php';
            wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $upload['file']));
            update_post_meta($att_id, '_wp_attachment_image_alt', $prompt);
            update_post_meta($att_id, '_eaiw_generated', 1);
        }
        return $att_id;
    }
}
