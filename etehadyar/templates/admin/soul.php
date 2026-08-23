<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$enabled=(int)get_option('eaiw_chatsoul_enabled',0);
$name=get_option('eaiw_chatsoul_name','اتحادیار');
if(!$name) $name='اتحادیار';
$greeting=get_option('eaiw_chatsoul_greeting','سلام! من '.$name.' هستم — دستیار باهوش و بامزه‌ات 😎 هر سوالی داری بپرس، حتی جوک!');
$color=get_option('eaiw_chatsoul_color','#6d28ff');
$size=get_option('eaiw_chatsoul_size','medium');
$avatar=get_option('eaiw_chatsoul_avatar','');
$position=get_option('eaiw_chatsoul_position','bottom-right');
$offset_x=get_option('eaiw_chatsoul_offset_x',22);
$offset_y=get_option('eaiw_chatsoul_offset_y',22);
$mobile=get_option('eaiw_chatsoul_mobile',1);
$faqs=EAIW_ChatSoul::faqs();

if(isset($_POST['eaiw_soul_save']) && check_admin_referer('eaiw_soul')){
  $enabled=isset($_POST['enabled'])?1:0;
  update_option('eaiw_chatsoul_enabled',$enabled);
  if(isset($_POST['soul_name'])) update_option('eaiw_chatsoul_name', sanitize_text_field($_POST['soul_name']));
  if(isset($_POST['soul_greeting'])) update_option('eaiw_chatsoul_greeting', sanitize_textarea_field($_POST['soul_greeting']));
  if(isset($_POST['soul_color'])) update_option('eaiw_chatsoul_color', sanitize_hex_color($_POST['soul_color']));
  if(isset($_POST['soul_size'])) update_option('eaiw_chatsoul_size', sanitize_text_field($_POST['soul_size']));
  if(isset($_POST['soul_avatar'])) update_option('eaiw_chatsoul_avatar', esc_url_raw($_POST['soul_avatar']));
  if(isset($_POST['soul_position'])) update_option('eaiw_chatsoul_position', sanitize_text_field($_POST['soul_position']));
  if(isset($_POST['soul_offset_x'])) update_option('eaiw_chatsoul_offset_x', intval($_POST['soul_offset_x']));
  if(isset($_POST['soul_offset_y'])) update_option('eaiw_chatsoul_offset_y', intval($_POST['soul_offset_y']));
  if(isset($_POST['soul_mobile'])) update_option('eaiw_chatsoul_mobile', isset($_POST['soul_mobile'])?1:0); else update_option('eaiw_chatsoul_mobile',0);
  // FAQs
  $qs=$_POST['faq_q']??[]; $as=$_POST['faq_a']??[];
  $new=[]; foreach($qs as $i=>$q){ $q=trim(sanitize_text_field($q)); $a=trim(sanitize_textarea_field($as[$i]??'')); if($q&&$a) $new[]=['q'=>$q,'a'=>$a]; }
  EAIW_ChatSoul::save_faqs($new);
  echo '<div class="notice notice-success"><p>✅ ذخیره شد — '.$name.' الان با شخصیت جدید و FAQ فعال است. <a href="'.home_url().'" target="_blank">نمایش در سایت →</a></p></div>';
  $faqs=EAIW_ChatSoul::faqs();
  $name=get_option('eaiw_chatsoul_name'); $greeting=get_option('eaiw_chatsoul_greeting'); $color=get_option('eaiw_chatsoul_color');
  $size=get_option('eaiw_chatsoul_size'); $avatar=get_option('eaiw_chatsoul_avatar'); $position=get_option('eaiw_chatsoul_position');
  $offset_x=get_option('eaiw_chatsoul_offset_x'); $offset_y=get_option('eaiw_chatsoul_offset_y'); $mobile=get_option('eaiw_chatsoul_mobile');
}
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">💬</div><div><h1>پشتیبان هوشمند — اتحادیارِ بامزه 😎</h1><p>شخصیت باهوش و فان + FAQ + GapGPT برای صحبت عادی + طراحی شیک — همه قابل تنظیم</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <!-- تنظیمات اصلی -->
  <div class="eaiw-card eaiw-col-6">
    <h3><i>⚙️</i> تنظیمات نمایش</h3>
    <form method="post">
      <?php wp_nonce_field('eaiw_soul'); ?>
      <label style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:12px; padding:12px">
        <input type="checkbox" name="enabled" value="1" <?php checked($enabled,1);?> style="width:18px; height:18px">
        <span><b>نمایش در سایت</b><br><span style="font-size:.82rem; color:var(--nebula-muted)">ویجت شناور — گوشه سایت</span></span>
      </label>
      <label style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:12px; padding:12px; margin-top:8px">
        <input type="checkbox" name="soul_mobile" value="1" <?php checked($mobile,1);?> style="width:18px; height:18px">
        <span><b>نمایش در موبایل</b><br><span style="font-size:.82rem; color:var(--nebula-muted)">اگر خاموش، فقط دسکتاپ</span></span>
      </label>

      <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:12px; display:block">اسم پشتیبان (شخصیت فان)</label>
      <input class="eaiw-input" name="soul_name" value="<?php echo esc_attr($name);?>" placeholder="مثلاً: اتحادیار بامزه">
      <div style="font-size:.75rem; color:var(--nebula-muted)">تو چت به‌عنوان «<?php echo esc_html($name);?>» جواب میده — بامزه و باهوش</div>

      <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">پیام خوش‌آمد</label>
      <textarea class="eaiw-textarea" name="soul_greeting" rows="2"><?php echo esc_textarea($greeting);?></textarea>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
        <div>
          <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700">رنگ</label>
          <input type="color" name="soul_color" value="<?php echo esc_attr($color);?>" style="width:100%; height:40px; border-radius:10px; border:1px solid var(--nebula-border); padding:3px; background:var(--nebula-input-bg)">
        </div>
        <div>
          <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700">اندازه</label>
          <select class="eaiw-select" name="soul_size">
            <option value="small" <?php selected($size,'small');?>>کوچک</option>
            <option value="medium" <?php selected($size,'medium');?>>متوسط</option>
            <option value="large" <?php selected($size,'large');?>>بزرگ</option>
          </select>
        </div>
      </div>

      <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">عکس پروفایل — URL (اختیاری)</label>
      <input class="eaiw-input" name="soul_avatar" value="<?php echo esc_attr($avatar);?>" placeholder="https://site.com/avatar.jpg — خالی = ایموجی 💬">
      <div style="font-size:.70rem; color:var(--nebula-muted)">مربع 1:1 — 128×128 — اگر ندهی، گرادیان اتحادیار می‌ماند</div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
        <div>
          <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700">محل قرارگیری</label>
          <select class="eaiw-select" name="soul_position">
            <option value="bottom-right" <?php selected($position,'bottom-right');?>>پایین راست</option>
            <option value="bottom-left" <?php selected($position,'bottom-left');?>>پایین چپ</option>
            <option value="top-right" <?php selected($position,'top-right');?>>بالا راست</option>
            <option value="top-left" <?php selected($position,'top-left');?>>بالا چپ</option>
          </select>
        </div>
        <div>
          <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700">فاصله از کنار/پایین (px)</label>
          <div style="display:flex; gap:6px">
            <input class="eaiw-input" name="soul_offset_x" type="number" value="<?php echo (int)$offset_x;?>" placeholder="X" style="flex:1">
            <input class="eaiw-input" name="soul_offset_y" type="number" value="<?php echo (int)$offset_y;?>" placeholder="Y" style="flex:1">
          </div>
        </div>
      </div>

      <div style="margin-top:10px; background:rgba(109,40,255,.07); border:1px solid #6d28ff22; border-radius:10px; padding:10px; font-size:.82rem; color:var(--nebula-muted)">
        <b style="color:var(--nebula-text-strong)">شخصیت:</b> باهوش، بامزه، فان — کمی شوخی، ولی دقیق — با GapGPT برای حرف‌های عادی (سلام، جوک، ...) و با حافظه سایت برای سوالات تخصصی — خودکار تشخیص میده.
      </div>

      <button name="eaiw_soul_save" class="eaiw-btn eaiw-btn-primary" style="margin-top:14px; width:100%">💾 ذخیره — شخصیت جدید فعال</button>
    </form>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>💬</i> تست زنده — همینجا بپرس</h3>
    <div style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:10px; font-size:.82rem; color:var(--nebula-muted)">
      شخصیت فعلی: <b style="color:var(--nebula-text-strong)"><?php echo esc_html($name);?></b> — GapGPT: <?php echo EAIW_Vault::get_key('gapgpt')?'✅ وصل (فان)':'— وصل نیست (فقط حافظه)';?> — FAQ: <?php echo count($faqs);?> تا
    </div>
    <input class="eaiw-input" id="eaiwSoulQ" placeholder="مثلاً: سلام چطوری؟ یا قیمت فلان محصول؟" style="margin-top:10px">
    <button class="eaiw-btn eaiw-btn-cyan" id="eaiwSoulTest" style="margin-top:10px; width:100%">پرسش از <?php echo esc_html($name);?> 😎</button>
    <div id="eaiwSoulAnswer" style="margin-top:12px; background:rgba(109,40,255,.07); border:1px solid #6d28ff22; border-radius:12px; padding:12px; min-height:90px; font-size:.9rem; color:var(--nebula-text); white-space:pre-wrap">پاسخ اینجا می‌آید — GapGPT اگر وصل باشد، حتی جوک هم میگه!</div>
    <div style="margin-top:8px; font-size:.75rem; color:var(--nebula-muted)">نکته: اگر سوال FAQ باشه، اول از FAQ جواب میده — وگرنه GapGPT + حافظه</div>
  </div>

  <div class="eaiw-card eaiw-col-12">
    <h3><i>❓</i> سوال‌های متداول — FAQ برای چت</h3>
    <p style="font-size:.85rem; color:var(--nebula-muted)">اینجا سوال/جواب‌های پرتکرار را بنویس — وقتی کاربر مشابهش را بپرسد، اول از اینجا جواب می‌دهد (سریع و دقیق) — در چت هم نمایش داده می‌شود.</p>
    <form method="post">
      <?php wp_nonce_field('eaiw_soul'); ?>
      <input type="hidden" name="enabled" value="<?php echo $enabled?'1':'0';?>">
      <input type="hidden" name="soul_name" value="<?php echo esc_attr($name);?>">
      <input type="hidden" name="soul_greeting" value="<?php echo esc_attr($greeting);?>">
      <input type="hidden" name="soul_color" value="<?php echo esc_attr($color);?>">
      <input type="hidden" name="soul_size" value="<?php echo esc_attr($size);?>">
      <input type="hidden" name="soul_avatar" value="<?php echo esc_attr($avatar);?>">
      <input type="hidden" name="soul_position" value="<?php echo esc_attr($position);?>">
      <input type="hidden" name="soul_offset_x" value="<?php echo (int)$offset_x;?>">
      <input type="hidden" name="soul_offset_y" value="<?php echo (int)$offset_y;?>">
      <input type="hidden" name="soul_mobile" value="<?php echo $mobile?'1':'0';?>">
      <div id="eaiwFaqList">
        <?php if(empty($faqs)) $faqs=[['q'=>'','a'=>'']]; foreach($faqs as $i=>$f): ?>
          <div class="eaiw-faq-row" style="display:grid; grid-template-columns:1fr 1.4fr auto; gap:8px; margin:8px 0; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px">
            <input class="eaiw-input" name="faq_q[]" value="<?php echo esc_attr($f['q']);?>" placeholder="سوال: مثلاً هزینه ارسال؟">
            <textarea class="eaiw-textarea" name="faq_a[]" rows="2" placeholder="جواب کامل..."><?php echo esc_textarea($f['a']);?></textarea>
            <button type="button" class="eaiw-btn eaiw-btn-ghost eaiwFaqDel" style="padding:6px 10px; font-size:.78rem">حذف</button>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex; gap:8px; margin-top:10px">
        <button type="button" class="eaiw-btn eaiw-btn-ghost" id="eaiwFaqAdd" style="padding:7px 12px; font-size:.82rem">➕ افزودن FAQ</button>
        <button name="eaiw_soul_save" class="eaiw-btn eaiw-btn-primary" style="padding:7px 14px; font-size:.82rem">💾 ذخیره FAQها</button>
        <span style="font-size:.78rem; color:var(--nebula-muted); align-self:center">در چت به‌صورت دکمه‌های سریع هم نمایش داده می‌شود</span>
      </div>
    </form>
  </div>
</div>
</div>

<script>
jQuery(function($){
  $('#eaiwFaqAdd').on('click', ()=>{
    $('#eaiwFaqList').append(`<div class="eaiw-faq-row" style="display:grid; grid-template-columns:1fr 1.4fr auto; gap:8px; margin:8px 0; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px">
      <input class="eaiw-input" name="faq_q[]" placeholder="سوال">
      <textarea class="eaiw-textarea" name="faq_a[]" rows="2" placeholder="جواب"></textarea>
      <button type="button" class="eaiw-btn eaiw-btn-ghost eaiwFaqDel" style="padding:6px 10px; font-size:.78rem">حذف</button>
    </div>`);
  });
  $(document).on('click','.eaiwFaqDel', function(){ $(this).closest('.eaiw-faq-row').remove(); });
  $('#eaiwSoulTest').on('click', function(){
    const q=$('#eaiwSoulQ').val(); if(!q) return;
    $('#eaiwSoulAnswer').text('در حال پرسش از <?php echo esc_js($name);?> ...');
    fetch('<?php echo esc_url_raw(rest_url('eaiw/v1/chat'));?>', {method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':'<?php echo wp_create_nonce('wp_rest');?>'}, body:JSON.stringify({message:q})})
      .then(r=>r.json()).then(d=> {
        let txt=d.answer||JSON.stringify(d);
        if(d.type) txt='['+d.type+']\n'+txt;
        $('#eaiwSoulAnswer').text(txt);
      }).catch(()=> $('#eaiwSoulAnswer').text('خطا — دوباره تلاش کن'));
  });
});
</script>
