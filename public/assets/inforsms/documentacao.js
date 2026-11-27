(function(){
function init(){
 const root=document.querySelector('.api-public-content');if(!root)return;
 const menu=root.querySelector('.api-doc-menu');
 const links=Array.from(root.querySelectorAll('.api-doc-link'));
 const targets=links.map(link=>({link,section:document.getElementById((link.getAttribute('href')||'').replace(/^#/,''))})).filter(x=>x.section);
 const normalize=s=>s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
 if(menu){let search=menu.querySelector('.api-doc-search');if(!search){search=document.createElement('input');search.type='search';search.className='api-doc-search';search.placeholder='Pesquisar documentação...';search.setAttribute('aria-label','Pesquisar documentação');menu.prepend(search);}search.addEventListener('input',()=>links.forEach(link=>{link.hidden=!normalize(link.textContent).includes(normalize(search.value.trim()));}));}
 function active(link){links.forEach(x=>{x.classList.toggle('active',x===link);if(x===link)x.setAttribute('aria-current','location');else x.removeAttribute('aria-current');});}
 targets.forEach(({link,section})=>link.addEventListener('click',e=>{e.preventDefault();active(link);section.scrollIntoView({behavior:'smooth',block:'start'});history.replaceState(null,'','#'+section.id);}));
 function update(){let current=targets[0];for(const item of targets){if(item.section.getBoundingClientRect().top<=150)current=item;}if(current)active(current.link);}
 window.addEventListener('scroll',update,{passive:true});update();
 root.querySelectorAll('td,th').forEach(cell=>{const method=cell.textContent.trim();if(['GET','POST'].includes(method)&&!cell.querySelector('.api-method')){const span=document.createElement('span');span.className='api-method-badge api-method-'+method.toLowerCase();span.textContent=method;cell.replaceChildren(span);}});
}
function toast(message){document.querySelector('.api-copy-toast')?.remove();const el=document.createElement('div');el.className='api-copy-toast';el.setAttribute('role','status');el.textContent=message;document.body.append(el);setTimeout(()=>el.remove(),2500);}
async function copy(id){const el=document.getElementById(id);if(!el)return;const value=el.matches('input,textarea')?el.value:el.innerText;try{await navigator.clipboard.writeText(value);toast('Copiado com sucesso!');}catch(e){toast('Não foi possível copiar. Seleccione o texto manualmente.');}}
window.copiarCodigo=copy;window.copiarTexto=copy;
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
