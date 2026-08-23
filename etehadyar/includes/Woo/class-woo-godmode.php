<?php
defined('ABSPATH') || exit;
class EAIW_Woo_GodMode {
    public static function enhance_product($product_id){
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('not_found','محصول یافت نشد');
        $title = $product->get_name();
        $desc = $product->get_description() ?: $product->get_short_description();
        // تولید توضیح متقاعدکننده + FAQ + جدول
        $enhanced = [
            'long_desc' => "<h2>$title — انتخاب حرفه‌ای‌ها</h2><p>$desc</p><h3>چرا همین را بخری؟</h3><ul><li>کیفیت ماورایی با ضمانت اتحاد</li><li>ارسال فوری + پشتیبانی واقعی</li><li>بهترین قیمت ۱۴۰۴</li></ul><h3>مقایسه</h3><table><tr><th>ویژگی</th><th>این محصول</th><th>رقبا</th></tr><tr><td>کیفیت</td><td>★★★★★</td><td>★★★</td></tr></table>",
            'faq' => [
                ['q'=>"آیا $title گارانتی دارد؟",'a'=>'بله، ۱۸ ماه گارانتی اتحاد + ۷ روز مرجوعی'],
                ['q'=>'ارسال چقدر طول می‌کشد؟','a'=>'تهران ۲۴ساعته، شهرستان ۴۸ ساعته'],
            ],
            'seo_title' => "$title | خرید با بهترین قیمت ۱۴۰۴ + ارسال فوری",
            'seo_desc' => "خرید $title با ضمانت اصل بودن، قیمت رقابتی و ارسال فوری از اتحاد. همین حالا سفارش بده.",
        ];
        // ذخیره در متا — نمایش در فرانت با فیلتر
        update_post_meta($product_id, '_eaiw_godmode_enhanced', wp_json_encode($enhanced, JSON_UNESCAPED_UNICODE));
        return $enhanced;
    }
}
