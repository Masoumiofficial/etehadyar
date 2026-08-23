<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
if(isset($_POST['eaiw_save_vault']) && check_admin_referer('eaiw_vault')){
  foreach(['openai','gemini','claude','gapgpt','stability'] as $p){
    if(isset($_POST['eaiw_key_'.$p])){
      $k = trim(sanitize_text_field($_POST['eaiw_key_'.$p]));
      if($k !== '' && $k !== '••••••••') EAIW_Vault::save_key($p, $k);
    }
  }
  if(isset($_POST['flux_key'])){
    $k=trim(sanitize_text_field($_POST['flux_key']));
    if($k && $k!=='••••••••') EAIW_Vault::save_key('flux',$k);
  }
  if(isset($_POST['serper_key'])) update_option('eaiw_serper_key', trim(sanitize_text_field($_POST['serper_key'])));
  if(isset($_POST['soul_name'])) update_option('eaiw_chatsoul_name', sanitize_text_field($_POST['soul_name']));
  if(isset($_POST['soul_greeting'])) update_option('eaiw_chatsoul_greeting', sanitize_textarea_field($_POST['soul_greeting']));
  if(isset($_POST['soul_color'])) update_option('eaiw_chatsoul_color', sanitize_hex_color($_POST['soul_color']));
  // social — trim دقیق
  if(isset($_POST['telegram_token'])) update_option('eaiw_telegram_token', trim(sanitize_text_field($_POST['telegram_token'])));
  if(isset($_POST['telegram_chat'])) update_option('eaiw_telegram_chat', trim(sanitize_text_field($_POST['telegram_chat'])));
  if(isset($_POST['instagram_token'])) update_option('eaiw_instagram_token', trim(sanitize_text_field($_POST['instagram_token'])));
  if(isset($_POST['instagram_user'])) update_option('eaiw_instagram_user', trim(sanitize_text_field($_POST['instagram_user'])));
  echo '<div class="notice notice-success"><p>✅ همه چیز ذخیره شد — کلیدها رمز شد + پشتیبان + شبکه‌ها. حالا «تست اتصال تلگرام» را بزن.</p></div>';
}
$has = fn($p)=> EAIW_Vault::get_key($p) ? '••••••••' : '';
$soul_name=get_option('eaiw_chatsoul_name','پشتیبان هوشمند');
$soul_greeting=get_option('eaiw_chatsoul_greeting','سلام! من '.$soul_name.' هستم — هر سوالی داری بپرس ✨');
$soul_color=get_option('eaiw_chatsoul_color','#6d28ff');
$tg_token=get_option('eaiw_telegram_token','');
$tg_chat=get_option('eaiw_telegram_chat','');
$ig_token=get_option('eaiw_instagram_token','');
$ig_user=get_option('eaiw_instagram_user','');
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">⚙️</div><div><h1>تنظیمات — همه‌چیز اینجا</h1><p>کلیدها + پشتیبان + تلگرام و اینستا — همه امن و رمز می‌شود — <b>همه آمار واقعی سایتت</b></p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>
<form method="post">
<?php wp_nonce_field('eaiw_vault'); ?>
<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-6">
    <h3><i>🔐</i> کلیدهای هوش مصنوعی</h3>
    <p style="font-size:.85rem">هر کدام را که داری وارد کن — GapGPT برای ایران عالیه.</p>
    <?php foreach(['openai'=>'OpenAI','gapgpt'=>'GapGPT (ایران)','gemini'=>'Gemini','claude'=>'Claude','stability'=>'Stability'] as $k=>$label): ?>
      <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block"><?php echo esc_html($label);?> <span class="eaiw-badge <?php echo $has($k)?'green':'red';?>" style="font-size:.68rem"><?php echo $has($k)?'فعال':'غیرفعال';?></span></label>
      <div style="display:flex; gap:6px; align-items:center; margin-top:4px">
        <input class="eaiw-input" name="eaiw_key_<?php echo esc_attr($k);?>" value="<?php echo esc_attr($has($k));?>" placeholder="کلید" style="flex:1">
        <button type="button" class="eaiw-btn eaiw-btn-ghost eaiwAiTest" data-provider="<?php echo esc_attr($k);?>" style="padding:7px 10px; font-size:.75rem; white-space:nowrap">تست</button>
      </div>
      <div class="eaiwAiTestRes" data-provider="<?php echo esc_attr($k);?>" style="font-size:.75rem; color:var(--nebula-muted); margin-top:4px"><?php echo $has($k)?'✓ ذخیره شده — تست بزن':'— وارد نشده';?></div>
    <?php endforeach; ?>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">Flux (Fal) — fal_... <span class="eaiw-badge <?php echo $has('flux')?'green':'red';?>" style="font-size:.68rem"><?php echo $has('flux')?'فعال':'غیرفعال';?></span></label>
    <div style="display:flex; gap:6px; align-items:center; margin-top:4px">
      <input class="eaiw-input" name="flux_key" value="<?php echo esc_attr($has('flux'));?>" placeholder="fal_..." style="flex:1">
      <button type="button" class="eaiw-btn eaiw-btn-ghost eaiwAiTest" data-provider="flux" style="padding:7px 10px; font-size:.75rem">تست</button>
    </div>
    <div class="eaiwAiTestRes" data-provider="flux" style="font-size:.75rem; color:var(--nebula-muted); margin-top:4px"><?php echo $has('flux')?'✓ ذخیره شده':'— وارد نشده';?></div>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">Serper.dev — serper_...</label>
    <input class="eaiw-input" name="serper_key" value="<?php echo esc_attr(get_option('eaiw_serper_key',''));?>" placeholder="serper_...">
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>💬</i> پشتیبان هوشمند</h3>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:6px; display:block">اسم پشتیبان (قابل تغییر)</label>
    <input class="eaiw-input" name="soul_name" value="<?php echo esc_attr($soul_name);?>" placeholder="مثلاً: دستیار اتحاد">
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">پیام خوش‌آمد</label>
    <textarea class="eaiw-textarea" name="soul_greeting" rows="2"><?php echo esc_textarea($soul_greeting);?></textarea>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">رنگ</label>
    <input type="color" name="soul_color" value="<?php echo esc_attr($soul_color);?>" style="width:70px; height:40px; border-radius:10px; border:1px solid var(--nebula-border); padding:3px; background:var(--nebula-input-bg)">

    <h3 style="margin-top:16px"><i>📣</i> تلگرام — انتشار واقعی (عیب‌یاب جدید)</h3>
    <div style="background:rgba(0,229,255,.06); border:1px solid rgba(0,229,255,.15); border-radius:10px; padding:10px; font-size:.82rem; color:var(--nebula-muted)">
      <b style="color:var(--nebula-text-strong)">راهنمای آیدی عددی:</b> اگر کانالت خصوصی است، آیدی باید با <code>-100</code> شروع شود (مثل: <code>-1001234567890</code>). از ربات <b>@getidsbot</b> یا <b>@username_to_id_bot</b> فوروارد کن تا آیدی را بگیری. ربات حتماً باید <b>ادمین کانال</b> با دسترسی «Post messages» باشد.
    </div>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; display:block; margin-top:10px">Bot Token — از @BotFather</label>
    <input class="eaiw-input" name="telegram_token" value="<?php echo esc_attr($tg_token);?>" placeholder="123456:ABC... (بدون فاصله)">
    <div style="font-size:.70rem; color:var(--nebula-muted)">دقیق کپی کن — فاصله اول/آخر پاک می‌شود</div>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:8px; display:block">آیدی کانال</label>
    <input class="eaiw-input" name="telegram_chat" value="<?php echo esc_attr($tg_chat);?>" placeholder="@username یا -1001234567890">
    <div style="font-size:.70rem; color:var(--nebula-muted)">عمومی: <code>@username</code> — خصوصی: <code>-100...</code> (13 رقم)</div>
    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; align-items:center">
      <button type="button" class="eaiw-btn eaiw-btn-primary" id="eaiwTestTg" style="padding:7px 12px; font-size:.82rem">🔍 تست کامل اتصال</button>
      <span id="eaiwTgResult" style="font-size:.78rem; color:var(--nebula-muted)"></span>
    </div>
    <div id="eaiwTgDetail" style="margin-top:8px; display:none; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px; font-size:.78rem"></div>

    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:14px; display:block">اینستاگرام — اختیاری</label>
    <input class="eaiw-input" name="instagram_token" value="<?php echo esc_attr($ig_token);?>" placeholder="IGQ...">
    <input class="eaiw-input" name="instagram_user" value="<?php echo esc_attr($ig_user);?>" placeholder="178414..." style="margin-top:6px">
  </div>

  <div class="eaiw-card eaiw-col-12" style="text-align:left; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px">
    <div style="font-size:.82rem; color:var(--nebula-muted)">کلیدها AES-256 — فقط •••••••• می‌بینی — تلگرام با فاصله اضافی هم درست ذخیره می‌شود.</div>
    <button name="eaiw_save_vault" class="eaiw-btn eaiw-btn-primary" style="padding:12px 20px">💾 ذخیره همه</button>
  </div>
</div>
</form>
</div>
<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2500);
  }
  $('#eaiwTestTg').on('click', function(){
    const btn=$(this); btn.text('در حال تست...').prop('disabled',true);
    $('#eaiwTgDetail').hide().html('');
    $.post(EAIW.ajax, {action:'eaiw_social_test_telegram', _ajax_nonce:EAIW.nonce}, res=>{
      btn.text('🔍 تست کامل اتصال').prop('disabled',false);
      if(res.success){
        const d=res.data;
        $('#eaiwTgResult').html('<span class="eaiw-badge green">✅ وصل — @'+d.name+'</span>');
        let detail=`<b>ربات:</b> @${d.name} (ID: ${d.id})<br>`;
        if(d.chat_title) detail+=`<b>کانال:</b> ${d.chat_title} (${d.chat_type})<br><b>وضعیت ربات:</b> ${d.admin_status}<br>`;
        detail+=`<span style="color:#10b981">${d.note}</span>`;
        if(d.admin_status && d.admin_status!=='administrator' && d.admin_status!=='creator'){
          detail+=`<br><br><span style="color:#ef4444; font-weight:700">→ ربات را به کانال اضافه کن و ادمین کن: کانال → مدیران → افزودن مدیر → ربات → تیک Post messages</span>`;
        }
        $('#eaiwTgDetail').html(detail).show();
        toast('تست موفق');
      } else {
        $('#eaiwTgResult').html('<span class="eaiw-badge red">❌ نشد</span>');
        $('#eaiwTgDetail').html('<span style="color:#FECACA">'+res.data+'</span>').show();
        toast(res.data,false);
      }
    }).fail(()=>{ btn.text('🔍 تست کامل اتصال').prop('disabled',false); $('#eaiwTgResult').html('<span class="eaiw-badge red">خطای ارتباط</span>'); });
  });
  $('.eaiwAiTest').on('click', function(){
    const btn=$(this), prov=btn.data('provider');
    if(prov==='flux'){
      const has=btn.closest('div').find('input').val()==='••••••••';
      if(has){ $('.eaiwAiTestRes[data-provider="flux"]').html('<span class="eaiw-badge green">فعال — ذخیره شده</span>'); toast('Flux فعال'); }
      else $('.eaiwAiTestRes[data-provider="flux"]').html('<span class="eaiw-badge red">غیرفعال</span>');
      return;
    }
    btn.text('...').prop('disabled',true);
    const resEl=$('.eaiwAiTestRes[data-provider="'+prov+'"]');
    resEl.html('در حال تست...');
    $.post(EAIW.ajax, {action:'eaiw_ai_test', provider:prov, _ajax_nonce:EAIW.nonce}, res=>{
      btn.text('تست').prop('disabled',false);
      if(res.success){
        resEl.html('<span class="eaiw-badge green">✅ کار می‌کند</span> — '+res.data.preview);
        toast(prov+' کار می‌کند');
      } else {
        resEl.html('<span class="eaiw-badge red">❌ کار نمی‌کند</span> — '+res.data);
        toast(prov+' مشکل', false);
      }
    }).fail(()=>{ btn.text('تست').prop('disabled',false); resEl.html('<span class="eaiw-badge red">خطای ارتباط</span>'); });
  });
});
</script>
<div style="margin-top:8px; font-size:.75rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:8px; padding:8px">💡 بعد از وارد کردن کلید جدید، اول <b>ذخیره</b> کن بعد <b>تست</b> بزن — تست با کلید ذخیره شده انجام می‌شود.</div>
