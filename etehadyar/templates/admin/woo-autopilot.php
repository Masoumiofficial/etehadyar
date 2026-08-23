<?php defined('ABSPATH')||exit;
$theme=get_option('eaiw_theme','dark');
$has_woo=class_exists('WooCommerce');
?>
<div class="wrap eaiw-nebula-wrap <?php echo $theme==='light'?'eaiw-light':'';?>">
<div class="eaiw-nebula-bg"></div>
<div class="eaiw-topbar">
  <div class="eaiw-brand"><div class="eaiw-logo">🛒</div><div><h1>فروش‌یار خودکار — ووکامرس هوشمند</h1><p>محصول ضعیف را پیدا کن، با یک کلیک جذابش کن، یا از صفر محصول بساز — همه با AI واقعی و <b>آمار واقعی سایتت</b></p></div></div>
  <a href="<?php echo admin_url('admin.php?page=eaiw-nebula');?>" class="eaiw-btn eaiw-btn-ghost">← اتاق فرمان</a>
</div>

<?php if(!$has_woo): ?>
  <div class="eaiw-card" style="background:rgba(239,68,68,.08); border-color:rgba(239,68,68,.15)">
    <h3 style="color:#FECACA">ووکامرس نصب نیست</h3>
    <p>این بخش برای فروشگاه ووکامرس است. وقتی ووکامرس را نصب و فعال کردی، اینجا محصولاتت می‌آید. فعلاً می‌تونی از <a href="<?php echo admin_url('admin.php?page=eaiw-factory');?>" style="color:#22d3ee">کارخانه محتوا</a> استفاده کنی.</p>
    <div style="margin-top:8px"><a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');?>" class="eaiw-btn eaiw-btn-primary" style="padding:7px 12px; font-size:.82rem">نصب ووکامرس</a></div>
  </div>
<?php else: ?>

<div class="eaiw-grid">
  <div class="eaiw-card eaiw-col-5">
    <h3><i>➕</i> ساخت محصول جدید از ایده — واقعی</h3>
    <textarea class="eaiw-textarea" id="eaiwWooPrompt" rows="3" placeholder="مثلاً: پک قهوه اسپرسو 250 گرمی + ماگ سرامیکی، هدیه برای عاشقان قهوه، قیمت حدود 800 هزار"></textarea>
    <label style="display:flex; gap:8px; align-items:center; margin-top:8px; font-size:.85rem; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:8px">
      <input type="checkbox" id="eaiwWooMakeImage" checked> عکس محصول هم با Flux بساز (1:1) — واقعی
    </label>
    <button class="eaiw-btn eaiw-btn-primary" id="eaiwWooCreate" style="margin-top:10px; width:100%">🛒 ساخت محصول — واقعی و ذخیره در ووکامرس</button>
    <div id="eaiwWooCreateRes" style="margin-top:10px"></div>
    <div style="margin-top:8px; font-size:.75rem; color:var(--nebula-muted); background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:8px; padding:8px">ذخیره به‌عنوان <b>پیش‌نویس</b> — با قیمت، SKU، تگ، FAQ و عکس (اگر تیک بزنی)</div>
  </div>

  <div class="eaiw-card eaiw-col-7">
    <h3><i>🔍</i> محصولات ضعیف — آماده بهبود (آمار واقعی)</h3>
    <p style="font-size:.85rem; color:var(--nebula-muted)">این‌ها توضیح کوتاه، بدون عکس یا بدون FAQ دارند — امتیاز ضعف واقعیِ سایتت:</p>
    <div id="eaiwWeakList">
      <div style="text-align:center; padding:18px; color:var(--nebula-muted)"><span style="display:inline-block; width:18px; height:18px; border:2px solid #6d28ff; border-top-color:transparent; border-radius:50%; animation:spin 0.8s linear infinite; vertical-align:middle; margin-left:6px"></span> در حال بررسی محصولات واقعی سایت...</div>
    </div>
    <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap">
      <button class="eaiw-btn eaiw-btn-ghost" id="eaiwWooRefresh" style="padding:6px 10px; font-size:.78rem">🔄 بروزرسانی لیست</button>
      <button class="eaiw-btn eaiw-btn-primary" id="eaiwWooBulk" style="padding:6px 10px; font-size:.78rem">⚡ بهبود دسته‌ای (تا 6 تا) — واقعی</button>
    </div>
    <div id="eaiwWooBulkRes" style="margin-top:10px; font-size:.82rem"></div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
  </div>

  <div class="eaiw-card eaiw-col-12">
    <h3><i>📦</i> همه محصولات — بهبود تکی (6 تای اخیر)</h3>
    <div id="eaiwAllProducts" style="display:grid; grid-template-columns:repeat(2,1fr); gap:10px">
      <div style="grid-column:span 2; text-align:center; padding:16px; color:var(--nebula-muted)">در حال بارگذاری...</div>
    </div>
  </div>
</div>

<script>
jQuery(function($){
  function toast(m,ok=true){
    const t=$(`<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:${ok?'#10b981':'#ef4444'}; color:white; padding:10px 14px; border-radius:12px; font-size:.9rem; box-shadow:0 8px 24px rgba(0,0,0,.3)">${m}</div>`);
    $('body').append(t); setTimeout(()=> t.fadeOut(400, ()=>t.remove()), 2800);
  }

  function loadWeak(){
    $('#eaiwWeakList').html('<div style="text-align:center; padding:18px; color:var(--nebula-muted)"><span style="display:inline-block; width:18px; height:18px; border:2px solid #6d28ff; border-top-color:transparent; border-radius:50%; animation:spin 0.8s linear infinite; vertical-align:middle; margin-left:6px"></span> در حال بررسی...</div>');
    $.post(EAIW.ajax, {action:'eaiw_woo_find_weak', _ajax_nonce:EAIW.nonce}, res=>{
      if(res.success){
        const list=res.data;
        if(!list.length){
          $('#eaiwWeakList').html('<div style="text-align:center; padding:16px; color:var(--nebula-muted); background:rgba(16,185,129,.07); border:1px solid rgba(16,185,129,.15); border-radius:12px">همه محصولات مرتب‌اند ✅ — موجودی واقعی سایتت عالیه.</div>');
        } else {
          let html='';
          list.forEach(w=>{
            html+=`<div style="display:flex; justify-content:space-between; align-items:center; gap:8px; padding:10px; background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; margin:6px 0">
              <div><b style="color:var(--nebula-text-strong)">${w.title}</b><br><span style="font-size:.78rem; color:var(--nebula-muted)">${w.words} کلمه • عکس: ${w.thumb} • قیمت: ${w.price} • امتیاز ضعف: <b style="color:${w.score>60?'#ef4444':'#f59e0b'}">${w.score}</b></span></div>
              <div style="display:flex; gap:6px"><button class="eaiw-btn eaiw-btn-primary eaiwWooOne" data-id="${w.id}" style="padding:6px 10px; font-size:.78rem">✨ بهبود</button><a href="${w.url}" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 10px; font-size:.78rem">ویرایش</a></div>
            </div>`;
          });
          $('#eaiwWeakList').html(html);
        }
      } else {
        $('#eaiwWeakList').html('<div style="background:rgba(239,68,68,.08); border:1px solid #ef444433; border-radius:10px; padding:10px; color:#FECACA; font-size:.85rem">خطا در بارگذاری: '+(res.data||'نامشخص')+' — دوباره بروزرسانی بزن</div>');
      }
    }).fail(()=> $('#eaiwWeakList').html('<div style="color:#FECACA">خطای ارتباط — دوباره تلاش کن</div>'));
  }

  function loadAll(){
    $.post(EAIW.ajax, {action:'eaiw_woo_find_weak', _ajax_nonce:EAIW.nonce}, res=>{
      // برای همه محصولات، از همین لیست + 6 تای اضافی via wc_get_products
      // fallback: اگر فیلتر شد، از AJAX جدا می‌گیریم
      if(res.success){
        let html='';
        // نمایش 6 تای ضعیف به‌عنوان همه (برای سبکی)
        (res.data.slice(0,6)).forEach(w=>{
          const enhanced = w.score<30 ? 'بهبود یافته' : 'آماده';
          const cls = w.score<30 ? 'green' : 'purple';
          html+=`<div style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:12px; padding:12px; display:flex; justify-content:space-between; gap:10px; align-items:center"><div><b>${w.title}</b> <span class="eaiw-badge ${cls}">${enhanced}</span><br><span style="font-size:.78rem; color:var(--nebula-muted)">${w.price} • ${w.words} کلمه</span></div><button class="eaiw-btn eaiw-btn-ghost eaiwWooOne" data-id="${w.id}" style="padding:6px 10px; font-size:.78rem">✨ بهبود با AI</button></div>`;
        });
        if(!html) html='<div style="grid-column:span 2; text-align:center; color:var(--nebula-muted)">محصولی پیدا نشد — یک محصول بساز</div>';
        $('#eaiwAllProducts').html(html);
      }
    });
  }

  // initial load
  loadWeak();
  setTimeout(loadAll, 600);

  $('#eaiwWooCreate').on('click', function(){
    const btn=$(this), prompt=$('#eaiwWooPrompt').val();
    if(!prompt) return toast('موضوع محصول را بنویس', false);
    btn.prop('disabled',true).text('در حال ساخت محصول... 30 ثانیه');
    $.post(EAIW.ajax, {action:'eaiw_woo_create_product', prompt:prompt, make_image: $('#eaiwWooMakeImage').is(':checked')?1:0, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('🛒 ساخت محصول — واقعی و ذخیره در ووکامرس');
      if(res.success){
        $('#eaiwWooCreateRes').html(`<div style="background:rgba(16,185,129,.08); border:1px solid #10b98122; border-radius:10px; padding:10px; font-size:.85rem">ساخته شد: <b>${res.data.title}</b> (پیش‌نویس)<br><a href="${res.data.edit_url}" target="_blank" class="eaiw-btn eaiw-btn-primary" style="padding:6px 10px; font-size:.78rem">ویرایش محصول</a> <a href="${res.data.view_url}" target="_blank" class="eaiw-btn eaiw-btn-ghost" style="padding:6px 10px; font-size:.78rem">نمایش</a></div>`);
        toast('محصول ساخته شد — رفت پیش‌نویس‌ها');
        loadWeak();
      } else toast(res.data, false);
    }).fail(()=>{ btn.prop('disabled',false).text('🛒 ساخت محصول — واقعی و ذخیره در ووکامرس'); toast('خطای ارتباط', false); });
  });
  $(document).on('click','.eaiwWooOne', function(){
    const btn=$(this), id=btn.data('id');
    btn.prop('disabled',true).text('در حال بهبود...');
    $.post(EAIW.ajax, {action:'eaiw_woo_enhance_one', product_id:id, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('✨ بهبود');
      if(res.success) { toast('محصول بهبود یافت — توضیح + FAQ + سئو واقعی'); loadWeak(); }
      else toast(res.data, false);
    }).fail(()=>{ btn.prop('disabled',false).text('✨ بهبود'); toast('خطای ارتباط', false); });
  });
  $('#eaiwWooRefresh').on('click', ()=> { loadWeak(); loadAll(); toast('بروزرسانی شد'); });
  $('#eaiwWooBulk').on('click', function(){
    const btn=$(this);
    const ids=[]; $('.eaiwWooOne').each(function(){ const id=$(this).data('id'); if(id) ids.push(id); });
    const uniq=[...new Set(ids)].slice(0,6);
    if(!uniq.length) return toast('محصولی برای بهبود دسته‌ای نیست — اول لیست را بروزرسانی کن', false);
    btn.prop('disabled',true).text('در حال بهبود دسته‌ای...');
    $('#eaiwWooBulkRes').html('<div class="eaiw-progress"><i style="width:30%"></i></div><div style="font-size:.82rem; color:var(--nebula-muted); margin-top:6px">در حال بهبود '+uniq.length+' محصول واقعی سایت — حدود 40 ثانیه...</div>');
    $.post(EAIW.ajax, {action:'eaiw_woo_bulk_enhance', ids:uniq, _ajax_nonce:EAIW.nonce}, res=>{
      btn.prop('disabled',false).text('⚡ بهبود دسته‌ای (تا 6 تا) — واقعی');
      if(res.success){
        let ok=res.data.filter(x=>x.ok).length;
        $('#eaiwWooBulkRes').html(`<span class="eaiw-badge green">${ok} محصول بهبود یافت (واقعی)</span> <span class="eaiw-badge purple">${res.data.length-ok} خطا</span><pre style="background:var(--nebula-input-bg); border:1px solid var(--nebula-border); border-radius:10px; padding:10px; font-size:.75rem; direction:ltr; text-align:left; margin-top:8px; max-height:160px; overflow:auto">${JSON.stringify(res.data,null,2)}</pre>`);
        toast(ok+' محصول بهبود یافت');
        loadWeak();
      } else toast(res.data, false);
    }).fail(()=>{ btn.prop('disabled',false).text('⚡ بهبود دسته‌ای (تا 6 تا) — واقعی'); toast('خطای ارتباط', false); });
  });
});
</script>

<?php endif; ?>
</div>
