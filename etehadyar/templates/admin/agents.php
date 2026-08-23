<?php defined('ABSPATH')||exit;
$agents = EAIW_Agent_Manager::all();
$theme = get_option('eaiw_theme','dark');
$friendly = [
  'seo_watcher'=>['نام'=>'بهبود سئو','آیکون'=>'📈','توضیح'=>'رتبه و کلیک‌ها رو هر روز چک می‌کند، اگر کلیک کم شد عنوان و توضیحات رو بهتر می‌کند','زمان'=>'هر ۱۵ دقیقه'],
  'gardener'=>['نام'=>'بروزرسان محتوا','آیکون'=>'🌱','توضیح'=>'مقاله‌های قدیمی (۶ ماه قبل) یا کوتاه رو پیدا می‌کند و می‌گوید کدام را بروز کنی','زمان'=>'هر ۱ ساعت'],
  'link_weaver'=>['نام'=>'لینک‌ساز هوشمند','آیکون'=>'🔗','توضیح'=>'بین مقاله‌هات لینک مرتبط و دقیق می‌سازد — با کمک حافظه هوشمند','زمان'=>'هر ۳۰ دقیقه'],
  'trend_hunter'=>['نام'=>'ایده‌یاب','آیکون'=>'💡','توضیح'=>'موضوعات داغ و پرجستجو رو پیدا می‌کند و ۳ تا عنوان آماده پیشنهاد می‌دهد','زمان'=>'هر ۲ ساعت'],
];
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🤖</div><div><h1>دستیاران خودکار — کارگران نامرئی شما</h1><p>روشن کن و بگذار خودشان کار کنند — فقط نتیجه را ببین</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <?php foreach($agents as $ag): $key=$ag['agent_key']; $fr=$friendly[$key]??['نام'=>$ag['title'],'آیکون'=>'🤖','توضیح'=>$ag['config']['description']??'','زمان'=>'']; $res=$ag['last_result']; ?>
  <div class="eaiw-card eaiw-col-6">
    <div style="display:flex; justify-content:space-between; align-items:start; gap:10px">
      <h3><i><?php echo $fr['آیکون'];?></i> <?php echo esc_html($fr['نام']);?></h3>
      <div class="eaiw-switch <?php echo $ag['is_enabled']?'on':'';?> eaiw-agent-toggle" data-agent="<?php echo esc_attr($key);?>" title="روشن/خاموش"></div>
    </div>
    <p><?php echo esc_html($fr['توضیح']); ?> — <b><?php echo esc_html($fr['زمان']);?></b></p>
    <div style="display:flex; gap:8px; margin:10px 0; flex-wrap:wrap">
      <button class="eaiw-btn eaiw-btn-primary eaiw-agent-run" data-agent="<?php echo esc_attr($key);?>">▶ همین الان اجرا کن</button>
      <span class="eaiw-badge <?php echo $ag['is_enabled']?'green':'red';?>"><?php echo $ag['is_enabled']?'فعال':'خاموش';?></span>
      <span class="eaiw-badge purple">اجرا شده: <?php echo (int)$ag['run_count'];?> بار</span>
    </div>
    <div style="font-size:.78rem; color:var(--nebula-muted)">آخرین اجرا: <?php echo $ag['last_run']? esc_html($ag['last_run']) : 'هنوز اجرا نشده';?> • بعدی: <?php echo esc_html($ag['next_run']??'—');?></div>
    <div id="eaiwAgentResult-<?php echo esc_attr($key);?>" style="margin-top:10px; display:<?php echo $res?'block':'none';?>"><?php if($res): echo '<pre style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:12px; font-size:.78rem; white-space:pre-wrap; direction:ltr; text-align:left; color:var(--nebula-text)">'.esc_html(wp_json_encode($res, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)).'</pre>'; endif; ?></div>
  </div>
  <?php endforeach; ?>

  <div class="eaiw-card eaiw-col-12" style="background:linear-gradient(90deg,rgba(109,40,255,.06),rgba(0,229,255,.04)); border-color:rgba(109,40,255,.12)">
    <h3><i>⚙️</i> این دستیارها چطور کار می‌کنند؟</h3>
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; font-size:.85rem; color:var(--nebula-text)">
      <div style="background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:10px; padding:10px"><b>بهبود سئو</b><br>صفحه‌ای که زیاد دیده می‌شود ولی کم کلیک می‌خورد را پیدا و عنوانش را بهتر می‌کند</div>
      <div style="background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:10px; padding:10px"><b>بروزرسان</b><br>مقاله‌های قدیمی یا کوتاه را لیست می‌کند تا تازه‌شان کنی</div>
      <div style="background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:10px; padding:10px"><b>لینک‌ساز</b><br>از حافظه هوشمند ۳ لینک دقیق و مرتبط پیشنهاد می‌دهد</div>
      <div style="background:rgba(255,255,255,.04); border:1px solid var(--nebula-border); border-radius:10px; padding:10px"><b>ایده‌یاب</b><br>از گوگل و جستجوها ۳ تا عنوان داغ و پرطرفدار می‌سازد</div>
    </div>
    <div style="margin-top:10px; font-size:.82rem; color:var(--nebula-muted)">زمان‌بندی: هر ۱۵ دقیقه به‌صورت خودکار — با <code>wp_cron</code> واقعی وردپرس، قابل اعتماد و قابل پیگیری</div>
  </div>
</div>
</div>
