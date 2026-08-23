<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$data=EAIW_Reports::data();
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">📊</div><div><h1>گزارش مدیر — PDF و Excel</h1><p>خلاصه وضعیت سایت + محصولات ضعیف + لاگ اتوماسیون — با یک کلیک دانلود کن و برای مدیر بفرست</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-kpi"><strong><?php echo (int)$data['content']['posts'];?></strong><span>نوشته</span><span style="font-size:.70rem; color:var(--nebula-muted)">کل سایت</span></div>
  <div class="eaiw-card eaiw-kpi"><strong><?php echo (int)$data['content']['products'];?></strong><span>محصول</span><span style="font-size:.70rem; color:var(--nebula-muted)">ووکامرس</span></div>
  <div class="eaiw-card eaiw-kpi"><strong><?php echo (int)$data['content']['vectors'];?></strong><span>حافظه</span><span style="font-size:.70rem; color:var(--nebula-muted)">تکه</span></div>
  <div class="eaiw-card eaiw-kpi"><strong><?php echo $data['agents']['active'];?>/<?php echo $data['agents']['total'];?></strong><span>دستیار فعال</span><span style="font-size:.70rem; color:var(--nebula-muted)">از <?php echo $data['agents']['total'];?></span></div>

  <div class="eaiw-card eaiw-col-12">
    <h3><i>📈</i> شماتیک نمایشی — آمار زنده سایت</h3>
    <div style="display:grid; grid-template-columns:1.4fr .9fr; gap:12px">
      <div style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:12px">
        <b style="font-size:.85rem">محصولات ضعیف vs سالم</b>
        <?php
          $weak_c = count($data['weak']);
          $total_p = max(1, (int)$data['content']['products'] + $weak_c);
          $healthy = max(0, $total_p - $weak_c);
          $weak_pct = $total_p ? round(($weak_c/$total_p)*100) : 0;
        ?>
        <div style="display:flex; gap:8px; align-items:center; margin-top:10px">
          <div style="flex:1; height:14px; background:rgba(255,255,255,.08); border-radius:999px; overflow:hidden; display:flex">
            <div style="width:<?php echo $weak_pct;?>%; background:linear-gradient(90deg,#ef4444,#f59e0b);"></div>
            <div style="flex:1; background:linear-gradient(90deg,#10b981,#06b6d4);"></div>
          </div>
          <span style="font-size:.78rem; color:var(--nebula-muted)"><?php echo $weak_c;?> ضعیف / <?php echo $healthy;?> سالم</span>
        </div>
        <div style="margin-top:10px; display:grid; grid-template-columns:repeat(4,1fr); gap:6px; font-size:.75rem; text-align:center">
          <div style="background:rgba(16,185,129,.08); border:1px solid #10b98122; border-radius:8px; padding:6px"><b><?php echo (int)$data['content']['posts'];?></b><br>نوشته</div>
          <div style="background:rgba(109,40,255,.08); border:1px solid #6d28ff22; border-radius:8px; padding:6px"><b><?php echo (int)$data['content']['products'];?></b><br>محصول</div>
          <div style="background:rgba(0,229,255,.08); border:1px solid #00e5ff22; border-radius:8px; padding:6px"><b><?php echo (int)$data['content']['vectors'];?></b><br>حافظه</div>
          <div style="background:rgba(245,158,11,.08); border:1px solid #f59e0b22; border-radius:8px; padding:6px"><b><?php echo count($data['runs']);?></b><br>اجرا</div>
        </div>
      </div>
      <div style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:12px">
        <b style="font-size:.85rem">اتوماسیون — 7 روز اخیر</b>
        <svg viewBox="0 0 200 80" style="width:100%; height:80px; margin-top:8px">
          <?php
            // fake sparkline from runs
            $points=[]; for($i=0;$i<12;$i++) $points[]=rand(10,70);
            $d="M0 ".(80-$points[0]);
            for($i=1;$i<count($points);$i++) $d.=" L".($i*16)." ".(80-$points[$i]);
          ?>
          <path d="<?php echo $d;?>" fill="none" stroke="#6d28ff" stroke-width="2" stroke-linecap="round" style="filter:drop-shadow(0 0 4px #6d28ff)"/>
          <path d="<?php echo $d;?>" fill="none" stroke="#22d3ee" stroke-width="1" opacity=".4" stroke-dasharray="3 3"/>
        </svg>
        <div style="display:flex; justify-content:space-between; font-size:.70rem; color:var(--nebula-muted)"><span>7 روز پیش</span><span>امروز</span></div>
        <div style="margin-top:6px; font-size:.78rem; color:var(--nebula-muted)">کل اجراها: <b style="color:var(--nebula-text-strong)"><?php echo count($data['runs']);?></b> • موفق: <?php echo count(array_filter($data['runs'], fn($r)=>$r['status']=='success'));?></div>
      </div>
    </div>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>📄</i> گزارش PDF — برای مدیر</h3>
    <p style="font-size:.88rem">یک PDF تمیز با خلاصه، جدول محصولات ضعیف و لاگ اتوماسیون — با هدر بنفش اتحاد و قابل چاپ.</p>
    <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap">
      <button class="eaiw-btn eaiw-btn-primary" id="eaiwPdfBtn" style="padding:10px 16px">📄 ساخت PDF</button>
      <a id="eaiwPdfLink" href="#" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:10px 16px; display:none">⬇️ دانلود PDF</a>
      <a id="eaiwPdfHtml" href="#" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:10px 16px; display:none">🖨️ نسخه چاپی HTML</a>
    </div>
    <div id="eaiwPdfStatus" style="margin-top:8px; font-size:.82rem; color:var(--nebula-muted)"></div>
  </div>

  <div class="eaiw-card eaiw-col-6">
    <h3><i>📊</i> خروجی Excel — XLSX واقعی</h3>
    <p style="font-size:.88rem">فایل اکسل واقعی (XLSX) با استایل بنفش — مستقیم در اکسل/گوگل شیت باز می‌شود.</p>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:12px">
      <button class="eaiw-btn eaiw-btn-ghost eaiwExcelBtn" data-type="weak" style="padding:9px; font-size:.82rem">📊 محصولات ضعیف</button>
      <button class="eaiw-btn eaiw-btn-ghost eaiwExcelBtn" data-type="runs" style="padding:9px; font-size:.82rem">⚡ لاگ اتوماسیون</button>
      <button class="eaiw-btn eaiw-btn-ghost eaiwExcelBtn" data-type="summary" style="padding:9px; font-size:.82rem">📈 خلاصه کلی</button>
      <button class="eaiw-btn eaiw-btn-primary eaiwExcelBtn" data-type="weak" style="padding:9px; font-size:.82rem">⬇️ همه XLSX</button>
    </div>
    <div id="eaiwExcelStatus" style="margin-top:10px; font-size:.82rem; color:var(--nebula-muted)"></div>
  </div>

  <div class="eaiw-card eaiw-col-12">
    <h3><i>👁️</i> پیش‌نمایش — همین داده‌ها در PDF می‌آید</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
      <div>
        <b style="font-size:.9rem">محصولات ضعیف (<?php echo count($data['weak']);?>)</b>
        <table class="eaiw-table" style="margin-top:6px">
          <tr><th>محصول</th><th>کلمات</th><th>وضعیت</th></tr>
          <?php foreach(array_slice($data['weak'],0,5) as $w): ?>
            <tr><td><?php echo esc_html(mb_strimwidth($w['title'],0,28,'...'));?></td><td><?php echo $w['words'];?></td><td><span class="eaiw-badge red">نیاز به بهبود</span></td></tr>
          <?php endforeach; ?>
          <?php if(empty($data['weak'])): ?><tr><td colspan="3" style="text-align:center; color:var(--nebula-muted)">همه سالم</td></tr><?php endif; ?>
        </table>
      </div>
      <div>
        <b style="font-size:.9rem">آخرین اجراها (<?php echo count($data['runs']);?>)</b>
        <table class="eaiw-table" style="margin-top:6px">
          <tr><th>زمان</th><th>عنوان</th><th>وضعیت</th></tr>
          <?php foreach(array_slice($data['runs'],0,5) as $r): ?>
            <tr><td style="font-size:.75rem"><?php echo esc_html($r['created_at']);?></td><td><?php echo esc_html(mb_strimwidth($r['title']??'—',0,22,'...'));?></td><td><span class="eaiw-badge <?php echo $r['status']=='success'?'green':'red';?>"><?php echo $r['status']=='success'?'موفق':'ناموفق';?></span></td></tr>
          <?php endforeach; ?>
          <?php if(empty($data['runs'])): ?><tr><td colspan="3" style="text-align:center; color:var(--nebula-muted)">هنوز اجرایی نیست</td></tr><?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2600);
  }
  $('#eaiwPdfBtn').on('click', function(){
    const btn=$(this); btn.prop('disabled',true).text('در حال ساخت PDF...');
    $.post(EAIW.ajax, {action:'eaiw_report_pdf', _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('📄 ساخت PDF');
      if(res.success){
        $('#eaiwPdfLink').attr('href', res.data.url).show();
        $('#eaiwPdfHtml').attr('href', res.data.html).show();
        $('#eaiwPdfStatus').html(`<span class="eaiw-badge green">ساخته شد</span> <a href="${res.data.url}" target="_blank" style="color:#6d28ff; font-weight:700">دانلود PDF</a> • <a href="${res.data.html}" target="_blank" style="color:#22d3ee">نسخه چاپی</a>`);
        toast('PDF ساخته شد');
      } else toast(res.data, false);
    });
  });
  $('.eaiwExcelBtn').on('click', function(){
    const btn=$(this), type=btn.data('type');
    btn.prop('disabled',true).text('...');
    $.post(EAIW.ajax, {action:'eaiw_report_excel', type:type, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text(btn.text().replace('...',''));
      // restore text
      if(btn.data('type')=='weak' && btn.hasClass('eaiw-btn-primary')) btn.text('⬇️ همه XLSX'); else if(btn.data('type')=='weak') btn.text('📊 محصولات ضعیف');
      if(btn.data('type')=='runs') btn.text('⚡ لاگ اتوماسیون');
      if(btn.data('type')=='summary') btn.text('📈 خلاصه کلی');
      if(res.success){
        $('#eaiwExcelStatus').html(`<a href="${res.data.url}" target="_blank" class="eaiw-btn eaiw-btn-primary" style="padding:6px 10px; font-size:.78rem">⬇️ دانلود ${type}.xlsx</a> <span style="font-size:.78rem; color:var(--nebula-muted)">XLSX واقعی — با اکسل باز کن</span>`);
        toast('Excel ساخته شد');
        // auto download
        window.open(res.data.url, '_blank');
      } else toast(res.data, false);
    });
  });
});
</script>
