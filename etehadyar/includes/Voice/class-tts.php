<?php
defined('ABSPATH') || exit;
/**
 * TTS — تبدیل متن به صدا (پادکست)
 * از OpenAI TTS یا GapGPT — خروجی MP3 در uploads
 */
class EAIW_TTS {
    public static function synthesize($text, $voice='alloy'){
        $text=trim($text);
        if(!$text) return new WP_Error('empty','متنی برای تبدیل نیست');
        if(mb_strlen($text)>3800) $text=mb_substr($text,0,3800);
        $key=EAIW_Vault::get_key('openai') ?: EAIW_Vault::get_key('gapgpt');
        if(!$key) return new WP_Error('no_key','کلید OpenAI/GapGPT برای صدا لازم است — در تنظیمات وارد کن');
        $is_gapgpt = EAIW_Vault::get_key('gapgpt')=== $key && !EAIW_Vault::get_key('openai');
        $endpoint = $is_gapgpt ? 'https://api.gapgpt.app/api/v1/audio/speech' : 'https://api.openai.com/v1/audio/speech';
        $resp=wp_remote_post($endpoint, [
            'headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'],
            'body'=>wp_json_encode(['model'=>'tts-1','input'=>$text,'voice'=>$voice,'response_format'=>'mp3'], JSON_UNESCAPED_UNICODE),
            'timeout'=>60,
        ]);
        if(is_wp_error($resp)) return $resp;
        $code=wp_remote_retrieve_response_code($resp);
        if($code!==200){
            $data=json_decode(wp_remote_retrieve_body($resp), true);
            return new WP_Error('tts_error', $data['error']['message'] ?? "خطای $code از TTS");
        }
        $body=wp_remote_retrieve_body($resp);
        $filename='eaiw-podcast-'.time().'.mp3';
        $upload=wp_upload_bits($filename,null,$body);
        if($upload['error']) return new WP_Error('upload',$upload['error']);
        // ذخیره به‌عنوان attachment صوتی
        $att=['post_mime_type'=>'audio/mpeg','post_title'=>'پادکست: '.mb_substr($text,0,40),'post_status'=>'inherit'];
        $id=wp_insert_attachment($att,$upload['file']);
        require_once ABSPATH.'wp-admin/includes/image.php';
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$upload['file']));
        return ['attachment_id'=>$id,'url'=>$upload['url'],'file'=>$upload['file']];
    }
}
