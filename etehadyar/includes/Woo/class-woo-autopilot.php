<?php
defined('ABSPATH') || exit;
/**
 * Woo AutoPilot 6.5.2 — بهینه و واقعی
 * - کش 5 دقیقه برای لیست ضعیف‌ها
 * - مدیریت خطا برای هاست‌های کند
 */
class EAIW_Woo_Autopilot {
    public static function enhance_one($product_id, $opts=[]){
        if(!class_exists('WooCommerce')) return new WP_Error('no_woo','ووکامرس فعال نیست');
        if(!function_exists('wc_get_product')) return new WP_Error('no_woo','ووکامرس به‌درستی بارگذاری نشده');
        // بررسی دسترسی
        $product=wc_get_product($product_id);
        if(!$product) return new WP_Error('not_found','محصول #'.$product_id.' پیدا نشد — شاید حذف شده');
        $title=$product->get_name() ?: get_the_title($product_id);
        $old_desc=$product->get_description() ?: $product->get_short_description() ?: $title;
        $price=$product->get_price() ?: 'نامشخص';

        $system="تو متخصص فروش و سئو ووکامرس هستی. خروجی فقط JSON معتبر بده — بدون توضیح اضافه.";
        $user="محصول: $title\nتوضیح فعلی: ".mb_substr($old_desc,0,800)."\nقیمت: $price\n\n".
               "یک JSON بساز با کلیدها:\n".
               "{\"short_desc\":\"توضیح کوتاه 2 جمله جذاب\",\"long_desc\":\"HTML کامل با <h2>چرا بخری</h2><ul><li>3 مزیت</li></ul><h3>مقایسه</h3><table><tr><th>ویژگی</th><th>این محصول</th><th>رقبا</th></tr><tr><td>کیفیت</td><td>★★★★★</td><td>★★★</td></tr></table><h3>مناسب برای کیست</h3><p>...</p>\",\"faq\":[{\"q\":\"سوال1\",\"a\":\"جواب1\"},{\"q\":\"سوال2\",\"a\":\"جواب2\"}],\"seo_title\":\"عنوان سئو 60 کاراکتر\",\"seo_desc\":\"متا 155 کاراکتر\",\"tags\":[\"تگ1\",\"تگ2\",\"تگ3\"],\"compare_table\":true}\n".
               "فقط JSON — بدون ```";

        $res=EAIW_AI_Client::json_complete($user,$system,['temperature'=>0.7,'max_tokens'=>2200]);
        if(is_wp_error($res)) return $res;

        // اعمال — با try
        try{
            if(!empty($res['long_desc'])){
                wp_update_post(['ID'=>$product_id,'post_content'=> wp_kses_post($res['long_desc'])]);
            }
            if(!empty($res['short_desc'])){
                wp_update_post(['ID'=>$product_id,'post_excerpt'=> sanitize_textfield($res['short_desc'])]);
            }
            if(!empty($res['seo_title'])){
                update_post_meta($product_id,'_yoast_wpseo_title', sanitize_text_field($res['seo_title']));
                update_post_meta($product_id,'_rank_math_title', sanitize_text_field($res['seo_title']));
            }
            if(!empty($res['seo_desc'])){
                update_post_meta($product_id,'_yoast_wpseo_metadesc', sanitize_text_field($res['seo_desc']));
                update_post_meta($product_id,'_rank_math_description', sanitize_text_field($res['seo_desc']));
            }
            if(!empty($res['tags']) && is_array($res['tags'])){
                wp_set_post_terms($product_id, array_map('sanitize_text_field',$res['tags']), 'product_tag', true);
            }
            if(!empty($res['faq']) && is_array($res['faq'])){
                update_post_meta($product_id,'_eaiw_faq', wp_json_encode($res['faq'], JSON_UNESCAPED_UNICODE));
                $schema=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
                foreach($res['faq'] as $f) if(isset($f['q'],$f['a'])) $schema['mainEntity'][]=['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]];
                update_post_meta($product_id,'_eaiw_faq_schema', wp_json_encode($schema, JSON_UNESCAPED_UNICODE));
            }
            update_post_meta($product_id,'_eaiw_enhanced_at', current_time('mysql'));
            update_post_meta($product_id,'_eaiw_enhanced_data', wp_json_encode($res, JSON_UNESCAPED_UNICODE));
            // پاک کردن کش
            delete_transient('eaiw_weak_cache');
        } catch(Exception $e){
            return new WP_Error('save_error','خطا در ذخیره: '.$e->getMessage());
        }

        return array_merge($res, ['product_id'=>$product_id,'edit_url'=>get_edit_post_link($product_id,''),'view_url'=>get_permalink($product_id)]);
    }

    public static function create_product($prompt, $opts=[]){
        if(!class_exists('WooCommerce')) return new WP_Error('no_woo','ووکامرس فعال نیست');
        if(!$prompt) return new WP_Error('empty','موضوع محصول را بنویس');
        $system="تو متخصص ساخت محصول ووکامرس هستی. JSON محصول کامل بساز.";
        $user="ایده محصول: $prompt\n\n".
              "JSON بساز:\n{\"title\":\"نام محصول جذاب\",\"short_desc\":\"...\",\"long_desc\":\"HTML کامل ...\",\"price\":\"990000\",\"regular_price\":\"1290000\",\"sku\":\"...\",\"tags\":[],\"faq\":[]}\nفقط JSON.";
        $res=EAIW_AI_Client::json_complete($user,$system,['temperature'=>0.8,'max_tokens'=>2000]);
        if(is_wp_error($res)) return $res;
        $title=$res['title'] ?? $prompt;
        $price=preg_replace('/[^\d]/','',$res['price'] ?? '990000');
        $regular=preg_replace('/[^\d]/','',$res['regular_price'] ?? $price);
        if(!$price) $price='990000';

        $id=wp_insert_post([
            'post_type'=>'product',
            'post_title'=>$title,
            'post_content'=> wp_kses_post($res['long_desc'] ?? ''),
            'post_excerpt'=> sanitize_textfield($res['short_desc'] ?? ''),
            'post_status'=>'draft',
        ]);
        if(is_wp_error($id)) return $id;
        wp_set_object_terms($id,'simple','product_type');
        update_post_meta($id,'_price',$price);
        update_post_meta($id,'_regular_price',$regular);
        update_post_meta($id,'_sku', sanitize_text_field($res['sku'] ?? 'EAIW-'.wp_rand(1000,9999)));
        update_post_meta($id,'_visibility','visible');
        update_post_meta($id,'_stock_status','instock');
        update_post_meta($id,'_manage_stock','no');
        if(!empty($res['tags']) && is_array($res['tags'])) wp_set_post_terms($id,array_map('sanitize_text_field',$res['tags']),'product_tag',true);
        if(!empty($res['faq']) && is_array($res['faq'])) update_post_meta($id,'_eaiw_faq', wp_json_encode($res['faq'], JSON_UNESCAPED_UNICODE));
        // تصویر
        if(!empty($opts['make_image'])){
            // نذار خطای عکس کل محصول را خراب کند
            $img=EAIW_Flux_Client::generate("Product photo of $title, studio lighting, white background, 1:1", 'photorealistic','1024x1024');
            if(!is_wp_error($img) && !empty($img['attachment_id'])) set_post_thumbnail($id,$img['attachment_id']);
        }
        delete_transient('eaiw_weak_cache');
        return ['product_id'=>$id,'edit_url'=>get_edit_post_link($id,''),'view_url'=>get_permalink($id),'title'=>$title];
    }

    public static function bulk_enhance($ids){
        if(!class_exists('WooCommerce')) return new WP_Error('no_woo','ووکامرس فعال نیست');
        $out=[];
        foreach(array_slice(array_map('intval',$ids),0,6) as $id){
            $r=self::enhance_one($id);
            $out[]=['id'=>$id,'ok'=>!is_wp_error($r),'error'=> is_wp_error($r)?$r->get_error_message():'', 'title'=> get_the_title($id)];
            usleep(300000);
        }
        delete_transient('eaiw_weak_cache');
        return $out;
    }

    // اسکن محصولات ضعیف — با کش 5 دقیقه و کوئری سبک
    public static function find_weak($limit=6){
        if(!class_exists('WooCommerce') || !function_exists('wc_get_products')) return [];
        // کش
        $key='eaiw_weak_cache_'.$limit;
        $cached=get_transient($key);
        if($cached!==false) return $cached;

        try{
            // فقط ID بگیر — سبک‌تر
            $ids=wc_get_products(['limit'=>$limit*2,'status'=>'publish','orderby'=>'modified','order'=>'ASC','return'=>'ids']);
            if(empty($ids)){
                // fallback WP_Query
                $q=new WP_Query(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>$limit*2,'orderby'=>'modified','order'=>'ASC','fields'=>'ids','no_found_rows'=>true]);
                $ids=$q->posts;
            }
            $weak=[];
            foreach(array_slice($ids,0,$limit*2) as $pid){
                if(count($weak)>=$limit) break;
                $post=get_post($pid);
                if(!$post) continue;
                $desc=$post->post_content;
                $words=str_word_count(strip_tags($desc ?: $post->post_excerpt));
                $has_thumb=has_post_thumbnail($pid);
                $has_faq=get_post_meta($pid,'_eaiw_faq',true);
                $price=get_post_meta($pid,'_price',true);
                $score=0;
                if($words<250) $score+=40;
                elseif($words<400) $score+=20;
                if(!$has_thumb) $score+=30;
                if(!$has_faq) $score+=15;
                if(!$price) $score+=10;
                if($score>25) $weak[]=['id'=>$pid,'title'=>get_the_title($pid)?:'محصول #'.$pid,'words'=>$words,'thumb'=>$has_thumb?'دارد':'ندارد','score'=>$score,'price'=>$price?:'—','url'=>get_edit_post_link($pid,'')];
            }
            // اگر هیچ ضعیفی نبود ولی محصول داریم، کمترین کلمات را برگردان
            if(empty($weak) && !empty($ids)){
                foreach(array_slice($ids,0,2) as $pid){
                    $weak[]=['id'=>$pid,'title'=>get_the_title($pid),'words'=>str_word_count(strip_tags(get_post_field('post_content',$pid))), 'thumb'=>has_post_thumbnail($pid)?'دارد':'ندارد','score'=>20,'price'=>get_post_meta($pid,'_price',true)?:'—','url'=>get_edit_post_link($pid,'')];
                }
            }
            set_transient($key,$weak,5*MINUTE_IN_SECONDS);
            return $weak;
        } catch(Exception $e){
            error_log('[EAIW] find_weak error: '.$e->getMessage());
            return [];
        }
    }

    // آمار واقعی ووکامرس برای داشبورد
    public static function stats_real(){
        if(!class_exists('WooCommerce')) return null;
        try{
            $today_orders=wc_get_orders(['date_created'=>'>'.date('Y-m-d 00:00:00'), 'limit'=>-1, 'return'=>'ids']);
            $total_orders=wp_count_posts('shop_order');
            $total_products=wp_count_posts('product');
            $revenue=0;
            // جمع درآمد 7 روز اخیر — سبک: فقط 20 سفارش آخر
            $orders=wc_get_orders(['limit'=>20,'orderby'=>'date','order'=>'DESC','return'=>'ids']);
            foreach($orders as $oid){
                $o=wc_get_order($oid);
                if($o && $o->get_status()!=='cancelled') $revenue+= (float)$o->get_total();
            }
            return [
                'today_orders'=> is_array($today_orders)?count($today_orders):0,
                'total_orders'=> $total_orders->wc_processing ?? 0 + ($total_orders->wc_completed ?? 0),
                'total_products'=> $total_products->publish ?? 0,
                'revenue_sample'=> $revenue,
            ];
        } catch(Exception $e){ return null; }
    }
}
