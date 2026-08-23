<?php
defined('ABSPATH') || exit;
/**
 * Flux / Stability / OpenAI Images — کلاینت تصویر واقعی 6.1
 * اولویت: 1) Flux (fal/Replicate) 2) Stability 3) OpenAI
 */
class EAIW_Flux_Client {
    public static function generate($prompt, $style='photorealistic', $size='1024x1024'){
        $prompt = trim($prompt);
        if(!$prompt) return new WP_Error('empty','توضیح تصویر خالی است');
        // 1. Flux via FAL (اگر کلید flux وارد شده)
        $flux_key = EAIW_Vault::get_key('flux');
        if($flux_key){
            $r=self::flux_fal($prompt,$flux_key,$style,$size);
            if(!is_wp_error($r)) return $r;
        }
        // 2. Stability
        $stability = EAIW_Vault::get_key('stability');
        if($stability){
            $r=self::stability($prompt,$stability,$style,$size);
            if(!is_wp_error($r)) return $r;
        }
        // 3. OpenAI fallback (از کلاس قدیمی)
        return EAIW_Vision_Studio::generate($prompt,$style,$size);
    }

    private static function flux_fal($prompt, $key, $style, $size){
        // FAL Flux Schnell/Pro — https://fal.ai/models/fal-ai/flux/schnell
        $style_map=['photorealistic'=>'photorealistic, ultra detailed','minimal'=>'minimal, flat','3d'=>'3d render','illustration'=>'illustration'];
        $full = $prompt . ', ' . ($style_map[$style]??'');
        $endpoint='https://queue.fal.run/fal-ai/flux/schnell';
        $resp=wp_remote_post($endpoint, [
            'headers'=>['Authorization'=>'Key '.$key,'Content-Type'=>'application/json'],
            'body'=> wp_json_encode(['prompt'=>$full,'image_size'=> $size==='1792x1024'?'landscape_16_9':($size==='1024x1024'?'square_hd':'portrait_16_9'),'num_images'=>1], JSON_UNESCAPED_UNICODE),
            'timeout'=>60,
        ]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        $data=json_decode(wp_remote_retrieve_body($resp), true);
        if($code!==200) return new WP_Error('flux_error', $data['detail']??"خطای $code از Flux");
        $url=$data['images'][0]['url'] ?? '';
        if(!$url) return new WP_Error('no_url','Flux تصویری برنگرداند');
        $att=self::download($url,$prompt);
        if(is_wp_error($att)) return $att;
        return ['mode'=>'flux','attachment_id'=>$att,'url'=>wp_get_attachment_url($att),'prompt'=>$prompt,'note'=>'ساخته شده با Flux Schnell — واقعی و باکیفیت ⚡'];
    }

    private static function stability($prompt, $key, $style, $size){
        // Stability SD3
        $endpoint='https://api.stability.ai/v2beta/stable-image/generate/sd3';
        $resp=wp_remote_post($endpoint, [
            'headers'=>['Authorization'=>'Bearer '.$key,'Accept'=>'image/*'],
            'body'=>['prompt'=>$prompt,'output_format'=>'png','aspect_ratio'=> $size==='1792x1024'?'16:9':($size==='1024x1024'?'1:1':'9:16'),'model'=>'sd3-large'],
            'timeout'=>60,
        ]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        if($code!==200){
            $data=json_decode(wp_remote_retrieve_body($resp), true);
            return new WP_Error('stability_error', $data['message']??"خطای $code از Stability");
        }
        $body=wp_remote_retrieve_body($resp);
        // باینری PNG برگشته — ذخیره
        $filename='eaiw-flux-'.sanitize_title(mb_substr($prompt,0,20)).'-'.time().'.png';
        $upload=wp_upload_bits($filename,null,$body);
        if($upload['error']) return new WP_Error('upload',$upload['error']);
        $att=['post_mime_type'=>'image/png','post_title'=>'تصویر: '.mb_substr($prompt,0,60),'post_status'=>'inherit'];
        $id=wp_insert_attachment($att,$upload['file']);
        require_once ABSPATH.'wp-admin/includes/image.php';
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$upload['file']));
        update_post_meta($id,'_wp_attachment_image_alt',$prompt);
        return ['mode'=>'stability','attachment_id'=>$id,'url'=>wp_get_attachment_url($id),'prompt'=>$prompt,'note'=>'ساخته شده با Stability SD3 — واقعی ✨'];
    }

    private static function download($url,$prompt){
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        $tmp=download_url($url,60);
        if(is_wp_error($tmp)) return $tmp;
        $file=['name'=>sanitize_title(mb_substr($prompt,0,25)).'-'.time().'.png','tmp_name'=>$tmp];
        $id=media_handle_sideload($file,0,'تصویر: '.mb_substr($prompt,0,60));
        if(is_wp_error($id)) @unlink($tmp);
        else update_post_meta($id,'_wp_attachment_image_alt',$prompt);
        return $id;
    }
}
