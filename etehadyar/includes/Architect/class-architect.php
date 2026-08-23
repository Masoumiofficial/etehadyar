<?php
defined('ABSPATH') || exit;
class EAIW_Architect {
    public static function generate($brief){
        if (!$brief) return new WP_Error('empty','خلاصه صفحه خالی است');
        // 3 سکشن ماورایی — سازگار با Gutenberg + Elementor
        $sections = [
            [
                'type'=>'hero',
                'title'=> 'همان چیزی که دنبالش بودی — همین‌جاست',
                'subtitle'=> $brief,
                'cta'=> 'همین حالا شروع کن',
                'style'=> 'nebula',
            ],
            [
                'type'=>'features',
                'title'=> 'چرا اتحاد؟',
                'items'=> [
                    ['icon'=>'⚡','title'=>'سرعت ماورایی','desc'=>'ساخت در ۳۰ ثانیه، نه ۳ روز'],
                    ['icon'=>'🧠','title'=>'هوش واقعی','desc'=>'مغز سایتت را می‌شناسد'],
                    ['icon'=>'🛡️','title'=>'امن و سئو','desc'=>'اسکیما، سرعت و امنیت خودکار'],
                ]
            ],
            [
                'type'=>'cta',
                'title'=> 'آماده‌ای امپراتوری بسازی؟',
                'cta'=> 'ساخت صفحه ماورایی',
            ]
        ];
        // خروجی HTML Gutenberg
        $html = self::to_gutenberg($sections);
        // ذخیره به‌عنوان پیش‌نویس برگه
        $post_id = wp_insert_post([
            'post_type'=>'page',
            'post_title'=> wp_trim_words($brief, 8),
            'post_content'=> $html,
            'post_status'=>'draft',
        ]);
        if (is_wp_error($post_id)) return $post_id;
        update_post_meta($post_id, '_eaiw_architect_brief', $brief);
        update_post_meta($post_id, '_eaiw_architect_sections', wp_json_encode($sections, JSON_UNESCAPED_UNICODE));
        return ['post_id'=>$post_id,'edit_url'=>get_edit_post_link($post_id,''),'preview_url'=>get_preview_post_link($post_id),'html'=>$html,'sections'=>$sections];
    }
    private static function to_gutenberg($sections){
        $html='';
        foreach($sections as $s){
            if ($s['type']==='hero'){
                $html .= '<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"backgroundColor":"contrast","textColor":"base","className":"eaiw-hero-nebula"} --><div class="wp-block-group eaiw-hero-nebula has-base-color has-contrast-background-color has-text-color has-background" style="padding-top:60px;padding-bottom:60px;text-align:center;border-radius:24px;background:linear-gradient(135deg,#1a1033,#0f172a);border:1px solid #6d28ff44">'.
                '<!-- wp:heading {"textAlign":"center","level":1} --><h1 class="has-text-align-center">'.esc_html($s['title']).'</h1><!-- /wp:heading -->'.
                '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">'.esc_html($s['subtitle']).'</p><!-- /wp:paragraph -->'.
                '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"vivid-purple","style":{"border":{"radius":"999px"}}} --><div class="wp-block-button"><a class="wp-block-button__link has-vivid-purple-background-color has-background wp-element-button" style="border-radius:999px">'.esc_html($s['cta']).'</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'.
                '</div><!-- /wp:group -->';
            } elseif($s['type']==='features'){
                $html .= '<!-- wp:columns --> <div class="wp-block-columns">';
                foreach($s['items'] as $it){
                    $html .= '<!-- wp:column --><div class="wp-block-column" style="text-align:center;padding:18px;border:1px solid #e2e8f0;border-radius:16px">'.
                    '<!-- wp:paragraph {"align":"center","fontSize":"large"} --><p class="has-text-align-center has-large-font-size">'.esc_html($it['icon']).'</p><!-- /wp:paragraph -->'.
                    '<!-- wp:heading {"textAlign":"center","level":3} --><h3 class="has-text-align-center">'.esc_html($it['title']).'</h3><!-- /wp:heading -->'.
                    '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">'.esc_html($it['desc']).'</p><!-- /wp:paragraph --></div><!-- /wp:column -->';
                }
                $html .= '</div><!-- /wp:columns -->';
            } elseif($s['type']==='cta'){
                $html .= '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px"}}},"backgroundColor":"primary","textColor":"base","className":"eaiw-cta"} --><div class="wp-block-group eaiw-cta has-base-color has-primary-background-color has-text-color has-background" style="border-radius:18px;text-align:center;padding-top:40px;padding-bottom:40px"><h2>'.esc_html($s['title']).'</h2><div class="wp-block-buttons" style="justify-content:center"><div class="wp-block-button"><a class="wp-block-button__link" style="border-radius:999px;background:white;color:#6d28ff">'.esc_html($s['cta']).'</a></div></div></div><!-- /wp:group -->';
            }
        }
        return $html;
    }
}
