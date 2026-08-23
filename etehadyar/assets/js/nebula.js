jQuery(function($){
  // THEME — light/dark with localStorage (fix 6.0.1)
  const wrap = $('.eaiw-nebula-wrap');
  const savedTheme = localStorage.getItem('eaiw_theme') || (EAIW.theme || 'dark');
  function applyTheme(t){
    if(t==='light'){ wrap.addClass('eaiw-light'); $('#eaiwThemeLabel').text('حالت روشن'); $('#eaiwThemeSwitch').addClass('on').attr('title','تغییر به تیره'); }
    else { wrap.removeClass('eaiw-light'); $('#eaiwThemeLabel').text('حالت تیره'); $('#eaiwThemeSwitch').removeClass('on').attr('title','تغییر به روشن'); }
    localStorage.setItem('eaiw_theme', t);
    // sync to server optional
    if(EAIW.nonce) $.post(EAIW.ajax, {action:'eaiw_theme_save', theme:t, _ajax_nonce:EAIW.nonce});
  }
  applyTheme(savedTheme);
  $(document).on('click','#eaiwThemeSwitch, #eaiwThemeToggle', function(){
    const isLight = wrap.hasClass('eaiw-light');
    applyTheme(isLight ? 'dark' : 'light');
  });

  // Supernatural toggle kept as separate — but now it just toggles portal effect
  $(document).on('click','#eaiwSupernaturalSwitch', function(){
    const on = $(this).hasClass('on') ? 0 : 1;
    $(this).toggleClass('on', !!on);
    $('#eaiwSupernaturalLabel').text(on ? 'افکت ماورایی روشن' : 'افکت ساده');
    $.post(EAIW.ajax, {action:'eaiw_supernatural_toggle', enabled:on, _ajax_nonce:EAIW.nonce});
    if(on) wrap.removeClass('eaiw-no-effect'); else wrap.addClass('eaiw-no-effect');
  });

  // Portal — show only if <3 views and supernatural enabled
  const seen = parseInt(EAIW.portalSeen||0);
  const sup = parseInt(EAIW.supernatural||1);
  if (sup && seen < 3 && !window.location.search.includes('noportal')) {
    const portal = $(`
      <div class="eaiw-portal" id="eaiwPortal">
        <div class="eaiw-portal-card">
          <div class="eaiw-portal-logo"><div>◉</div></div>
          <h2 style="color:white; font-weight:900; font-size:1.6rem; margin:0">ورود به جهان ماورایی</h2>
          <p style="color:#C2C8E6; margin:6px 0 14px">EtehadWP — دستیار هوشمند سایت شما • v6.0.1</p>
          <div style="color:#DDD0FF; font-size:.9rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); border-radius:999px; padding:8px 14px; display:inline-flex; gap:8px; align-items:center; backdrop-filter:blur(8px)">
            <span style="width:8px; height:8px; background:#00e5ff; border-radius:50%; box-shadow:0 0 10px #00e5ff; display:inline-block"></span> ${EAIW.i18n.jarvisHi}
          </div>
          <div style="margin-top:16px"><button class="eaiw-btn eaiw-btn-primary" id="eaiwEnter">✨ ورود به اتاق فرمان</button> <button class="eaiw-btn eaiw-btn-ghost" id="eaiwSkip" style="background:rgba(255,255,255,.12); color:white; border-color:rgba(255,255,255,.2)">رد کردن</button></div>
          <div style="margin-top:10px; font-size:.75rem; color:#94A3B8">این پرده بعد از ۳ ورود خودکار محو می‌شود</div>
        </div>
      </div>
    `);
    $('body').append(portal);
    function enter(){
      portal.addClass('hide');
      $.post(EAIW.ajax, {action:'eaiw_portal_seen', _ajax_nonce:EAIW.nonce});
      setTimeout(()=> portal.remove(), 700);
    }
    $('#eaiwEnter, #eaiwSkip').on('click', enter);
    setTimeout(enter, 3000);
    // also allow ESC
    $(document).on('keydown', e=>{ if(e.key==='Escape') enter(); });
  }

  // Command Palette — Cmd+K / Ctrl+K
  const cmds = [
    {k:'خانه هوشمند', d:'اتاق فرمان اصلی', a:()=> location.href='admin.php?page=eaiw-nebula'},
    {k:'حافظه هوشمند', d:'جستجو و ایندکس کل سایت', a:()=> location.href='admin.php?page=eaiw-brain'},
    {k:'دستیاران خودکار', d:'مدیریت کارگران هوشمند', a:()=> location.href='admin.php?page=eaiw-agents'},
    {k:'کارخانه محتوا', d:'تبدیل یک مقاله به 7 خروجی', a:()=> location.href='admin.php?page=eaiw-factory'},
    {k:'تصویرساز', d:'ساخت عکس با هوش مصنوعی', a:()=> location.href='admin.php?page=eaiw-vision'},
    {k:'صفحه‌ساز', d:'ساخت لندینگ بدون کدنویسی', a:()=> location.href='admin.php?page=eaiw-architect'},
    {k:'فروش یار', d:'تقویت محصولات ووکامرس', a:()=> location.href='admin.php?page=eaiw-woo'},
    {k:'پیش‌بینی سئو', d:'پیش‌بینی افت و رشد رتبه', a:()=> location.href='admin.php?page=eaiw-oracle'},
    {k:'نگهبان سایت', d:'بررسی لینک و امنیت', a:()=> location.href='admin.php?page=eaiw-guardian'},
    {k:'پشتیبان هوشمند', d:'چت‌بات سایت', a:()=> location.href='admin.php?page=eaiw-soul'},
    {k:'اتوماسیون', d:'کارهای خودکار', a:()=> location.href='admin.php?page=eaiw-nexus'},
  ];
  function openCmd(){
    let box = $('#eaiwCmd');
    if(!box.length){
      box = $(`<div class="eaiw-command" id="eaiwCmd"><div class="eaiw-command-box"><input class="eaiw-command-input" placeholder="چی می‌خوای بسازیم؟ مثلاً: مقاله درباره قهوه اسپرسو" id="eaiwCmdInput"><div class="eaiw-command-list" id="eaiwCmdList"></div><div style="padding:8px 12px; font-size:.75rem; color:#64748B; border-top:1px solid #1e293b">💡 نکته: Enter بزن تا مستقیم بسازی — Esc برای خروج</div></div></div>`);
      $('body').append(box);
      box.on('click', e=>{ if(e.target===box[0]) box.removeClass('open'); });
      $('#eaiwCmdInput').on('input', renderCmd);
      $('#eaiwCmdInput').on('keydown', e=>{ if(e.key==='Escape') box.removeClass('open'); if(e.key==='Enter'){ const first=$('#eaiwCmdList .eaiw-command-item').first(); if(first.length) first.click(); } });
    }
    box.addClass('open');
    setTimeout(()=> $('#eaiwCmdInput').focus(), 50);
    renderCmd();
  }
  function renderCmd(){
    const q = ($('#eaiwCmdInput').val()||'').toLowerCase();
    const list = $('#eaiwCmdList'); list.empty();
    const filtered = cmds.filter(c=> !q || c.k.toLowerCase().includes(q) || c.d.includes(q));
    if(q && q.length>2){
      list.append(`<div class="eaiw-command-item" data-gen="${q.replace(/"/g,'&quot;')}" style="background:linear-gradient(90deg,#6d28ff18,#00e5ff14); border:1px solid #6d28ff33"><span>✨ ساخت سریع برای: <b>${$('<div>').text(q).html()}</b></span><span class="eaiw-badge cyan">Enter</span></div>`);
    }
    filtered.forEach(c=>{
      list.append(`<div class="eaiw-command-item" data-k="${c.k}"><span><b>${c.k}</b><br><small style="color:#94A3B8">${c.d}</small></span><span>↩</span></div>`);
    });
    if(filtered.length===0 && !q) list.append('<div style="padding:12px; color:#94A3B8; text-align:center">چیزی پیدا نشد — یک کلمه بنویس</div>');
    list.find('.eaiw-command-item').on('click', function(){
      const k=$(this).data('k'); const gen=$(this).data('gen');
      if(gen){ location.href='admin.php?page=eaiw-factory&prompt='+encodeURIComponent(gen); }
      else {
        const found = cmds.find(x=>x.k===k);
        if(found) found.a();
      }
      $('#eaiwCmd').removeClass('open');
    });
  }
  $(document).on('keydown', e=>{ if((e.metaKey||e.ctrlKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); openCmd(); }});
  $(document).on('click','#eaiwOpenCmd', openCmd);
});
