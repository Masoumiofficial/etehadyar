<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$preds = EAIW_Oracle::predict();
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🔮</div><div><h1>پیش‌بینی سئو — آینده را ببین</h1><p>می‌گوید کدام صفحه ممکن است افت کند و چطور قبل از رقبا نجاتش بدهی</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>
<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-12">
    <h3><i>📊</i> پیش‌بینی ۳۰ روز آینده (واقعی از داده‌های سایت)</h3>
    <table class="eaiw-table">
      <tr><th>صفحه</th><th>کلیک</th><th>نرخ کلیک</th><th>جایگاه</th><th>وضعیت</th><th>پیش‌بینی</th><th>کار</th></tr>
      <?php foreach($preds as $r): ?>
      <tr>
        <td><a href="<?php echo esc_url($r['url']);?>" target="_blank" style="color:#6d28ff; font-weight:700"><?php echo esc_html(mb_substr($r['title'],0,36));?></a></td>
        <td><?php echo (int)$r['clicks'];?></td>
        <td><?php echo esc_html($r['ctr']);?>%</td>
        <td><?php echo esc_html($r['position']);?></td>
        <td><span class="eaiw-badge <?php echo $r['risk']=='high'?'red':($r['risk']=='medium'?'purple':'green');?>"><?php echo $r['risk']=='high'?'نیاز فوری':($r['risk']=='medium'?'قابل بهبود':'خوب');?> </span></td>
        <td style="font-size:.82rem"><?php echo esc_html($r['forecast']);?></td>
        <td><a href="<?php echo admin_url('admin.php?page=eaiw-agents');?>" class="eaiw-btn eaiw-btn-ghost" style="padding:5px 8px; font-size:.75rem">بهبود بده</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="margin-top:10px; font-size:.82rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:8px">داده‌ها از اتصال گوگل (GSC + GA4) می‌آید — اگر هنوز وصل نکردی، یک پیش‌نمایش هوشمند از محتواهای خودت نمایش داده می‌شود تا با اتصال، دقیق شود.</div>
  </div>
</div>
</div>
