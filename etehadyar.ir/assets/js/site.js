(function(){
  'use strict';
  const doc=document,root=doc.documentElement,body=doc.body;
  const cfg=window.ETEHADYAR_CONFIG||{};
  const langButton=doc.getElementById('lang-toggle');
  const menuButton=doc.getElementById('menu-toggle');
  const mobileMenu=doc.getElementById('mobile-menu');
  const header=doc.getElementById('site-header');
  const progress=doc.querySelector('.scroll-progress');
  const glow=doc.querySelector('.ambient-glow');
  const currencyButtons=Array.from(doc.querySelectorAll('[data-currency]'));
  const currencyPanels=Array.from(doc.querySelectorAll('[data-price-panel]'));
  const tourButtons=Array.from(doc.querySelectorAll('[data-tour]'));
  const tourPanels=Array.from(doc.querySelectorAll('[data-tour-panel]'));
  const lightbox=doc.getElementById('lightbox');
  const lightboxImage=doc.getElementById('lightbox-image');
  let currencyTouched=false;
  body.classList.add('js-ready');

  function getStored(key,fallback){try{return localStorage.getItem(key)||fallback}catch(e){return fallback}}
  function store(key,value){try{localStorage.setItem(key,value)}catch(e){}}
  function buyUrl(currency){return currency==='usd'?(cfg.purchaseUrlInternational||cfg.purchaseUrlIR||'https://etehadyar.ir/'):(cfg.purchaseUrlIR||cfg.purchaseUrlInternational||'https://etehadyar.ir/')}
  function applyConfig(){
    doc.querySelectorAll('[data-price-irr]').forEach(el=>el.textContent=cfg.priceToman||'۱۵٬۰۰۰٬۰۰۰');
    doc.querySelectorAll('[data-price-usd]').forEach(el=>el.textContent=cfg.priceUSD||'150');
  }
  function setCurrency(currency,persist){
    const chosen=currency==='usd'?'usd':'irr';root.dataset.currency=chosen;
    currencyButtons.forEach(button=>{const active=button.dataset.currency===chosen;button.setAttribute('aria-selected',String(active));button.tabIndex=active?0:-1});
    currencyPanels.forEach(panel=>panel.hidden=panel.dataset.pricePanel!==chosen);
    doc.querySelectorAll('[data-buy]').forEach(link=>link.href=buyUrl(chosen));
    if(persist)store('etehadyar-currency',chosen);
  }
  function setLanguage(lang,initial){
    const en=lang==='en';root.dataset.lang=en?'en':'fa';root.lang=en?'en':'fa';root.dir=en?'ltr':'rtl';
    doc.title=en?'Etehadyar 6.5 — AI WordPress Operating System':'اتحادیار 6.5 — سیستم‌عامل هوشمند وردپرس';
    langButton.setAttribute('aria-label',en?'تغییر زبان به فارسی':'Switch to English');
    menuButton.setAttribute('aria-label',en?'Open menu':'باز کردن منو');
    store('etehadyar-lang',en?'en':'fa');
    if(!currencyTouched){const saved=initial?getStored('etehadyar-currency',''):'';setCurrency(saved||(en?'usd':'irr'),false)}
  }
  applyConfig();
  setLanguage(getStored('etehadyar-lang','fa')==='en'?'en':'fa',true);
  langButton.addEventListener('click',()=>setLanguage(root.dataset.lang==='fa'?'en':'fa',false));

  currencyButtons.forEach((button,index)=>{
    button.addEventListener('click',()=>{currencyTouched=true;setCurrency(button.dataset.currency,true)});
    button.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight'].includes(event.key))return;event.preventDefault();const dir=event.key==='ArrowRight'?1:-1,next=(index+dir+currencyButtons.length)%currencyButtons.length;currencyTouched=true;setCurrency(currencyButtons[next].dataset.currency,true);currencyButtons[next].focus()});
  });

  function closeMenu(){mobileMenu.classList.remove('open');menuButton.setAttribute('aria-expanded','false');body.classList.remove('menu-open')}
  menuButton.addEventListener('click',()=>{const open=!mobileMenu.classList.contains('open');mobileMenu.classList.toggle('open',open);menuButton.setAttribute('aria-expanded',String(open));body.classList.toggle('menu-open',open)});
  mobileMenu.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeMenu));
  doc.addEventListener('click',event=>{if(!event.target.closest('.nav-shell'))closeMenu()});

  function activateTour(name,focus){
    tourButtons.forEach(button=>{const active=button.dataset.tour===name;button.setAttribute('aria-selected',String(active));button.tabIndex=active?0:-1;if(active&&focus)button.focus()});
    tourPanels.forEach(panel=>panel.hidden=panel.dataset.tourPanel!==name);
  }
  tourButtons.forEach((button,index)=>{
    button.addEventListener('click',()=>activateTour(button.dataset.tour,false));
    button.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight'].includes(event.key))return;event.preventDefault();const dir=event.key==='ArrowRight'?1:-1,next=(index+dir+tourButtons.length)%tourButtons.length;activateTour(tourButtons[next].dataset.tour,true)});
  });

  function openLightbox(src,alt){lightboxImage.src=src;lightboxImage.alt=alt||'';lightbox.hidden=false;body.classList.add('lightbox-open');doc.querySelector('.lightbox-close').focus()}
  function closeLightbox(){lightbox.hidden=true;lightboxImage.src='';body.classList.remove('lightbox-open')}
  doc.querySelectorAll('[data-lightbox]').forEach(button=>button.addEventListener('click',()=>openLightbox(button.dataset.lightbox,button.querySelector('img')?.alt)));
  lightbox.addEventListener('click',event=>{if(event.target===lightbox||event.target.closest('.lightbox-close'))closeLightbox()});

  function onScroll(){const y=window.scrollY||0;header.classList.toggle('scrolled',y>32);const height=doc.documentElement.scrollHeight-window.innerHeight,amount=height>0?Math.min(100,Math.max(0,y/height*100)):0;progress.style.setProperty('--scroll',amount+'%')}
  onScroll();window.addEventListener('scroll',onScroll,{passive:true});
  doc.addEventListener('keydown',event=>{if(event.key==='Escape'){closeMenu();if(!lightbox.hidden)closeLightbox()}});

  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(!reduced&&'IntersectionObserver'in window){const observer=new IntersectionObserver((entries,obs)=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');obs.unobserve(entry.target)}}),{threshold:.08,rootMargin:'0px 0px -28px'});doc.querySelectorAll('.reveal').forEach(el=>observer.observe(el))}else doc.querySelectorAll('.reveal').forEach(el=>el.classList.add('is-visible'));
  if('IntersectionObserver'in window){const links=Array.from(doc.querySelectorAll('.desktop-nav a[href^="#"]')),map=new Map(links.map(link=>[link.getAttribute('href').slice(1),link]));const navObserver=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting&&map.has(entry.target.id)){links.forEach(link=>link.classList.remove('active'));map.get(entry.target.id).classList.add('active')}}),{rootMargin:'-36% 0px -56%',threshold:0});map.forEach((link,id)=>{const section=doc.getElementById(id);if(section)navObserver.observe(section)})}
  if(!reduced&&glow&&window.matchMedia('(pointer:fine)').matches)window.addEventListener('pointermove',event=>{glow.style.setProperty('--mx',event.clientX+'px');glow.style.setProperty('--my',event.clientY+'px')},{passive:true});
})();
