<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
EAIW_Nexus::seed_defaults();
$autos = EAIW_Nexus::all_automations();
$runs = class_exists('EAIW_Automation_Engine') ? EAIW_Automation_Engine::recent_runs(8) : [];
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">⚡</div><div><h1>اتوماسیون هوشمند — همه‌چیز خودکار</h1><p>محصول جدید آمد؟ خودکار مقاله + ویدیو + تلگرام بساز — بدون کلیک</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-8">
    <h3><i>⚙️</i> کارهای خودکار فعال</h3>
    <p style="font-size:.85rem; color:var(--nebula-muted)">این‌ها بدون اینکه دست بزنی کار می‌کنند — هر کدام را خاموش/روشن یا الان اجرا کن:</p>
    <table class="eaiw-table">
      <tr><th>عنوان</th><th>اگر این شد</th><th>این کار را بکن</th><th>وضعیت</th><th>اجرا</th></tr>
      <?php foreach($autos as $a): 
        $trig=json_decode($a['trigger_config'],true); $act=json_decode($a['action_config'],true);
        $trigger_label=['new_product'=>'محصول جدید','new_post'=>'نوشته جدید','schedule'=>'زمان‌بندی','gsc_ctr_drop'=>'افت CTR'][$a['trigger_type']]??$a['trigger_type'];
        $action_label=['create_article_from_product'=>'مقاله + تلگرام','create_video_from_factory'=>'ساخت ویدیو','publish_telegram'=>'ارسال تلگرام','enhance_product'=>'بهبود محصول','trend_hunter'=>'ایده‌یاب','rewrite_meta'=>'بهبود سئو'][$a['action_type']]??$a['action_type'];
      ?>
      <tr>
        <td><b style="color:var(--nebula-text-strong)"><?php echo esc_html($a['title']);?></b><br><span style="font-size:.75rem; color:var(--nebula-muted)"><?php echo (int)$a['run_count'];?> بار اجرا • آخرین: <?php echo $a['last_run']? esc_html($a['last_run']) : 'هرگز';?></span></td>
        <td><span class="eaiw-badge purple"><?php echo esc_html($trigger_label);?></span></td>
        <td><span class="eaiw-badge cyan"><?php echo esc_html($action_label);?></span></td>
        <td>
          <div class="eaiw-switch <?php echo $a['is_active']?'on':'';?> eaiw-nexus-toggle" data-id="<?php echo $a['id'];?>" data-active="<?php echo $a['is_active']?0:1;?>" style="transform:scale(.9)"></div>
          <div style="font-size:.70rem; color:var(--nebula-muted); text-align:center"><?php echo $a['is_active']?'فعال':'خاموش';?></div>
        </td>
        <td>
          <button class="eaiw-btn eaiw-btn-primary eaiw-nexus-run" data-id="<?php echo $a['id'];?>" style="padding:5px 9px; font-size:.75rem">▶ اجرا</button>
          <button class="eaiw-btn eaiw-btn-ghost eaiw-nexus-delete" data-id="<?php echo $a['id'];?>" style="padding:5px 8px; font-size:.75rem">🗑️</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($autos)): ?>
        <tr><td colspan="5" style="text-align:center; color:var(--nebula-muted)">هنوز کاری نساختی — از سمت راست بساز</td></tr>
      <?php endif; ?>
    </table>
    <div id="eaiwNexusRunRes" style="margin-top:12px"></div>
  </div>

  <div class="eaiw-card eaiw-col-4">
    <h3><i>➕</i> ساخت اتوماسیون جدید — واقعی</h3>
    <label style="font-size:.82rem; color:var(--nebula-muted); font-weight:700">عنوان (مثلاً: محصول جدید → مقاله خودکار)</label>
    <input class="eaiw-input" id="eaiwNexusTitle" placeholder="عنوان را بنویس">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px">
      <div>
        <label style="font-size:.78rem; color:var(--nebula-muted); font-weight:700">اگر این اتفاق افتاد</label>
        <select class="eaiw-select" id="eaiwNexusTrigger">
          <option value='{"type":"new_product"}'>📦 محصول جدید منتشر شد</option>
          <option value='{"type":"new_post"}'>📝 نوشته جدید منتشر شد</option>
          <option value='{"type":"schedule","interval":"weekly"}'>🕐 هر هفته</option>
          <option value='{"type":"schedule","interval":"daily"}'>🕐 هر روز</option>
          <option value='{"type":"gsc_ctr_drop","threshold":2}'>📉 افت نرخ کلیک</option>
        </select>
      </div>
      <div>
        <label style="font-size:.78rem; color:var(--nebula-muted); font-weight:700">این کار را بکن</label>
        <select class="eaiw-select" id="eaiwNexusAction">
          <option value='{"type":"create_article_from_product"}'>📝 ساخت مقاله از محصول + تلگرام</option>
          <option value='{"type":"enhance_product"}'>✨ بهبود خودکار محصول</option>
          <option value='{"type":"create_video_from_factory"}'>🎬 ساخت ویدیو از آخرین کارخانه</option>
          <option value='{"type":"publish_telegram"}'>✈️ ارسال به تلگرام</option>
          <option value='{"type":"trend_hunter"}'>💡 ایده‌یاب (3 عنوان داغ)</option>
          <option value='{"type":"rewrite_meta"}'>🔍 بهبود سئو</option>
        </select>
      </div>
    </div>
    <button class="eaiw-btn eaiw-btn-primary" id="eaiwNexusCreate" style="margin-top:12px; width:100%">➕ ساخت اتوماسیون واقعی</button>
    <div style="margin-top:10px; font-size:.78rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:8px">
      💡 نکته: «محصول جدید → مقاله» یعنی همین که محصولی را منتشر کردی، خودکار یک مقاله کامل (1200 کلمه + 3 عکس) می‌سازد و پیش‌نویس می‌کند — حتی اگر تلگرام وصل باشد، همزمان می‌فرستد.
    </div>
  </div>

  <div class="eaiw-card eaiw-col-12">
    <h3><i>📜</i> آخرین اجراها — لاگ واقعی</h3>
    <?php if(empty($runs)): ?>
      <div style="text-align:center; padding:14px; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px dashed var(--nebula-border); border-radius:12px">هنوز اجرایی نبوده — یک اتوماسیون را «▶ اجرا» بزن تا لاگ اینجا بیاید</div>
    <?php else: ?>
      <table class="eaiw-table">
        <tr><th>زمان</th><th>اتوماسیون</th><th>وضعیت</th><th>زمان اجرا</th><th>نتیجه/خطا</th></tr>
        <?php foreach($runs as $r): ?>
          <tr>
            <td style="font-size:.82rem"><?php echo esc_html($r['created_at']);?></td>
            <td><b><?php echo esc_html($r['title'] ?? '—');?></b></td>
            <td><span class="eaiw-badge <?php echo $r['status']=='success'?'green':'red';?>"><?php echo $r['status']=='success'?'موفق':'ناموفق';?></span></td>
            <td><?php echo esc_html($r['elapsed']);?>ث</td>
            <td style="max-width:320px; font-size:.78rem; color:var(--nebula-muted); white-space:pre-wrap"><?php 
              if($r['status']=='success') echo esc_html(mb_substr($r['result']??'',0,180));
              else echo esc_html($r['error_text']??'');
            ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <div style="margin-top:10px; font-size:.82rem; color:var(--nebula-muted)">اجرای خودکار هر 15 دقیقه + هنگام انتشار محصول/نوشته — همه با لاگ و زمان دقیق</div>
  </div>
</div>
</div>

<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2600);
  }
  $(document).on('click','.eaiw-nexus-toggle', function(){
    const id=$(this).data('id'), active=$(this).data('active');
    $.post(EAIW.ajax, {action:'eaiw_nexus_toggle', id:id, active:active, _ajax_nonce:EAIW.nonce}, res=>{
      if(res.success) location.reload();
    });
  });
  $(document).on('click','.eaiw-nexus-run', function(){
    const btn=$(this), id=btn.data('id');
    btn.prop('disabled',true).text('در حال اجرا...');
    $.post(EAIW.ajax, {action:'eaiw_nexus_run', id:id, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('▶ اجرا');
      if(res.success){
        const d=res.data;
        if(d.ok) $('#eaiwNexusRunRes').html(`<div style="background:rgba(16,185,129,.08); border:1px solid #10b98133; border-radius:10px; padding:10px; font-size:.85rem">✅ موفق — ${d.elapsed}ث<br><pre style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:8px; padding:8px; font-size:.75rem; direction:ltr; text-align:left; margin-top:6px; max-height:160px; overflow:auto">${JSON.stringify(d.result,null,2)}</pre></div>`);
        else $('#eaiwNexusRunRes').html(`<div style="background:rgba(239,68,68,.08); border:1px solid #ef444433; border-radius:10px; padding:10px; font-size:.85rem">❌ ${d.error||'خطا'} — ${d.elapsed}ث</div>`);
        toast(d.ok?'اجرا شد':'خطا', d.ok);
        setTimeout(()=> location.reload(), 1200);
      } else toast(res.data, false);
    });
  });
  $(document).on('click','.eaiw-nexus-delete', function(){
    if(!confirm('حذف شود؟')) return;
    const id=$(this).data('id');
    $.post(EAIW.ajax, {action:'eaiw_nexus_delete', id:id, _ajax_nonce:EAIW.nonce}, res=>{ if(res.success) location.reload(); });
  });
  $('#eaiwNexusCreate').on('click', function(){
    const title=$('#eaiwNexusTitle').val();
    if(!title) return toast('عنوان را بنویس', false);
    const trigger=$('#eaiwNexusTrigger').val();
    const action=$('#eaiwNexusAction').val();
    const btn=$(this); btn.prop('disabled',true).text('در حال ساخت...');
    $.post(EAIW.ajax, {action:'eaiw_nexus_create', title:title, trigger:trigger, action:action, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('➕ ساخت اتوماسیون واقعی');
      if(res.success){ toast('ساخته شد'); location.reload(); }
      else toast(res.data, false);
    });
  });
});
</script>
