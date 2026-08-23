<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$token=get_option('eaiw_telegram_token','');
$chat=get_option('eaiw_telegram_chat','');
$order_chat=get_option('eaiw_telegram_order_chat','');
$order_enabled=get_option('eaiw_telegram_order_enabled',0);
$proxy=get_option('eaiw_telegram_proxy','');
if(isset($_POST['eaiw_tg_save']) && check_admin_referer('eaiw_tg')){
  $token=trim(sanitize_text_field($_POST['telegram_token']??''));
  $chat=trim(sanitize_text_field($_POST['telegram_chat']??''));
  $order_chat=trim(sanitize_text_field($_POST['telegram_order_chat']??''));
  $order_enabled=isset($_POST['telegram_order_enabled'])?1:0;
  $proxy=trim(sanitize_text_field($_POST['telegram_proxy']??''));
  update_option('eaiw_telegram_token',$token);
  update_option('eaiw_telegram_chat',$chat);
  update_option('eaiw_telegram_order_chat',$order_chat);
  update_option('eaiw_telegram_order_enabled',$order_enabled);
  update_option('eaiw_telegram_proxy',$proxy);
  echo '<div class="notice notice-success"><p>✅ تنظیمات تلگرام ذخیره شد — تست اتصال را بزن.</p></div>';
}
$has_token = !empty($token);
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">📣</div><div><h1>تلگرام پیشرفته — همه کار تلگرام</h1><p>کانال رسمی + سفارشات + ربات + پروکسی — همه واقعی، برای ایران</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-6">
    <h3><i>🤖</i> اتصال ربات</h3>
    <form method="post">
      <?php wp_nonce_field('eaiw_tg'); ?>
      <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700">Bot Token — از @BotFather</label>
      <input class="eaiw-input" name="telegram_token" value="<?php echo esc_attr($token);?>" placeholder="123456:ABC...">
      <div style="font-size:.70rem; color:var(--nebula-muted)">بدون فاصله — از @BotFather → /newbot</div>

      <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">پروکسی (برای ایران — اختیاری)</label>
      <input class="eaiw-input" name="telegram_proxy" value="<?php echo esc_attr($proxy);?>" placeholder="مثلاً: https://proxy.example.com یا خالی = مستقیم">
      <div style="font-size:.70rem; color:var(--nebula-muted)">اگر تلگرام فیلتره، یک پروکسی HTTP بذار — مثلاً `https://api.allorigins.win/raw?url=` یا سرور خودت — خالی = مستقیم `api.telegram.org`</div>

      <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap">
        <button type="button" class="eaiw-btn eaiw-btn-primary" id="eaiwTgTest" style="padding:7px 12px; font-size:.82rem">🔍 تست اتصال ربات</button>
        <span id="eaiwTgTestRes" style="font-size:.78rem; color:var(--nebula-muted)"></span>
      </div>
      <div id="eaiwTgTestDetail" style="margin-top:8px; display:none; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px; font-size:.78rem"></div>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>📢</i> کانال رسمی — ارسال پست</h3>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700">آیدی کانال رسمی</label>
    <input class="eaiw-input" name="telegram_chat" value="<?php echo esc_attr($chat);?>" placeholder="@eteyadyar یا -1001234567890">
    <div style="font-size:.70rem; color:var(--nebula-muted)">عمومی: @username — خصوصی: -100... — ربات باید ادمین باشد</div>

    <div style="margin-top:10px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px">
      <b style="font-size:.85rem">تست ارسال به کانال:</b>
      <textarea class="eaiw-textarea" id="eaiwTgTestMsg" rows="2" placeholder="متن تست: سلام از اتحادیار 😎"></textarea>
      <div style="display:flex; gap:8px; margin-top:8px">
        <button type="button" class="eaiw-btn eaiw-btn-ghost" id="eaiwTgSendTest" style="padding:6px 10px; font-size:.78rem">✈️ ارسال تست به کانال</button>
        <button type="button" class="eaiw-btn eaiw-btn-ghost" id="eaiwTgSendFactory" style="padding:6px 10px; font-size:.78rem">🏭 ارسال آخرین کارخانه</button>
      </div>
      <div id="eaiwTgSendRes" style="margin-top:8px; font-size:.78rem"></div>
    </div>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>🛒</i> سفارشات ووکامرس → تلگرام</h3>
    <label style="display:flex; gap:8px; align-items:center; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px">
      <input type="checkbox" name="telegram_order_enabled" value="1" <?php checked($order_enabled,1);?> style="width:18px; height:18px">
      <span><b>ارسال خودکار سفارش جدید به تلگرام</b><br><span style="font-size:.78rem; color:var(--nebula-muted)">هر سفارش جدید → پیام به بات/کانال شخصی</span></span>
    </label>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">آیدی بات/کانال شخصی برای سفارش (جدا از کانال رسمی)</label>
    <input class="eaiw-input" name="telegram_order_chat" value="<?php echo esc_attr($order_chat);?>" placeholder="@username شخصی یا -100... یا آیدی عددی">
    <div style="font-size:.70rem; color:var(--nebula-muted)">اگر خالی باشد، به همون کانال رسمی می‌فرستد — برای شخصی، آیدی عددی خودت را از @getidsbot بگیر</div>
    <div style="margin-top:8px; background:rgba(16,185,129,.07); border:1px solid #10b98122; border-radius:10px; padding:8px; font-size:.80rem">
      <b style="color:#065F46">چطور کار می‌کند؟</b> هوک `woocommerce_new_order` — سفارش که ثبت شد، پیام با نام مشتری، مبلغ، محصولات به تلگرام می‌رود — بدون نیاز به Cron.
    </div>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>🔗</i> اتصال ربات به سایت — قابلیت‌ها</h3>
    <ul style="font-size:.85rem; color:var(--nebula-text); padding-right:18px">
      <li><b>ارسال پست کانال:</b> از کارخانه → تلگرام (با عکس)</li>
      <li><b>سفارشات:</b> هر خرید → پیام فوری به تو</li>
      <li><b>پشتیبان:</b> ربات می‌تواند به چت سایت وصل شود (آینده)</li>
      <li><b>دستورات:</b> /start → خوش‌آمد + لینک سایت</li>
    </ul>
    <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap">
      <a href="https://core.telegram.org/bots/tutorial" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 10px; font-size:.78rem">📚 آموزش BotFather</a>
      <a href="https://t.me/getidsbot" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 10px; font-size:.78rem">🆔 @getidsbot</a>
    </div>
    <div style="margin-top:10px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:8px; font-size:.75rem; color:var(--nebula-muted)">
      پروکسی: اگر `telegram_proxy` پر باشد، همه درخواست‌ها از `proxy + https://api.telegram.org/...` می‌رود — برای دور زدن فیلتر بدون VPN سرور.
    </div>
  </div>

  <div class="eaiw-card eaiw-col-12" style="text-align:left">
    <button name="eaiw_tg_save" class="eaiw-btn eaiw-btn-primary" style="padding:10px 18px">💾 ذخیره تنظیمات تلگرام</button>
    <span style="font-size:.82rem; color:var(--nebula-muted); margin-right:10px">ذخیره با trim خودکار — فاصله‌ها پاک می‌شود</span>
  </div>
    </form>
</div>
</div>

<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2800);
  }
  $('#eaiwTgTest').on('click', function(){
    const btn=$(this); btn.text('در حال تست...').prop('disabled',true);
    $('#eaiwTgTestDetail').hide();
    $.post(EAIW.ajax, {action:'eaiw_social_test_telegram', _ajax_nonce:EAIW.nonce}, res=>{
      btn.text('🔍 تست اتصال ربات').prop('disabled',false);
      if(res.success){
        const d=res.data;
        $('#eaiwTgTestDetail').html(`<b>ربات:</b> @${d.name} (ID:${d.id})<br><b>کانال:</b> ${d.chat_title||'—'} (${d.chat_type||''})<br><b>ادمین:</b> ${d.admin_status} — <span style="color:#10b981">${d.note}</span>`).show();
        toast('تست موفق');
      } else {
        $('#eaiwTgTestDetail').html(`<span style="color:#FECACA">${res.data}</span>`).show();
        toast(res.data, false);
      }
    });
  });
  $('#eaiwTgSendTest').on('click', function(){
    const btn=$(this), txt=$('#eaiwTgTestMsg').val()||'سلام از اتحادیار 😎 — تست کانال';
    btn.text('در حال ارسال...').prop('disabled',true);
    $.post(EAIW.ajax, {action:'eaiw_factory_publish_telegram', text:txt, image:'', _ajax_nonce:EAIW.nonce}, res=>{
      btn.text('✈️ ارسال تست به کانال').prop('disabled',false);
      if(res.success) { $('#eaiwTgSendRes').html('<span class="eaiw-badge green">ارسال شد — ID:'+res.data.message_id+'</span>'); toast('ارسال شد'); }
      else { $('#eaiwTgSendRes').html('<span class="eaiw-badge red">'+res.data+'</span>'); toast(res.data,false); }
    });
  });
  $('#eaiwTgSendFactory').on('click', function(){
    const btn=$(this);
    const raw=localStorage.getItem('eaiw_factory_last');
    if(!raw) return toast('اول کارخانه را اجرا کن', false);
    try{
      const d=JSON.parse(raw);
      const text=d.title+"\n\n"+d.hashtags;
      const img=(d.images&&d.images[0])?d.images[0].url:'';
      btn.text('در حال ارسال...').prop('disabled',true);
      $.post(EAIW.ajax, {action:'eaiw_factory_publish_telegram', text:text, image:img, _ajax_nonce:EAIW.nonce}, res=>{
        btn.text('🏭 ارسال آخرین کارخانه').prop('disabled',false);
        if(res.success) { $('#eaiwTgSendRes').html('<span class="eaiw-badge green">ارسال شد</span>'); toast('کارخانه به کانال رفت'); }
        else { $('#eaiwTgSendRes').html('<span class="eaiw-badge red">'+res.data+'</span>'); toast(res.data,false); }
      });
    }catch(e){ toast('خطا',false); }
  });
});
</script>
