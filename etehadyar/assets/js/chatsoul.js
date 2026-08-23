document.addEventListener('DOMContentLoaded', ()=>{
  if(!window.EAIW_SOUL) return;
  const s=EAIW_SOUL;
  // mobile toggle
  if(s.mobile==='0' || s.mobile===0 || s.mobile===false){
    if(window.innerWidth<768) return;
  }
  const name=s.name||'اتحادیار';
  const greeting=s.greeting||`سلام! من ${name} هستم — دستیار باهوش و بامزه‌ات 😎`;
  const color=s.color||'#6d28ff';
  const size=s.size||'medium'; // small 340x380, medium 372x460, large 400x540
  const avatar=s.avatar||'';
  const pos=s.position||'bottom-right';
  const ox=parseInt(s.offset_x||22), oy=parseInt(s.offset_y||22);
  const faqs=s.faqs||[];
  // size map
  const dims={small:{w:340,h:380}, medium:{w:372,h:460}, large:{w:400,h:540}}[size]||{w:372,h:460};
  // position
  const isBottom=pos.includes('bottom'), isRight=pos.includes('right');
  const btnPos=isBottom?`bottom:${oy}px;`:`top:${oy}px;`;
  const panelPos=isBottom?`bottom:${oy+66}px;`:`top:${oy+66}px;`;
  const hPos=isRight?`right:${ox}px;`:`left:${ox}px;`;

  const wrap=document.createElement('div');
  wrap.innerHTML=`
    <style>
      @keyframes eaiwPulse{0%,100%{box-shadow:0 0 0 0 rgba(109,40,255,.4)} 50%{box-shadow:0 0 0 10px rgba(109,40,255,0)}}
      @keyframes eaiwBounce{0%,100%{transform:translateY(0)} 50%{transform:translateY(-3px)}}
      #eaiwSoulBtn{animation:eaiwPulse 2.2s infinite}
      #eaiwSoulPanel{backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px)}
      #eaiwSoulLog::-webkit-scrollbar{width:6px}
      #eaiwSoulLog::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15); border-radius:999px}
    </style>
    <div id="eaiwSoulBtn" style="position:fixed; ${btnPos} ${hPos} z-index:9999; width:58px; height:58px; border-radius:50%; background:conic-gradient(from 0deg,${color},#00e5ff,#ff2e97,${color}); display:grid; place-items:center; cursor:pointer; box-shadow:0 8px 24px rgba(0,0,0,.28); transition:.2s">
      <div style="width:52px; height:52px; border-radius:50%; background:#0a0f1f; display:grid; place-items:center; overflow:hidden; border:2px solid rgba(255,255,255,.9)">
        ${avatar ? `<img src="${avatar}" style="width:100%; height:100%; object-fit:cover">` : `<span style="font-size:1.45rem">💬</span>`}
      </div>
    </div>
    <div id="eaiwSoulPanel" style="position:fixed; ${panelPos} ${hPos} z-index:9999; width:${dims.w}px; max-width:94vw; height:${dims.h}px; background:rgba(10,15,31,.96); border:1px solid rgba(255,255,255,.10); border-radius:18px; overflow:hidden; display:none; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.5); font-family:Vazirmatn,Tahoma,sans-serif; backdrop-filter:blur(18px)">
      <div style="padding:12px 14px; background:linear-gradient(90deg,${color},#4f46e5); color:white; display:flex; justify-content:space-between; align-items:center; gap:8px">
        <div style="display:flex; gap:10px; align-items:center">
          <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.18); display:grid; place-items:center; overflow:hidden; border:1px solid rgba(255,255,255,.3)">
            ${avatar ? `<img src="${avatar}" style="width:100%; height:100%; object-fit:cover">` : `😎`}
          </div>
          <div><b style="font-size:.95rem">${name}</b><br><span style="font-size:.72rem; opacity:.85">آنلاین • باهوش و بامزه</span></div>
        </div>
        <span id="eaiwSoulClose" style="cursor:pointer; background:rgba(255,255,255,.18); width:28px; height:28px; display:grid; place-items:center; border-radius:50%; transition:.15s">✕</span>
      </div>
      <div id="eaiwSoulQuick" style="padding:8px 10px; display:flex; gap:6px; flex-wrap:wrap; border-bottom:1px solid rgba(255,255,255,.06); background:rgba(255,255,255,.03); max-height:70px; overflow:auto"></div>
      <div id="eaiwSoulLog" style="flex:1; overflow:auto; padding:12px; font-size:.88rem; color:#E6E8F2; background:radial-gradient(400px 200px at 50% 0%, rgba(109,40,255,.08), transparent), #070A14"></div>
      <div style="display:flex; gap:8px; padding:10px; border-top:1px solid rgba(255,255,255,.08); background:rgba(10,15,31,.96)">
        <input id="eaiwSoulInput" placeholder="پیامت رو بنویس..." style="flex:1; background:#111827; border:1px solid rgba(255,255,255,.10); color:white; border-radius:999px; padding:11px 13px; outline:none; font-family:inherit; font-size:.9rem">
        <button id="eaiwSoulSend" style="background:linear-gradient(90deg,${color},#00e5ff); border:none; color:white; border-radius:999px; padding:11px 15px; font-weight:800; cursor:pointer; font-family:inherit; box-shadow:0 4px 12px rgba(109,40,255,.3)">ارسال</button>
      </div>
      <div style="padding:6px 10px; font-size:.70rem; color:#64748B; text-align:center; background:rgba(0,0,0,.18); border-top:1px solid rgba(255,255,255,.04)">قدرت گرفته از اتحاد وردپرس — etehadyar.ir • GapGPT + حافظه هوشمند</div>
    </div>
  `;
  document.body.appendChild(wrap);
  const btn=document.getElementById('eaiwSoulBtn'), panel=document.getElementById('eaiwSoulPanel'), close=document.getElementById('eaiwSoulClose'), input=document.getElementById('eaiwSoulInput'), send=document.getElementById('eaiwSoulSend'), log=document.getElementById('eaiwSoulLog'), quick=document.getElementById('eaiwSoulQuick');
  let sid='soul-'+Math.random().toString(36).slice(2,8);
  // FAQ quick buttons
  if(faqs && faqs.length){
    faqs.slice(0,4).forEach(f=>{
      const b=document.createElement('button');
      b.textContent=f.q.slice(0,28);
      b.style.cssText='background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.10); color:#C2C8E6; padding:5px 9px; border-radius:999px; font-size:.75rem; cursor:pointer; font-family:inherit';
      b.onclick=()=>{ input.value=f.q; ask(); };
      quick.appendChild(b);
    });
  } else {
    quick.innerHTML='<span style="font-size:.75rem; color:#94A3B8">سوالی داری؟ بپرس — حتی جوک 😄</span>';
  }
  function toggle(){ 
    const isOpen=panel.style.display==='flex';
    panel.style.display= isOpen?'none':'flex';
    if(!isOpen) { input.focus(); btn.style.transform='scale(.92)'; setTimeout(()=> btn.style.transform='scale(1)', 180); }
  }
  btn.onclick=toggle; close.onclick=toggle;
  // hover
  btn.onmouseenter=()=> btn.style.transform='scale(1.06)';
  btn.onmouseleave=()=> btn.style.transform='scale(1)';
  function addMsg(role, text, type){
    const div=document.createElement('div');
    const isUser=role==='user';
    div.style.cssText=`margin:8px 0; padding:10px 12px; border-radius:14px; max-width:86%; line-height:1.7; white-space:pre-wrap; word-wrap:break-word; font-size:.88rem; box-shadow:0 2px 8px rgba(0,0,0,.15); ${isUser?'background:linear-gradient(135deg,#1e293b,#334155); margin-left:auto; color:white; border:1px solid rgba(255,255,255,.08)':'background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.08); color:#E6E8F2; backdrop-filter:blur(8px)'}`;
    if(type==='faq') div.style.border='1px solid rgba(16,185,129,.25)';
    // bamze emoji for assistant
    if(!isUser && !text.includes('😎') && !text.includes('😄')) text='😎 '+text;
    div.textContent=text;
    log.appendChild(div); log.scrollTop=log.scrollHeight;
  }
  async function ask(){
    const q=input.value.trim(); if(!q) return;
    addMsg('user', q); input.value='';
    const thinking=document.createElement('div'); thinking.textContent='...'; thinking.style.cssText='margin:8px 0; padding:10px 12px; border-radius:14px; background:rgba(109,40,255,.10); border:1px solid rgba(109,40,255,.15); color:#C2C8E6; font-size:.85rem; display:inline-block'; log.appendChild(thinking); log.scrollTop=log.scrollHeight;
    try{
      const res=await fetch(s.rest, {method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':s.nonce}, body:JSON.stringify({message:q, session_id:sid})});
      const data=await res.json();
      thinking.remove();
      addMsg('assistant', data.answer||'پاسخی دریافت نشد', data.type);
      if(data.sources && data.sources.length && data.type!=='faq'){
        const src=document.createElement('div'); src.style.cssText='margin:6px 0 10px; font-size:.72rem; color:#94A3B8; display:flex; gap:6px; flex-wrap:wrap';
        src.innerHTML=data.sources.slice(0,2).map(x=> x.url ? `<a href="${x.url}" target="_blank" style="color:#22d3ee; text-decoration:none; background:rgba(34,211,238,.10); border:1px solid rgba(34,211,238,.18); padding:3px 7px; border-radius:999px">🔗 ${x.title.slice(0,22)}</a>` : '').join('');
        if(src.innerHTML) { log.appendChild(src); log.scrollTop=log.scrollHeight; }
      }
    } catch(e){ thinking.remove(); addMsg('assistant','وای، اینترنت لگ زد 😅 — دوباره بگو!'); }
  }
  send.onclick=ask; input.addEventListener('keydown', e=>{ if(e.key==='Enter') ask(); });
  setTimeout(()=> addMsg('assistant', greeting), 700);
  // typing indicator on focus
  input.addEventListener('focus', ()=> btn.style.animation='none');
});
