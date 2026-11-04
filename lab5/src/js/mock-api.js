// Совместимая реализация учебного mock API. Настоящие сетевые запросы не выполняются.
(function () {
  'use strict';
  const nativeFetch=window.fetch.bind(window);
  let minDelay=300,maxDelay=800,errorRate=0,networkDown=false;
  function filterList(items,query) {
    return items.filter(item=>Object.entries(query).every(([key,value])=>{
      if(!value)return true;
      if(key==='q')return Object.values(item).some(v=>String(v).toLowerCase().includes(value.toLowerCase()));
      return String(item[key]??'').toLowerCase()===value.toLowerCase();
    })).map(item=>({...item}));
  }
  window.filterList=filterList;
  window.MockApi={setDelay(min,max){minDelay=Math.max(0,Number(min));maxDelay=Math.max(minDelay,Number(max));},setErrorRate(rate){errorRate=Math.max(0,Math.min(1,Number(rate)));},setNetworkDown(value){networkDown=Boolean(value);}};
  window.fetch=async function(input,options={}) {
    const url=new URL(typeof input==='string'?input:input.url,location.href);
    if(!url.pathname.startsWith('/api/'))return nativeFetch(input,options);
    const method=(options.method||'GET').toUpperCase();
    await new Promise(resolve=>setTimeout(resolve,minDelay+Math.random()*(maxDelay-minDelay)));
    if(networkDown)throw new TypeError('Failed to fetch: сеть недоступна');
    if(Math.random()<errorRate)return jsonResponse({error:'Внутренняя ошибка учебного сервера'},500);
    let route=ROUTES[`${method} ${url.pathname}`],params={};
    if(!route){for(const [pattern,handler] of Object.entries(ROUTES)){
      const [routeMethod,path]=pattern.split(' ');if(routeMethod!==method||!path.includes(':'))continue;
      const keys=[];const regex=new RegExp('^'+path.replace(/:([^/]+)/g,(_,key)=>{keys.push(key);return '([^/]+)';})+'$');
      const match=url.pathname.match(regex);if(match){route=handler;keys.forEach((key,i)=>params[key]=decodeURIComponent(match[i+1]));break;}
    }}
    if(!route)return jsonResponse({error:`Нет строки ${method} ${url.pathname} в ROUTES`},404);
    let body=null;
    try{if(options.body)body=JSON.parse(options.body);}catch{return jsonResponse({error:'Неверный JSON'},400);}
    try{const result=route(Object.fromEntries(url.searchParams),body,params);
      if(result&&typeof result==='object'&&typeof result.status==='number')return jsonResponse(result.error?{error:result.error}:result.data,result.status);
      return jsonResponse(result,200);
    }catch(error){return jsonResponse({error:error.message},500);}
  };
  function jsonResponse(data,status){return new Response(JSON.stringify(data),{status,headers:{'Content-Type':'application/json'}});}
})();
