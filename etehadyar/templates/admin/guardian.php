<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
if(isset($_POST['eaiw_guardian_scan']) && check_admin_referer('eaiw_guardian')){
  $issues = EAIW_Guardian::scan();
  echo '<div class="notice notice-success"><p>✅ بررسی کامل انجام شد — '.count($issues).' مورد نیاز به توجه پیدا شد. جزئیات پایین را ببین.</p></div>';
}
$scan = EAIW_Guardian::last_scan();
$health = class_exists('EAIW_Health') ? EAIW_Health::check() : [];
// تست‌های نگهبان — توضیح دقیق
$checks = [
  'no_thumb'=>['title'=>'عکس شاخص','desc'=>'هر نوشته باید عکس شاخص داشته باشد — برای سئو و شبکه‌های اجتماعی','test'=>'بررسی 30 نوشته اخیر بدون _thumbnail_id'],
  'broken_link'=>['title'=>'لینک خراب','desc'=>'لینک‌های خالی (href="") یا 404 داخلی — تجربه کاربری را خراب می‌کند','test'=>'جستجوی href="" در 10 نوشته اخیر'],
  'schema'=>['title'=>'اسکیما و سئو','desc'=>'FAQ/Article Schema ناقص — گوگل ریچ‌ریزالت نمی‌دهد','test'=>'بررسی _yoast_wpseo_schema_article_type در 10 نوشته'],
  'php'=>['title'=>'نسخه PHP','desc'=>'PHP باید 7.4+ باشد — امنیت و سرعت','test'=>'version_compare(PHP_VERSION, 7.4)'],
  'ssl'=>['title'=>'HTTPS','desc'=>'سایت باید با SSL باشد — اعتماد کاربر و سئو','test'=>'is_ssl()'],
  'cron'=>['title'=>'زمان‌بندی (Cron)','desc'=>'WP-Cron باید فعال باشد — دستیاران و اتوماسیون با آن کار می‌کنند','test'=>'wp_next_scheduled(eaiw_agents_cron)'],
];
$issues_by_type = [];
foreach($scan['issues'] as $iss) $issues_by_type[$iss['type']] = $iss;
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🛡️</div><div><h1>نگهبان سایت — سلامت و امنیت (6.6 دقیق)</h1><p>هر امتحان چه بود، چرا مهم است، و سایتت پاس شد یا نه — کاملاً واقعی</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-8">
    <h3><i>🔍</i> نتیجه آخرین بررسی دقیق</h3>
    <div style="font-size:.85rem; color:var(--nebula-muted); margin:6px 0">زمان: <?php echo $scan['time']? human_time_diff($scan['time']).' پیش ('.date_i18n('Y/m/d H:i',$scan['time']).')' : 'هنوز بررسی نشده — دکمه پایین را بزن';?> • هر ساعت خودکار هم چک می‌شود</div>
    
    <div style="display:grid; gap:10px; margin-top:10px">
      <?php foreach($checks as $key=>$info): 
        $issue = $issues_by_type[$key] ?? null;
        $health_ok = null;
        if(in_array($key,['php','ssl','cron'])) $health_ok = $health[$key] ?? false;
        $is_problem = $issue || $health_ok===false;
        $status = $is_problem ? 'fail' : 'pass';
      ?>
        <div style="background:<?php echo $is_problem ? 'rgba(239,68,68,.06)' : 'rgba(16,185,129,.06)';?>; border:1px solid <?php echo $is_problem ? 'rgba(239,68,68,.15)' : 'rgba(16,185,129,.15)';?>; border-radius:12px; padding:12px; display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center">
          <div>
            <div style="display:flex; gap:8px; align-items:center">
              <span style="width:22px; height:22px; border-radius:50%; display:grid; place-items:center; background:<?php echo $is_problem?'#ef4444':'#10b981';?>; color:white; font-size:.75rem; font-weight:800"><?php echo $is_problem?'✕':'✓';?></span>
              <b style="color:var(--nebula-text-strong)"><?php echo esc_html($info['title']);?></b>
              <span class="eaiw-badge <?php echo $is_problem?'red':'green';?>" style="font-size:.68rem"><?php echo $is_problem?'نیاز به توجه':'پاس شد';?></span>
            </div>
            <div style="font-size:.82rem; color:var(--nebula-text); margin-top:4px"><?php echo esc_html($info['desc']);?></div>
            <div style="font-size:.75rem; color:var(--nebula-muted); margin-top:4px">امتحان: <?php echo esc_html($info['test']);?> — <?php if($issue) echo 'پیدا شد: '.(int)$issue['count'].' مورد • راه‌حل: '.esc_html($issue['fix']); else echo 'پاس شد — مشکلی نیست'; ?></div>
          </div>
          <div style="text-align:center">
            <?php if($is_problem): ?>
              <div style="font-size:.75rem; color:#ef4444; font-weight:700"><?php echo isset($issue['count']) ? (int)$issue['count'].' مورد' : 'مشکل';?></div>
            <?php else: ?>
              <div style="font-size:.75rem; color:#10b981; font-weight:700">✅</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="post" style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center">
      <?php wp_nonce_field('eaiw_guardian'); ?>
      <button name="eaiw_guardian_scan" class="eaiw-btn eaiw-btn-primary">🛡️ بررسی کامل الان — واقعی</button>
      <span style="font-size:.82rem; color:var(--nebula-muted)">نتیجه با زمان دقیق ذخیره می‌شود — هر 3 امتحان محتوا + 3 امتحان سیستمی</span>
    </form>
  </div>

  <div class="eaiw-card eaiw-col-4">
    <h3><i>✅</i> خلاصه سلامت — واقعی</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px">
      <?php
        $total = count($checks);
        $passed = 0;
        foreach($checks as $k=>$v){
          $iss = $issues_by_type[$k] ?? null;
          $hok = in_array($k,['php','ssl','cron']) ? ($health[$k] ?? false) : null;
          if(!$iss && $hok!==false) $passed++;
          if(!isset($hok) && !$iss) $passed++;
        }
        $pct = round(($passed/max(1,$total))*100);
      ?>
      <div style="grid-column:span 2; text-align:center; padding:12px; background:<?php echo $pct>70?'rgba(16,185,129,.08)':'rgba(245,158,11,.08)';?>; border:1px solid <?php echo $pct>70?'#10b98122':'#f59e0b22';?>; border-radius:12px">
        <div style="font-size:2rem; font-weight:900; color:<?php echo $pct>70?'#10b981':'#f59e0b';?>"><?php echo $pct;?>%</div>
        <div style="font-size:.82rem; color:var(--nebula-muted)">سلامت کلی — <?php echo $passed;?>/<?php echo $total;?> امتحان پاس شد</div>
        <div class="eaiw-progress" style="margin-top:8px"><i style="width:<?php echo $pct;?>%; background:<?php echo $pct>70?'linear-gradient(90deg,#10b981,#06b6d4)':'linear-gradient(90deg,#f59e0b,#ef4444)';?>"></i></div>
      </div>
      <div style="background:rgba(16,185,129,.06); border:1px solid #10b98122; border-radius:10px; padding:10px; text-align:center">
        <div style="font-size:1.2rem; color:#10b981">✅ <?php echo $passed;?></div><div style="font-size:.75rem; color:var(--nebula-muted)">پاس شده</div>
      </div>
      <div style="background:rgba(239,68,68,.06); border:1px solid #ef444422; border-radius:10px; padding:10px; text-align:center">
        <div style="font-size:1.2rem; color:#ef4444">✕ <?php echo $total-$passed;?></div><div style="font-size:.75rem; color:var(--nebula-muted)">نیاز به توجه</div>
      </div>
    </div>
    <div style="margin-top:12px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px; font-size:.82rem; color:var(--nebula-muted)">
      <b style="color:var(--nebula-text-strong)">هر امتحان چه بود؟</b><br>
      • عکس شاخص: 30 نوشته بدون تامبنیل<br>
      • لینک خراب: href="" در 10 نوشته<br>
      • اسکیما: Yoast Schema خالی<br>
      • PHP/SSL/Cron: چک سیستمی واقعی
    </div>
  </div>
</div>
</div>
