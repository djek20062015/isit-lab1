const API_BASE = '/api';
class ApiError extends Error {constructor(message,status,errors={}){super(message);this.status=status;this.errors=errors;}}
const session={token:localStorage.getItem('eventmanager_token'),user:JSON.parse(localStorage.getItem('eventmanager_user')||'null')};
async function request(path,options={}){
 const headers={'Accept':'application/json',...(options.body?{'Content-Type':'application/json'}:{}),...(session.token?{'Authorization':'Bearer '+session.token}:{})};
 let response;
 try{response=await fetch(API_BASE+path,{...options,headers:{...headers,...options.headers}})}
 catch{throw new ApiError('Нет связи с сервером. Проверьте подключение и повторите.');}
 const raw=await response.text();let data=null;
 if(raw){try{data=JSON.parse(raw)}catch{throw new ApiError('Сервер вернул некорректный ответ',response.status)}}
 if(!response.ok){const e=new ApiError(data?.error||'Ошибка сервера',response.status,data?.errors||{});if(e.status===401&&path!=='/auth/login'){clearSession();window.dispatchEvent(new Event('session-expired'));}throw e;}
 return data;
}
function clearSession(){session.token=null;session.user=null;localStorage.removeItem('eventmanager_token');localStorage.removeItem('eventmanager_user')}
async function login(email,password){const data=await request('/auth/login',{method:'POST',body:JSON.stringify({email,password})});session.token=data.token;session.user=data.user;localStorage.setItem('eventmanager_token',data.token);localStorage.setItem('eventmanager_user',JSON.stringify(data.user));return data;}
async function logout(){try{await request('/auth/logout',{method:'POST'})}finally{clearSession()}}
const getEvents=(q='',status='')=>request('/events?'+new URLSearchParams({q,status,sort:'-id'}));
const getVenues=()=>request('/venues');const getCategories=()=>request('/categories');
const createEvent=data=>request('/events',{method:'POST',body:JSON.stringify(data)});
const patchEvent=(id,data)=>request('/events/'+id,{method:'PATCH',body:JSON.stringify(data)});
const deleteEvent=id=>request('/events/'+id,{method:'DELETE'});
