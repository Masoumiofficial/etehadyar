(function(){
  'use strict';
  const doc = document;
  const root = doc.documentElement;
  const config = window.ETEHADYAR_CONFIG || {};
  const search = doc.getElementById('release-search');
  const filterButtons = Array.from(doc.querySelectorAll('[data-filter]'));
  const cards = Array.from(doc.querySelectorAll('.release-card'));
  const groups = Array.from(doc.querySelectorAll('.release-group'));
  const resultCount = doc.getElementById('result-count');
  const noResults = doc.getElementById('no-results');
  const expandButton = doc.getElementById('expand-all');
  const progress = doc.querySelector('.scroll-progress');
  const header = doc.getElementById('doc-header');
  let activeFilter = 'all';
  let expanded = false;

  function faNumber(value){
    return String(value).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[Number(d)]; });
  }
  function normalize(value){
    return (value || '').toLowerCase().trim().replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ');
  }
  function applyConfig(){
    const url = config.purchaseUrlIR || config.purchaseUrlInternational || 'https://etehadyar.ir/';
    doc.querySelectorAll('[data-buy]').forEach(function(link){ link.href = url; });
  }
  function applyFilters(){
    const term = normalize(search.value);
    let visibleCount = 0;
    cards.forEach(function(card){
      const filterMatch = activeFilter === 'all' || card.dataset.major === activeFilter;
      const searchMatch = !term || normalize(card.dataset.search).includes(term);
      const visible = filterMatch && searchMatch;
      card.classList.toggle('is-filtered', !visible);
      if(visible){
        visibleCount++;
        if(term) card.open = true;
      }
    });
    groups.forEach(function(group){
      const hasVisible = Array.from(group.querySelectorAll('.release-card')).some(function(card){ return !card.classList.contains('is-filtered'); });
      group.classList.toggle('is-filtered', !hasVisible);
    });
    if(resultCount){
      resultCount.textContent = activeFilter === 'all' && !term
        ? '۵۴ نسخه اصلی + ۱ Alpha'
        : faNumber(visibleCount) + ' نتیجه';
    }
    if(noResults) noResults.hidden = visibleCount !== 0;
  }

  applyConfig();
  applyFilters();

  filterButtons.forEach(function(button){
    button.addEventListener('click', function(){
      activeFilter = button.dataset.filter;
      filterButtons.forEach(function(item){ item.classList.toggle('active', item === button); });
      applyFilters();
    });
  });
  search.addEventListener('input', applyFilters);
  search.addEventListener('search', applyFilters);

  doc.addEventListener('keydown', function(event){
    if(event.key === '/' && doc.activeElement !== search){
      event.preventDefault(); search.focus();
    }
    if(event.key === 'Escape' && doc.activeElement === search){
      search.value = ''; search.blur(); applyFilters();
    }
  });

  expandButton.addEventListener('click', function(){
    expanded = !expanded;
    cards.forEach(function(card){ if(!card.classList.contains('is-filtered')) card.open = expanded; });
    expandButton.textContent = expanded ? 'بستن همه' : 'بازکردن همه';
  });

  function onScroll(){
    const y = window.scrollY || 0;
    if(header) header.classList.toggle('scrolled', y > 20);
    const height = doc.documentElement.scrollHeight - window.innerHeight;
    const amount = height > 0 ? Math.min(100, Math.max(0, y / height * 100)) : 0;
    if(progress) progress.style.setProperty('--scroll', amount + '%');
  }
  onScroll();
  window.addEventListener('scroll', onScroll, {passive:true});

  if('IntersectionObserver' in window){
    const indexLinks = Array.from(doc.querySelectorAll('.index-card a'));
    const observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(!entry.isIntersecting) return;
        indexLinks.forEach(function(link){ link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id); });
      });
    }, {rootMargin:'-28% 0px -65%', threshold:0});
    groups.forEach(function(group){ observer.observe(group); });
  }

  if(location.hash){
    const target = doc.querySelector(location.hash);
    if(target && target.matches('details')) target.open = true;
  }
})();
