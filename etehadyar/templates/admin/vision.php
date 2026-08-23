<?php defined('ABSPATH')||exit; 
$theme=get_option('eaiw_theme','dark'); 
$has_flux = EAIW_Vault::get_key('flux') ? true : false;
$has_openai = EAIW_Vault::get_key('openai') || EAIW_Vault::get_key('gapgpt');
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🎨</div><div><h1>تصویرساز هوشمند — Flux + OpenAI واقعی</h1><p>بنویس چی می‌خوای، همین‌جا عکس واقعی بساز — اولویت Flux، بعد OpenAI</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>
<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-6">
    <h3><i>✏️</i> چی بسازیم؟</h3>
    <textarea class="eaiw-textarea" id="eaiwVisionPrompt" rows="3" placeholder="مثلاً: عکس شاخص برای مقاله قهوه اسپرسو، فوتورئال، نور استودیو، پس‌زمینه روشن، کیفیت بالا"><?php echo esc_textarea($_GET['prompt']??'');?></textarea>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
      <select class="eaiw-select" id="eaiwVisionStyle">
        <option value="photorealistic">واقع‌گرایانه — مثل عکس</option>
        <option value="minimal">مینیمال — ساده</option>
        <option value="3d">سه‌بعدی</option>
        <option value="illustration">نقاشی هنری</option>
      </select>
      <select class="eaiw-select" id="eaiwVisionSize">
        <option value="1024x1024">مربع 1:1 (اینستا)</option>
        <option value="1792x1024" selected>افقی 16:9 (شاخص)</option>
        <option value="1024x1792">عمودی 9:16 (استوری)</option>
      </select>
    </div>
    <button class="eaiw-btn eaiw-btn-primary" id="eaiwVisionGen" style="margin-top:12px; width:100%">✨ ساخت تصویر واقعی — Flux / OpenAI</button>
    <button class="eaiw-btn eaiw-btn-ghost" id="eaiwVisionFlux" style="margin-top:8px; width:100%">⚡ ساخت با Flux (سریع‌تر)</button>
    <div style="margin-top:12px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:10px; font-size:.82rem; color:var(--nebula-muted)">
      <?php if($has_flux): ?><span class="eaiw-badge green">Flux فعال</span> عکس‌ها با Flux Schnell ساخته می‌شود — سریع و واقعی.<br>
      <?php elseif($has_openai): ?><span class="eaiw-badge cyan">OpenAI فعال</span> عکس‌ها با DALL·E 3 ساخته می‌شود.<br>
      <?php else: ?><span class="eaiw-badge red">کلید تصویر وارد نشده</span> یک عکس موقت باکیفیت ساخته و ذخیره می‌شود — برای واقعی، کلید Flux یا OpenAI را در تنظیمات وارد کن.<br>
      <?php endif; ?>
      مستقیم در کتابخانه ذخیره + Alt خودکار.
    </div>
  </div>
  <div class="eaiw-card eaiw-col-6">
    <h3><i>🖼️</i> نتیجه</h3>
    <div id="eaiwVisionResult" style="min-height:220px; display:grid; place-items:center; color:var(--nebula-muted); font-size:.9rem; text-align:center; background:var(--nebula-input-bg); border:1px dashed var(--nebula-border); border-radius:12px; padding:16px">توضیح را بنویس و دکمه را بزن<br><span style="font-size:.8rem">عکس اینجا + کتابخانه</span></div>
    <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap">
      <span class="eaiw-pill">ذخیره خودکار</span><span class="eaiw-pill">Alt فارسی</span><span class="eaiw-pill">قابل استفاده فوری</span>
    </div>
  </div>
</div>
</div>
<script>
jQuery(function($){
  function genFlux(){
    const prompt=$('#eaiwVisionPrompt').val(), style=$('#eaiwVisionStyle').val(), size=$('#eaiwVisionSize').val()||'1792x1024';
    if(!prompt || prompt.length<8){ alert('توضیح را کامل‌تر بنویس'); return; }
    const btn=$('#eaiwVisionFlux'); btn.prop('disabled',true).text('در حال ساخت با Flux...');
    $.post(EAIW.ajax, {action:'eaiw_flux_generate', prompt:prompt, style:style, size:size, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('⚡ ساخت با Flux (سریع‌تر)');
      const box=$('#eaiwVisionResult');
      if(res.success){
        box.html(`<div style="text-align:center"><img src="${res.data.url}" style="max-width:100%; border-radius:12px; border:1px solid var(--nebula-border)"><div style="margin-top:8px; font-size:.82rem; color:var(--nebula-muted)">${res.data.note||''}</div><div style="margin-top:8px"><a href="${res.data.url}" target="_blank" class="eaiw-btn eaiw-btn-primary" style="padding:6px 10px; font-size:.78rem">⬇️ دانلود</a> <a href="upload.php" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 10px; font-size:.78rem">کتابخانه</a></div></div>`);
      } else alert(res.data);
    }).fail(()=>{ btn.prop('disabled',false).text('⚡ ساخت با Flux (سریع‌تر)'); alert('خطا'); });
  }
  $('#eaiwVisionFlux').on('click', genFlux);
});
</script>
