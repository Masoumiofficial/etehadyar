jQuery(function($){
  function toast(msg, ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-family:Vazirmatn; font-size:.9rem; box-shadow:0 8px 24px rgba(0,0,0,.3)">${msg}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2600);
  }
  $(document).on('click','.eaiw-agent-toggle', function(){
    const btn=$(this), key=btn.data('agent'), enabled=btn.hasClass('on')?0:1;
    btn.prop('disabled', true);
    $.post(EAIW.ajax, {action:'eaiw_agent_toggle', agent:key, enabled:enabled, _ajax_nonce:EAIW.nonce}, res=>{
      btn.toggleClass('on', !!enabled).find('span').text(enabled?'فعال':'غیرفعال');
      toast(enabled?'Agent فعال شد — هر ۱۵ دقیقه بیدار می‌شود':'Agent متوقف شد');
      btn.prop('disabled', false);
    }).fail(()=>{ toast('خطا در تغییر وضعیت', false); btn.prop('disabled', false); });
  });
  $(document).on('click','.eaiw-agent-run', function(){
    const btn=$(this), key=btn.data('agent');
    btn.prop('disabled',true).text('در حال اجرا...');
    $.post(EAIW.ajax, {action:'eaiw_agent_run', agent:key, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('▶ اجرای الان');
      if(res.success){
        const r=res.data;
        toast('اجرا شد: '+(r.message||'موفق'));
        // render result
        const box=$('#eaiwAgentResult-'+key);
        if(box.length) box.html(`<pre style="background:#0a0f1f; border:1px solid #1e293b; border-radius:12px; padding:12px; font-size:.82rem; white-space:pre-wrap; direction:ltr; text-align:left">${JSON.stringify(r, null, 2)}</pre>`).show();
      } else toast(res.data||'خطا', false);
    }).fail(()=>{ btn.prop('disabled',false).text('▶ اجرای الان'); toast('خطا', false); });
  });

  // Brain indexing
  let brainOffset=0;
  $(document).on('click','#eaiwBrainIndexBtn', function(){
    const btn=$(this); btn.prop('disabled',true).text('ایندکس ماورایی...');
    function step(){
      $.post(EAIW.ajax, {action:'eaiw_brain_index', offset:brainOffset, _ajax_nonce:EAIW.nonce}, res=>{
        if(res.success){
          brainOffset=res.data.offset;
          const pct = Math.min(100, Math.round((brainOffset / (res.data.total_estimate||100))*100));
          $('#eaiwBrainProgress i').css('width', pct+'%');
          $('#eaiwBrainStatus').text(`ایندکس شد: ${brainOffset} — باقی: ${res.data.has_more?'ادامه...':'پایان'}`);
          if(res.data.has_more) step();
          else { btn.prop('disabled',false).text('ایندکس مجدد'); toast('مغز سایت بیدار شد — '+brainOffset+' تکه'); }
        } else { btn.prop('disabled',false).text('ایندکس'); toast('خطا', false); }
      });
    }
    step();
  });
  $(document).on('click','#eaiwBrainSearchBtn', function(){
    const q=$('#eaiwBrainQ').val();
    if(!q) return;
    $.post(EAIW.ajax, {action:'eaiw_brain_search', q:q, _ajax_nonce:EAIW.nonce}, res=>{
      const box=$('#eaiwBrainResults'); box.empty();
      if(res.success && res.data.length){
        res.data.forEach(r=>{
          box.append(`<div style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); border-radius:12px; padding:12px; margin:8px 0"><div style="display:flex; justify-content:space-between"><b>${r.title}</b><span class="eaiw-badge cyan">${r.score}</span></div><div style="font-size:.85rem; color:#9aa0c0">${r.snippet}</div><div style="margin-top:6px"><a href="${r.edit_url}" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:4px 8px; font-size:.75rem">ویرایش</a> <a href="${r.url}" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:4px 8px; font-size:.75rem">نمایش</a></div></div>`);
        });
      } else box.html('<div style="color:#9aa0c0">نتیجه‌ای یافت نشد — اول ایندکس کن</div>');
    });
  });

  // Vision — real
  $(document).on('click','#eaiwVisionGen', function(){
    const btn=$(this), prompt=$('#eaiwVisionPrompt').val(), style=$('#eaiwVisionStyle').val(), size=$('#eaiwVisionSize').val()||'1280x720';
    if(!prompt) return toast('لطفاً توضیح تصویر را بنویس', false);
    if(prompt.length<8) return toast('توضیح خیلی کوتاه است', false);
    btn.prop('disabled',true).text('در حال ساخت... ۳۰ ثانیه صبر کن');
    $.post(EAIW.ajax, {action:'eaiw_vision_generate', prompt:prompt, style:style, size:size, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('✨ ساخت تصویر — همین الان');
      const box=$('#eaiwVisionResult');
      if(res.success){
        const d=res.data;
        const badge = d.mode==='openai' ? '<span class="eaiw-badge green">واقعی AI</span>' : '<span class="eaiw-badge purple">موقت باکیفیت</span>';
        box.html(`<div style="text-align:center"><div style="margin-bottom:8px">${badge}</div><img src="${d.url}" style="max-width:100%; border-radius:14px; border:1px solid var(--nebula-border); box-shadow:0 4px 16px rgba(0,0,0,.15)"><div style="margin-top:8px; font-size:.85rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:8px">${d.note||''}</div><div style="margin-top:10px; display:flex; gap:8px; justify-content:center"><a href="${d.url}" target="_blank" class="eaiw-btn eaiw-btn-primary">⬇️ دانلود</a><a href="${EAIW.ajax.replace('admin-ajax.php','upload.php')}" target="_blank" class="eaiw-btn eaiw-btn-ghost">کتابخانه →</a></div></div>`);
        toast(d.mode==='openai'?'تصویر واقعی ساخته شد ✨':'تصویر موقت ساخته شد — برای واقعی، کلید را وارد کن');
      } else toast(res.data||'خطا در ساخت', false);
    }).fail(()=>{ btn.prop('disabled',false).text('✨ ساخت تصویر — همین الان'); toast('خطا در ارتباط', false); });
  });

  // Architect
  $(document).on('click','#eaiwArchitectGen', function(){
    const btn=$(this), brief=$('#eaiwArchitectBrief').val();
    if(!brief) return toast('خلاصه را بنویس', false);
    btn.prop('disabled',true).text('در حال معماری...');
    $.post(EAIW.ajax, {action:'eaiw_architect_generate', brief:brief, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('🏗️ ساخت صفحه ماورایی');
      if(res.success){
        $('#eaiwArchitectResult').html(`<div style="background:rgba(16,185,129,.08); border:1px solid #10b98133; border-radius:14px; padding:14px">صفحه ساخته شد — <a href="${res.data.edit_url}" target="_blank" class="eaiw-btn eaiw-btn-primary" style="padding:6px 12px">ویرایش برگه</a> <a href="${res.data.preview_url}" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 12px">پیش‌نمایش</a></div>`);
        toast('صفحه ماورایی آماده است');
      } else toast(res.data||'خطا', false);
    });
  });
});
