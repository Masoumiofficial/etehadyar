<?php
defined('ABSPATH') || exit;
/**
 * Instagram (Facebook Graph) — انتشار کاروسل (واقعی اگر توکن باشد)
 * اگر توکن نباشد → بسته دانلودی ZIP
 */
class EAIW_Instagram {
    public static function publish_carousel($images, $caption){
        $token=get_option('eaiw_instagram_token','');
        $ig_id=get_option('eaiw_instagram_user','');
        if(!$token || !$ig_id){
            // fallback: ZIP دانلودی
            return self::make_zip($images,$caption);
        }
        // واقعی — مرحله 1: ساخت containerها
        $containers=[];
        foreach(array_slice($images,0,10) as $url){
            $resp=wp_remote_post("https://graph.facebook.com/v20.0/{$ig_id}/media", [
                'body'=>['image_url'=>$url,'is_carousel_item'=>'true','access_token'=>$token],
                'timeout'=>20,
            ]);
            if(is_wp_error($resp)) return $resp;
            $data=json_decode(wp_remote_retrieve_body($resp), true);
            if(empty($data['id'])) return new WP_Error('ig_error', $data['error']['message'] ?? 'خطای اینستا');
            $containers[]=$data['id'];
        }
        $resp=wp_remote_post("https://graph.facebook.com/v20.0/{$ig_id}/media", [
            'body'=>['media_type'=>'CAROUSEL','children'=>implode(',',$containers),'caption'=>$caption,'access_token'=>$token],
            'timeout'=>20,
        ]);
        if(is_wp_error($resp)) return $resp;
        $data=json_decode(wp_remote_retrieve_body($resp), true);
        if(empty($data['id'])) return new WP_Error('ig_error', $data['error']['message'] ?? 'خطای کاروسل');
        // انتشار
        $pub=wp_remote_post("https://graph.facebook.com/v20.0/{$ig_id}/media_publish", [
            'body'=>['creation_id'=>$data['id'],'access_token'=>$token],
            'timeout'=>20,
        ]);
        if(is_wp_error($pub)) return $pub;
        $pd=json_decode(wp_remote_retrieve_body($pub), true);
        return ['ok'=>true,'id'=>$pd['id'] ?? $data['id']];
    }

    private static function make_zip($images,$caption){
        if(!class_exists('ZipArchive')) return new WP_Error('no_zip','ZipArchive در هاست فعال نیست');
        $upload=wp_upload_dir();
        $zipname='eaiw-instagram-'.time().'.zip';
        $zippath=$upload['path'].'/'.$zipname;
        $zip=new ZipArchive();
        if($zip->open($zippath, ZipArchive::CREATE)!==true) return new WP_Error('zip','ساخت ZIP ناموفق');
        $zip->addFromString('caption.txt', $caption);
        $zip->addFromString('README.txt', "این بسته شامل عکس‌ها + کپشن اینستا است — هر عکس را به ترتیب به‌عنوان کاروسل آپلود کن\nکپشن در caption.txt");
        foreach($images as $i=>$url){
            $tmp=download_url($url,20);
            if(!is_wp_error($tmp)){
                $zip->addFile($tmp, 'slide-'.($i+1).'.jpg');
            }
        }
        $zip->close();
        $url=$upload['url'].'/'.$zipname;
        return ['mode'=>'zip','url'=>$url,'path'=>$zippath,'note'=>'توکن اینستا تنظیم نشده — بسته کاروسل (ZIP) آماده دانلود شد. عکس‌ها + کپشن داخل ZIP است، مستقیم در اینستا آپلود کن.'];
    }
}
