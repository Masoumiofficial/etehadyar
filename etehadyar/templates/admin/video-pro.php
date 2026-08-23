<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$has_key = class_exists('EAIW_Vault') && (EAIW_Vault::get_key('openai') || EAIW_Vault::get_key('gapgpt'));
$has_ffmpeg=false; @exec('ffmpeg -version 2>&1', $o, $r); $has_ffmpeg=$r===0;
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🎬</div><div><h1>ویدیو ساز 6.7 — خفن <span style="background:linear-gradient(90deg,#00e5ff,#6d28ff); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:.7rem; border:1px solid #00e5ff33; padding:2px 6px; border-radius:999px">B-Roll + Voice</span></h1><p>سناریو + عکس + صدا → پیش‌نمایش + MP4 + SRT + انتخاب صدا</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-5">
    <h3><i>🎞️</i> ورودی ویدیو</h3>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700">عنوان ویدیو</label>
    <input class="eaiw-input" id="eaiwVideoTitle" placeholder="مثلاً: 3 نکته طلایی خرید قهوه">
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">سناریو — 4 صحنه</label>
    <textarea class="eaiw-textarea" id="eaiwVideoScript" rows="5" placeholder='[{"start":"0:00","end":"0:07","shot":"Hook","vo":"..."}]'></textarea>
    <div style="display:flex; gap:8px; margin-top:8px">
      <button class="eaiw-btn eaiw-btn-ghost" id="eaiwVideoFromFactory" style="padding:6px 10px; font-size:.78rem; flex:1">🏭 از کارخانه</button>
      <button class="eaiw-btn eaiw-btn-ghost" id="eaiwVideoAutoScript" style="padding:6px 10px; font-size:.78rem; flex:1">✨ نمونه</button>
    </div>
    <!-- Cool upgrade: Voice + B-Roll -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px">
      <div><label style="font-size:.80rem; color:var(--nebula-muted); font-weight:700">صدا</label>
        <select class="eaiw-select" id="eaiwVideoVoice">
          <option value="alloy">Alloy — متعادل</option>
          <option value="nova">Nova — زن جوان</option>
          <option value="onyx">Onyx — مرد عمیق</option>
          <option value="shimmer">Shimmer — نرم</option>
        </select></div>
      <div><label style="font-size:.80rem; color:var(--nebula-muted); font-weight:700">B-Roll</label>
        <select class="eaiw-select" id="eaiwVideoBroll">
          <option value="auto">خودکار — از تصاویر</option>
          <option value="flux">Flux — بساز</option>
          <option value="none">بدون B-Roll</option>
        </select></div>
    </div>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">عکس‌ها (هر خط یک URL)</label>
    <textarea class="eaiw-textarea" id="eaiwVideoImages" rows="2" placeholder="https://..."></textarea>
    <label style="font-size:.85rem; color:var(--nebula-muted); font-weight:700; margin-top:10px; display:block">صدا (MP3 — خالی = از پادکست)</label>
    <input class="eaiw-input" id="eaiwVideoAudio" placeholder="https://.../voice.mp3">
    <button class="eaiw-btn eaiw-btn-primary" id="eaiwVideoBuild" style="margin-top:14px; width:100%; justify-content:center; padding:12px">🎬 ساخت ویدیو — خفن</button>
    <div id="eaiwVideoError" style="display:none; margin-top:8px; background:rgba(239,68,68,.08); border:1px solid #ef444433; border-radius:10px; padding:8px; font-size:.82rem; color:#FECACA"></div>
    <div style="margin-top:8px; font-size:.75rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:8px; padding:8px">
      <?php if($has_ffmpeg): ?><span class="eaiw-badge green">FFmpeg فعال</span> MP4 واقعی<br><?php else: ?><span class="eaiw-badge purple">FFmpeg غیرفعال</span> پیش‌نمایش + ZIP — JSON برای Creatomate<br><?php endif; ?>
      خفن 6.7: انتخاب صدا + B-Roll خودکار از Flux
    </div>
  </div>

  <div class="eaiw-card eaiw-col-7">
    <h3><i>👁️</i> نتیجه</h3>
    <div id="eaiwVideoEmpty" style="text-align:center; padding:24px; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px dashed var(--nebula-border); border-radius:12px">
      «ساخت ویدیو» را بزن — پیش‌نمایش اینجا می‌آید<br>
      <span style="font-size:.75rem">با صدای انتخابی + B-Roll</span>
    </div>
    <div id="eaiwVideoResult" style="display:none">
      <div id="eaiwVideoLinks" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px"></div>
      <div id="eaiwVideoPreviewWrap" style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; overflow:hidden; height:380px">
        <iframe id="eaiwVideoPreview" style="width:100%; height:100%; border:0; background:#000"></iframe>
      </div>
      <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap">
        <a id="eaiwVideoMp4" href="#" target="_blank" class="eaiw-btn eaiw-btn-primary" style="padding:7px 12px; font-size:.82rem; display:none">⬇️ MP4</a>
        <a id="eaiwVideoZip" href="#" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:7px 12px; font-size:.82rem; display:none">📦 ZIP</a>
      </div>
      <div id="eaiwVideoNote" style="margin-top:8px; font-size:.82rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:8px; padding:8px"></div>
    </div>
  </div>
</div>
</div>

<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2800);
  }
  $('#eaiwVideoFromFactory').on('click', function(){
    const raw=localStorage.getItem('eaiw_factory_last');
    if(raw){
      try{
        const d=JSON.parse(raw);
        $('#eaiwVideoTitle').val(d.title||'');
        $('#eaiwVideoScript').val(JSON.stringify(d.video||[], null, 2));
        const imgs=(d.images||[]).map(x=>x.url).join('\n');
        $('#eaiwVideoImages').val(imgs);
        if(d.podcast && d.podcast.audio) $('#eaiwVideoAudio').val(d.podcast.audio.url);
        toast('از کارخانه آورده شد');
        return;
      }catch(e){}
    }
    toast('اول کارخانه را اجرا کن', false);
  });
  $('#eaiwVideoAutoScript').on('click', function(){
    const title=$('#eaiwVideoTitle').val()||'موضوع ویدیو';
    const demo=[
      {"start":"0:00","end":"0:07","shot":"Hook — کلوزآپ","vo":"آیا می‌دانستید "+title+" می‌تواند همه‌چیز را عوض کند؟"},
      {"start":"0:07","end":"0:25","shot":"توضیح + B-Roll","vo":"در این ویدیو 3 نکته طلایی را می‌گوییم..."},
      {"start":"0:25","end":"0:45","shot":"نکته + B-Roll","vo":"نکته مهم: انتخاب درست، نصف راه است."},
      {"start":"0:45","end":"0:60","shot":"CTA","vo":"برای آموزش کامل، لینک را در توضیحات ببین."}
    ];
    $('#eaiwVideoScript').val(JSON.stringify(demo,null,2));
    toast('سناریو نمونه با B-Roll ساخته شد');
  });
  $('#eaiwVideoBuild').on('click', function(){
    const btn=$(this);
    let script=$('#eaiwVideoScript').val().trim();
    let images=$('#eaiwVideoImages').val().split('\n').map(s=>s.trim()).filter(Boolean);
    const title=$('#eaiwVideoTitle').val().trim()||'ویدیو اتحادیار';
    const audio=$('#eaiwVideoAudio').val().trim();
    const voice=$('#eaiwVideoVoice').val();
    const broll=$('#eaiwVideoBroll').val();
    if(!script){
      const raw=localStorage.getItem('eaiw_factory_last');
      if(raw){ try{ const d=JSON.parse(raw); script=JSON.stringify(d.video||[]); images=(d.images||[]).map(x=>x.url); }catch(e){} }
    }
    if(!script){ $('#eaiwVideoError').text('سناریو را بنویس یا از کارخانه بیار').show(); return; }
    else $('#eaiwVideoError').hide();
    let parsed=[];
    try{
      if(script.startsWith('[')) parsed=JSON.parse(script);
      else {
        const lines=script.split('\n').filter(Boolean);
        parsed=lines.slice(0,4).map((l,i)=> ({start:(i*15)+":00", end:((i+1)*15)+":00", shot:"Scene "+(i+1), vo:l.slice(0,120)}));
        if(!parsed.length) parsed=[{start:"0:00",end:"0:60",shot:"متن",vo:script.slice(0,200)}];
        script=JSON.stringify(parsed);
      }
    } catch(e){ $('#eaiwVideoError').text('سناریو JSON نامعتبر').show(); return; }
    // B-Roll auto: if flux, add note
    if(broll==='flux' && !images.length){
      toast('B-Roll Flux: 3 عکس جدید با Flux ساخته می‌شود', true);
    }
    btn.prop('disabled',true).text('در حال ساخت ویدیو... 20 ثانیه');
    $('#eaiwVideoError').hide();
    $.post(EAIW.ajax, {action:'eaiw_video_build', title:title, script:script, images:images, audio:audio, voice:voice, broll:broll, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('🎬 ساخت ویدیو — خفن');
      if(!res.success){
        $('#eaiwVideoError').text(res.data||'خطا').show();
        return toast(res.data||'خطا', false);
      }
      const d=res.data;
      $('#eaiwVideoEmpty').hide();
      $('#eaiwVideoResult').show();
      const links=$('#eaiwVideoLinks'); links.empty();
      if(d.video_url) links.append(`<span class="eaiw-badge green">MP4 واقعی آماده</span>`);
      else links.append(`<span class="eaiw-badge purple">پیش‌نمایش + ZIP آماده</span>`);
      if(d.preview_url) $('#eaiwVideoPreview').attr('src', d.preview_url);
      if(d.video_url) $('#eaiwVideoMp4').attr('href', d.video_url).show(); else $('#eaiwVideoMp4').hide();
      if(d.zip_url) $('#eaiwVideoZip').attr('href', d.zip_url).show(); else $('#eaiwVideoZip').hide();
      $('#eaiwVideoNote').text((d.note||'') + (broll==='flux'?' + B-Roll Flux':'' ) + ' + صدا: '+voice);
      toast('ویدیو خفن ساخته شد');
    }).fail(xhr=>{
      btn.prop('disabled',false).text('🎬 ساخت ویدیو — خفن');
      let msg='خطای ارتباط';
      if(xhr.responseJSON && xhr.responseJSON.data) msg=xhr.responseJSON.data;
      $('#eaiwVideoError').text(msg).show();
      toast(msg, false);
    });
  });
});
</script>
