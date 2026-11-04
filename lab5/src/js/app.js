'use strict';
const API='/api/events';
const STATUS_NAMES={draft:'Черновик',published:'Опубликовано',cancelled:'Отменено'};
const CATEGORY_NAMES={conference:'Конференция',workshop:'Мастер-класс',hackathon:'Хакатон',meeting:'Встреча'};
const form=document.getElementById('event-form');
const tbody=document.getElementById('event-body');
const table=document.getElementById('event-table');
const info=document.getElementById('info');
const refreshButton=document.getElementById('refresh-button');
const submitButton=document.getElementById('submit-button');
const statusFilter=document.getElementById('status-filter');
const categoryFilter=document.getElementById('category-filter');
const searchInput=document.getElementById('search-input');
let requestNumber=0;
let searchTimer;
function showInfo(text,type=''){info.textContent=text;info.className='message'+(type?' '+type+'-state':'');info.hidden=false;}
function updateCounter(){const rows=[...tbody.rows];document.getElementById('counter').textContent=rows.length;document.getElementById('published-counter').textContent=rows.filter(r=>r.dataset.status==='published').length;document.getElementById('draft-counter').textContent=rows.filter(r=>r.dataset.status==='draft').length;table.hidden=rows.length===0;}
function addCell(row,text){const cell=document.createElement('td');cell.textContent=String(text);row.appendChild(cell);return cell;}
function createEventRow(item){
  const row=document.createElement('tr');row.dataset.id=String(item.id);row.dataset.status=item.status;row.setAttribute('title',`Мероприятие №${item.id}`);
  if(item.status==='cancelled')row.classList.add('done');
  const titleCell=addCell(row,'');const title=document.createElement('strong');title.textContent=item.title;titleCell.appendChild(title);
  const locationCell=addCell(row,'');const date=document.createElement('strong');date.textContent=item.date;locationCell.appendChild(date);const venue=document.createElement('small');venue.textContent=item.venue;locationCell.appendChild(venue);
  addCell(row,CATEGORY_NAMES[item.category]||item.category);
  const statusCell=addCell(row,'');const pill=document.createElement('span');pill.className='pill '+item.status;pill.textContent=STATUS_NAMES[item.status]||item.status;statusCell.appendChild(pill);
  addCell(row,item.capacity);
  const actions=addCell(row,'');actions.className='actions';
  const toggle=document.createElement('button');toggle.type='button';toggle.className='small-btn';toggle.textContent=item.status==='published'?'В черновик':'Опубликовать';toggle.setAttribute('title','Изменить статус мероприятия');toggle.addEventListener('click',()=>onStatusClick(row,item,toggle,pill));actions.appendChild(toggle);
  const remove=document.createElement('button');remove.type='button';remove.className='small-btn danger';remove.textContent='Удалить';remove.setAttribute('title','Удалить мероприятие');remove.addEventListener('click',()=>onDeleteClick(row,item));actions.appendChild(remove);
  return row;
}
async function apiRequest(url,options){const response=await fetch(url,options);const data=await response.json();if(!response.ok)throw new Error(`Ошибка сервера: ${response.status}${data?.error?' — '+data.error:''}`);return data;}
async function loadEvents(){const query=new URLSearchParams({status:statusFilter.value,category:categoryFilter.value,q:searchInput.value.trim()});return await apiRequest(`${API}?${query}`);}
async function refreshList(){const current=++requestNumber;refreshButton.disabled=true;showInfo('Загрузка…');tbody.replaceChildren();updateCounter();console.log('Загрузка мероприятий');try{const items=await loadEvents();if(current!==requestNumber)return;items.forEach(item=>tbody.appendChild(createEventRow(item)));updateCounter();if(!items.length)showInfo('Мероприятия не найдены. Измените фильтр или текст поиска.');else info.hidden=true;}catch(error){if(current!==requestNumber)return;showInfo(error.message,'error');console.error(error);}finally{if(current===requestNumber)refreshButton.disabled=false;}}
async function onStatusClick(row,item,button,pill){const status=item.status==='published'?'draft':'published';button.disabled=true;try{const updated=await apiRequest(`${API}/${item.id}`,{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({status})});item.status=updated.status;row.dataset.status=updated.status;row.classList.toggle('done',updated.status==='cancelled');pill.className='pill '+updated.status;pill.textContent=STATUS_NAMES[updated.status];button.textContent=updated.status==='published'?'В черновик':'Опубликовать';button.setAttribute('title',updated.status==='published'?'Вернуть в черновики':'Опубликовать мероприятие');updateCounter();showInfo('Статус мероприятия обновлён.','success');}catch(error){showInfo(error.message,'error');}finally{button.disabled=false;}}
async function onDeleteClick(row,item){if(!confirm(`Удалить мероприятие «${item.title}»?`))return;const buttons=row.querySelectorAll('button');buttons.forEach(b=>b.disabled=true);try{await apiRequest(`${API}/${item.id}`,{method:'DELETE'});row.remove();updateCounter();showInfo('Мероприятие удалено.','success');if(!tbody.rows.length)showInfo('Мероприятия не найдены. Измените фильтр или текст поиска.');}catch(error){showInfo(error.message,'error');buttons.forEach(b=>b.disabled=false);}}
function clearErrors(){['title','date','venue','capacity'].forEach(field=>document.getElementById('error-'+field).textContent='');}
async function onFormSubmit(event){event.preventDefault();clearErrors();console.log('Отправка формы');const title=document.getElementById('event-title').value.trim();const date=document.getElementById('event-date').value;const venue=document.getElementById('event-venue').value.trim();const capacity=Number(document.getElementById('event-capacity').value);let valid=true;
  if(title.length<3){document.getElementById('error-title').textContent='Введите минимум 3 символа';valid=false;}
  if(!date){document.getElementById('error-date').textContent='Укажите дату мероприятия';valid=false;}
  if(!venue){document.getElementById('error-venue').textContent='Укажите площадку';valid=false;}
  if(!Number.isInteger(capacity)||capacity<1){document.getElementById('error-capacity').textContent='Укажите целое число мест больше нуля';valid=false;}
  if(!valid)return;
  submitButton.disabled=true;
  try{const item=await apiRequest(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title,date,venue,capacity,category:document.getElementById('event-category').value})});form.reset();console.log('Создано мероприятие',item);await refreshList();showInfo('Мероприятие создано как черновик.','success');}catch(error){showInfo(error.message,'error');}finally{submitButton.disabled=false;}
}
form.addEventListener('submit',onFormSubmit);
refreshButton.addEventListener('click',()=>{console.log('Нажата кнопка обновления');refreshList();});
statusFilter.addEventListener('change',()=>{console.log('Статус:',statusFilter.value);refreshList();});
categoryFilter.addEventListener('change',()=>{console.log('Категория:',categoryFilter.value);refreshList();});
searchInput.addEventListener('input',()=>{console.log('Поиск:',searchInput.value);clearTimeout(searchTimer);searchTimer=setTimeout(refreshList,300);});
refreshList();
