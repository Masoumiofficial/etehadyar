<?php defined('ABSPATH')||exit; $theme=get_option('eaiw_theme','dark'); ?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🧱</div><div><h1>صفحه‌ساز هوشمند — بدون کدنویسی</h1><p>بنویس چی می‌خوای، یک صفحه کامل و زیبا تحویل بگیر — آماده با ویرایشگر وردپرس</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>
<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-6">
    <h3><i>💬</i> چی بسازیم؟</h3>
    <textarea class="eaiw-textarea" id="eaiwArchitectBrief" rows="4" placeholder="مثلاً: یک صفحه فروش برای دوره فتوشاپ مقدماتی، مخصوص دانشجویان، قیمت ۹۹۰ هزار تومان، با ۳ ویژگی اصلی و دکمه خرید واضح"><?php echo esc_textarea($_GET['brief']??'');?></textarea>
    <button class="eaiw-btn eaiw-btn-primary" id="eaiwArchitectGen" style="margin-top:12px; width:100%">🧱 ساخت صفحه — همین الان</button>
    <div style="margin-top:10px; font-size:.82rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px">خروجی: یک برگه پیش‌نویس با ۳ بخش (سربرگ جذاب + ویژگی‌ها + دعوت به اقدام) — با ویرایشگر وردپرس یا المنتور قابل ویرایش.</div>
  </div>
  <div class="eaiw-card eaiw-col-6">
    <h3><i>👁️</i> نتیجه</h3>
    <div id="eaiwArchitectResult" style="min-height:200px; display:grid; place-items:center; color:var(--nebula-muted); text-align:center; font-size:.9rem; background:var(--nebula-input-bg); border:1px dashed var(--nebula-border); border-radius:12px; padding:16px">
      <div>توضیح را بنویس و «ساخت صفحه» را بزن<br><span style="font-size:.8rem">پیش‌نمایش و لینک ویرایش اینجا می‌آید</span></div>
    </div>
    <div style="margin-top:12px; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; font-size:.78rem; text-align:center">
      <span class="eaiw-pill">✨ سربرگ جذاب</span><span class="eaiw-pill">🔲 ۳ ویژگی</span><span class="eaiw-pill">🎯 دعوت به خرید</span>
    </div>
  </div>
</div>
</div>
