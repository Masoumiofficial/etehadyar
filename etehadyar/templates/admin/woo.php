<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$products = class_exists('WooCommerce') ? wc_get_products(['limit'=>6,'status'=>'publish']) : [];
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🛒</div><div><h1>فروش‌یار هوشمند</h1><p>محصولاتت رو جذاب‌تر کن — توضیح بهتر + سوال و جواب + مقایسه + عنوان سئو</p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>
<?php if (!class_exists('WooCommerce')): ?>
  <div class="eaiw-card eaiw-col-12" style="background:rgba(239,68,68,.08); border-color:rgba(239,68,68,.15)">ووکامرس نصب نیست — وقتی ووکامرس را نصب کنی، این بخش خودکار فعال می‌شود و محصولاتت را می‌آورد.</div>
<?php else: ?>
<div class="eaiw-grid">
  <?php foreach($products as $p): $id=$p->get_id(); $enhanced = get_post_meta($id,'_eaiw_godmode_enhanced',true); ?>
  <div class="eaiw-card eaiw-col-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px">
      <h3><i>📦</i> <?php echo esc_html($p->get_name());?></h3>
      <span class="eaiw-badge <?php echo $enhanced?'green':'purple';?>"><?php echo $enhanced?'بهبود یافته':'آماده بهبود';?></span>
    </div>
    <div style="font-size:.85rem; color:var(--nebula-muted)"><?php echo wp_trim_words($p->get_short_description()?:$p->get_description(), 18) ?: 'بدون توضیح — وقتشه جذابش کنیم!';?></div>
    <form method="post" style="margin-top:10px">
      <?php wp_nonce_field('eaiw_woo_'.$id); ?>
      <input type="hidden" name="eaiw_woo_id" value="<?php echo $id;?>">
      <button class="eaiw-btn eaiw-btn-primary" name="eaiw_woo_enhance" style="padding:7px 12px; font-size:.82rem">✨ جذاب‌سازی محصول</button>
      <a href="<?php echo get_edit_post_link($id);?>" class="eaiw-btn eaiw-btn-ghost" style="padding:7px 12px; font-size:.82rem">ویرایش</a>
    </form>
    <?php if($enhanced): $data=json_decode($enhanced,true); ?>
      <div style="margin-top:10px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px; font-size:.82rem"><b>عنوان سئو:</b> <?php echo esc_html($data['seo_title']??'');?><br><b>سوال:</b> <?php echo esc_html($data['faq'][0]['q']??'');?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if(empty($products)): ?>
    <div class="eaiw-card eaiw-col-12" style="text-align:center; color:var(--nebula-muted)">هنوز محصولی نداری — یک محصول بساز تا اینجا بهبودش بدهم.</div>
  <?php endif; ?>
</div>
<?php
  if(isset($_POST['eaiw_woo_enhance']) && isset($_POST['eaiw_woo_id']) && wp_verify_nonce($_POST['_wpnonce'],'eaiw_woo_'.intval($_POST['eaiw_woo_id']))){
    $res = EAIW_Woo_GodMode::enhance_product(intval($_POST['eaiw_woo_id']));
    if(!is_wp_error($res)) echo '<div class="notice notice-success"><p>محصول جذاب شد — توضیح کامل + سوال و جواب + عنوان سئو ذخیره شد. صفحه محصول را ببین.</p></div>';
  }
?>
<?php endif; ?>
</div>
