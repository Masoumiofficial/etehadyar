<?php
defined('ABSPATH') || exit;
/**
 * Video Studio Pro 6.2 — ویدیو ساز واقعی
 * ورودی: سناریو + تصاویر + صدا → خروجی: MP4 واقعی (اگر FFmpeg باشد) + پیش‌نمایش HTML + SRT + JSON برای سرویس خارجی
 */
class EAIW_Video_Studio_Pro {
    public static function build($input){
        // $input: ['title'=>string, 'script'=>array, 'images'=>array(url), 'audio_url'=>string, 'duration'=>60]
        $title = sanitize_text_field($input['title'] ?? 'ویدیو اتحاد');
        $script = $input['script'] ?? [];
        $images = $input['images'] ?? [];
        $audio_url = $input['audio_url'] ?? '';
        $duration = intval($input['duration'] ?? 60);

        if(empty($script) || empty($images)) return new WP_Error('empty','سناریو یا تصویر کافی نیست');

        $trace = EAIW_Logger::log('Video Pro build', ['title'=>$title, 'scenes'=>count($script)]);

        // 1. دانلود تصاویر به temp
        $tmp_dir = self::tmp_dir();
        $local_images=[];
        foreach(array_slice($images,0,4) as $i=>$url){
            $tmp = download_url($url, 25);
            if(!is_wp_error($tmp)){
                $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $new = $tmp_dir . "/scene-".($i+1).".".$ext;
                rename($tmp, $new);
                $local_images[]=$new;
            }
        }
        if(empty($local_images)) return new WP_Error('dl','دانلود تصاویر ناموفق');

        // 2. دانلود صدا اگر URL داده
        $local_audio='';
        if($audio_url){
            $tmp = download_url($audio_url, 25);
            if(!is_wp_error($tmp)){
                $new = $tmp_dir . "/voice.mp3";
                rename($tmp,$new);
                $local_audio=$new;
            }
        }

        // 3. ساخت SRT کپشن از VO
        $srt_path = $tmp_dir . "/captions.srt";
        self::make_srt($script, $srt_path);

        // 4. JSON تایملاین (برای Creatomate / Shotstack / Premiere)
        $timeline = self::make_timeline_json($title, $script, $images, $audio_url);
        $json_path = $tmp_dir . "/timeline.json";
        file_put_contents($json_path, wp_json_encode($timeline, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

        // 5. تلاش FFmpeg واقعی
        $video_url='';
        $video_path='';
        $ffmpeg = self::ffmpeg_available();
        if($ffmpeg){
            $video_path = $tmp_dir . "/video-".time().".mp4";
            $ok = self::ffmpeg_slideshow($local_images, $local_audio, $srt_path, $video_path, $duration);
            if($ok && file_exists($video_path)){
                // انتقال به uploads
                $upload = wp_upload_dir();
                $dest = $upload['path'] . "/eaiw-video-".time().".mp4";
                rename($video_path, $dest);
                $video_url = $upload['url'] . "/" . basename($dest);
                // ثبت به‌عنوان attachment
                $att=['post_mime_type'=>'video/mp4','post_title'=>$title,'post_status'=>'inherit'];
                $id=wp_insert_attachment($att,$dest);
                require_once ABSPATH.'wp-admin/includes/image.php';
                wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$dest));
                $video_url = wp_get_attachment_url($id);
                $video_path=$dest;
            }
        }

        // 6. پیش‌نمایش HTML (همیشه کار می‌کند — حتی بدون FFmpeg)
        $preview_html = self::make_preview_html($title, $script, $images, $audio_url);
        $preview_path = $tmp_dir . "/preview.html";
        file_put_contents($preview_path, $preview_html);
        $upload = wp_upload_dir();
        $preview_dest = $upload['path'] . "/eaiw-preview-".time().".html";
        rename($preview_path, $preview_dest);
        $preview_url = $upload['url'] . "/" . basename($preview_dest);

        // 7. ZIP بسته کامل
        $zip_url = self::make_zip($title, $local_images, $local_audio, $srt_path, $json_path, $preview_html, $script);

        return [
            'title'=>$title,
            'duration'=>$duration,
            'ffmpeg'=>$ffmpeg,
            'video_url'=>$video_url,
            'video_path'=>$video_path,
            'preview_url'=>$preview_url,
            'srt_url'=> str_replace($upload['basedir'],$upload['baseurl'],$srt_path) . (file_exists($srt_path)?'':''),
            'json_url'=> str_replace($upload['basedir'],$upload['baseurl'],$json_path) . (file_exists($json_path)?'':''),
            'zip_url'=>$zip_url,
            'timeline'=>$timeline,
            'trace'=>$trace,
            'note'=> $video_url ? 'ویدیو MP4 واقعی با FFmpeg ساخته شد — آماده دانلود و انتشار' : 'پیش‌نمایش HTML + ZIP کامل ساخته شد — برای MP4 واقعی، FFmpeg را روی هاست فعال کن یا JSON را به Creatomate بده (راهنما داخل ZIP)',
        ];
    }

    private static function tmp_dir(){
        $upload=wp_upload_dir();
        $dir=$upload['basedir']."/eaiw-video-tmp-".time()."-".wp_rand(1000,9999);
        wp_mkdir_p($dir);
        return $dir;
    }

    private static function ffmpeg_available(){
        $out=[]; $ret=0;
        @exec('ffmpeg -version 2>&1', $out, $ret);
        return $ret===0;
    }

    private static function ffmpeg_slideshow($images, $audio, $srt, $out, $total_duration){
        // ساده: هر عکس ~ total/4 ثانیه، با crossfade
        $count=count($images);
        if($count<1) return false;
        $dur_per = max(3, floor($total_duration / $count));
        // ساخت فایل concat
        $list = dirname($out)."/list.txt";
        $txt='';
        foreach($images as $img){
            $txt.="file '".str_replace("'", "'\\''", $img)."'\n";
            $txt.="duration $dur_per\n";
        }
        // آخرین عکس تکرار برای درست کار کردن concat
        $txt.="file '".str_replace("'", "'\\''", end($images))."'\n";
        file_put_contents($list,$txt);

        $cmd='';
        if($audio && file_exists($audio)){
            // با صدا + کپشن اگر srt هست
            $vf = file_exists($srt) ? "subtitles=".escapeshellarg($srt).":force_style='FontSize=22,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=3,Outline=2'" : "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2";
            $cmd = "ffmpeg -y -f concat -safe 0 -i ".escapeshellarg($list)." -i ".escapeshellarg($audio)." -vf ".escapeshellarg($vf)." -c:v libx264 -r 30 -pix_fmt yuv420p -c:a aac -shortest ".escapeshellarg($out)." 2>&1";
        } else {
            $vf="scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2";
            $cmd="ffmpeg -y -f concat -safe 0 -i ".escapeshellarg($list)." -vf ".escapeshellarg($vf)." -c:v libx264 -r 30 -pix_fmt yuv420p -t $total_duration ".escapeshellarg($out)." 2>&1";
        }
        $out_log=[]; $ret=0;
        @exec($cmd, $out_log, $ret);
        return $ret===0 && file_exists($out);
    }

    private static function make_srt($script,$path){
        $srt='';
        foreach($script as $i=>$sc){
            $idx=$i+1;
            $start=self::tc($sc['start'] ?? ($i*15).":00");
            $end=self::tc($sc['end'] ?? (($i+1)*15).":00");
            $text=trim($sc['vo'] ?? $sc['text'] ?? '');
            $srt.="$idx\n$start --> $end\n$text\n\n";
        }
        file_put_contents($path,$srt);
    }
    private static function tc($t){
        // "0:07" -> "00:00:07,000"
        if(strpos($t,':')!==false){
            $parts=explode(':',$t);
            if(count($parts)==2) return sprintf("00:%02d:%02d,000", intval($parts[0]), intval($parts[1]));
            if(count($parts)==3) return sprintf("%02d:%02d:%02d,000", intval($parts[0]), intval($parts[1]), intval($parts[2]));
        }
        return "00:00:00,000";
    }

    private static function make_timeline_json($title,$script,$images,$audio){
        $tracks=[];
        foreach($script as $i=>$sc){
            $tracks[]=[
                'scene'=> $i+1,
                'start'=> $sc['start'] ?? '',
                'end'=> $sc['end'] ?? '',
                'shot'=> $sc['shot'] ?? '',
                'vo'=> $sc['vo'] ?? '',
                'image'=> $images[$i] ?? ($images[0] ?? ''),
                'text_on_screen'=> $sc['text'] ?? $sc['vo'] ?? '',
            ];
        }
        return [
            'title'=>$title,
            'duration'=>60,
            'tracks'=>$tracks,
            'audio'=>$audio,
            'export'=>[
                'creatomate'=>'https://creatomate.com — این JSON را به‌عنوان template وارد کن',
                'shotstack'=>'https://shotstack.io — همین ساختار را به timeline تبدیل کن',
                'premiere'=>'در Premiere: هر scene = یک sequence',
            ]
        ];
    }

    private static function make_preview_html($title,$script,$images,$audio){
        $slides='';
        foreach($script as $i=>$sc){
            $img=$images[$i % count($images)] ?? $images[0];
            $vo=esc_html($sc['vo'] ?? '');
            $shot=esc_html($sc['shot'] ?? '');
            $slides.="<div class='slide' style=\"background-image:url('".esc_url($img)."')\"><div class='overlay'><span class='shot'>$shot</span><p class='vo'>$vo</p></div></div>";
        }
        $audio_tag=$audio ? "<audio controls src='".esc_url($audio)."' style='width:100%; margin-top:12px'></audio>" : "<p style='color:#94A3B8; font-size:.85rem'>صدا هنوز ساخته نشده — از کارخانه پادکست را بساز</p>";
        return <<<HTML
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{$title}</title>
<style>
*{box-sizing:border-box} body{margin:0; font-family:Vazirmatn,Tahoma,sans-serif; background:#070A14; color:white; display:grid; place-items:center; min-height:100vh; padding:18px}
.player{width:min(720px,96vw); background:#0a0f1f; border:1px solid #1e293b; border-radius:18px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,.5)}
.header{padding:14px 16px; background:linear-gradient(90deg,#6d28ff,#4f46e5); font-weight:800}
.slides{position:relative; height:400px; overflow:hidden; background:#000}
.slide{position:absolute; inset:0; background-size:cover; background-position:center; opacity:0; transition:opacity .9s; display:grid; align-items:end}
.slide.active{opacity:1}
.overlay{background:linear-gradient(transparent, rgba(0,0,0,.78)); padding:18px; width:100%}
.shot{font-size:.75rem; background:rgba(255,255,255,.15); padding:3px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.2)}
.vo{margin:8px 0 0; font-size:1rem; font-weight:700; line-height:1.7}
.controls{padding:12px; display:flex; gap:8px; align-items:center; justify-content:space-between; flex-wrap:wrap}
.btn{padding:8px 14px; border-radius:999px; border:1px solid #1e293b; background:rgba(255,255,255,.07); color:white; cursor:pointer; font-family:inherit; font-weight:700}
.btn-primary{background:linear-gradient(90deg,#6d28ff,#4f46e5); border-color:transparent}
</style></head><body>
<div class="player">
<div class="header">🎬 {$title} — پیش‌نمایش 60 ثانیه</div>
<div class="slides" id="slides">{$slides}</div>
<div class="controls">
<button class="btn btn-primary" id="play">▶ پخش</button>
<span id="info" style="font-size:.82rem; color:#94A3B8">اسلاید 1 از 4 — هر اسلاید 15 ثانیه</span>
<button class="btn" onclick="window.print()">🖨️ چاپ استوری‌بورد</button>
</div>
<div style="padding:12px">{$audio_tag}</div>
</div>
<script>
let idx=0, slides=document.querySelectorAll('.slide'), timer=null;
function show(i){ slides.forEach((s,j)=> s.classList.toggle('active', j===i)); document.getElementById('info').textContent='اسلاید '+(i+1)+' از '+slides.length; }
show(0);
document.getElementById('play').onclick=()=>{
  if(timer){ clearInterval(timer); timer=null; document.getElementById('play').textContent='▶ پخش'; return; }
  document.getElementById('play').textContent='⏸ توقف';
  timer=setInterval(()=>{ idx=(idx+1)%slides.length; show(idx); }, 3000);
  const audio=document.querySelector('audio'); if(audio) audio.play();
};
</script>
</body></html>
HTML;
    }

    private static function make_zip($title,$images,$audio,$srt,$json,$preview_html,$script){
        if(!class_exists('ZipArchive')) return '';
        $upload=wp_upload_dir();
        $zipname='eaiw-video-pack-'.sanitize_title(mb_substr($title,0,25)).'-'.time().'.zip';
        $zippath=$upload['path'].'/'.$zipname;
        $zip=new ZipArchive();
        if($zip->open($zippath, ZipArchive::CREATE)!==true) return '';
        // تصاویر
        foreach($images as $i=>$p){
            if(file_exists($p)) $zip->addFile($p, 'images/scene-'.($i+1).'.'.pathinfo($p,PATHINFO_EXTENSION));
        }
        if($audio && file_exists($audio)) $zip->addFile($audio, 'audio/voice.mp3');
        if(file_exists($srt)) $zip->addFile($srt, 'captions.srt');
        if(file_exists($json)) $zip->addFile($json, 'timeline.json');
        $zip->addFromString('preview.html', $preview_html);
        $zip->addFromString('README.txt', "EtehadWP Video Pack — $title\n- images/: عکس هر صحنه\n- audio/voice.mp3: صدا\n- captions.srt: زیرنویس\n- timeline.json: برای Creatomate/Shotstack\n- preview.html: پیش‌نمایش را در مرورگر باز کن\n\nاگر FFmpeg روی هاست فعال باشد، MP4 هم ساخته می‌شود.");
        $zip->addFromString('script.txt', implode("\n\n", array_map(fn($s)=> ($s['start']??'').' - '.$s['vo'], $script)));
        $zip->close();
        return $upload['url'].'/'.$zipname;
    }
}
