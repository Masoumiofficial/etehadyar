<?php defined('ABSPATH')||exit;
$stats = EAIW_Site_Brain::stats();
$theme = get_option('eaiw_theme','dark');
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🧠</div><div><h1>حافظه هوشمند سایت</h1><p>همه مقاله‌ها و محصولاتت رو به خاطر می‌سپاره — بعد هر سوالی بپرسی، دقیق‌ترین جواب رو میاره</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-8">
    <h3><i>🧠</i> حافظه رو فعال کن</h3>
    <p>با یک کلیک، تمام نوشته‌ها و محصولات به قطعه‌های کوچک تبدیل و ذخیره میشه. بعد هوش مصنوعی بدون اشتباه (بدون توهم) از همین حافظه جواب میده و لینک دقیق می‌ده.</p>
    <div style="display:flex; gap:10px; margin:12px 0; flex-wrap:wrap">
      <button class="eaiw-btn eaiw-btn-primary" id="eaiwBrainIndexBtn">⚡ فعال‌سازی حافظه برای کل سایت</button>
      <span class="eaiw-badge purple" id="eaiwBrainStatus">ذخیره شده: <?php echo esc_html($stats['count']);?> تکه • آخرین: <?php echo esc_html($stats['last_index']);?></span>
    </div>
    <div class="eaiw-progress" id="eaiwBrainProgress" style="margin:10px 0"><i></i></div>
    <div style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:12px; font-size:.85rem; color:var(--nebula-muted)">
      💡 بدون کلید هم کار می‌کند. اگر کلید OpenAI یا GapGPT رو در تنظیمات وارد کنی، دقتش 10 برابر می‌شود. جدول: <code>wp_eaiw_vectors</code>
    </div>
  </div>
  <div class="eaiw-card eaiw-col-4">
    <h3><i>🔍</i> امتحان کن — بپرس</h3>
    <input class="eaiw-input" id="eaiwBrainQ" placeholder="مثلاً: بهترین روش سئو چیست؟">
    <button class="eaiw-btn eaiw-btn-cyan" id="eaiwBrainSearchBtn" style="margin-top:10px; width:100%">جستجوی هوشمند</button>
    <div id="eaiwBrainResults" style="margin-top:12px; max-height:380px; overflow:auto"></div>
  </div>
  <div class="eaiw-card eaiw-col-12">
    <h3><i>💡</i> این حافظه کجا به درد می‌خوره؟</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:.88rem; color:var(--nebula-text)">
      <div style="background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:12px; padding:12px">1. میگی: «مقاله درباره قهوه بنویس»<br>2. حافظه 4 تا مرتبط‌ترین مطلب سایتت رو میاره<br>3. هوش مصنوعی با کمک همین‌ها مقاله بدون اشتباه می‌نویسه + لینک داخلی دقیق</div>
      <div style="background:rgba(109,40,255,.07); border:1px solid rgba(109,40,255,.15); border-radius:12px; padding:12px"><b style="color:var(--nebula-text-strong)">پایگاه دانش قبلی هم اضافه میشه</b><br>هر یادداشتی که در «پایگاه دانش» ذخیره کردی، به همین حافظه اضافه میشه — یعنی دانش تو = دانش سایت.</div>
    </div>
  </div>
</div>
</div>
