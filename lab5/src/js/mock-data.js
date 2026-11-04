// Учебные данные системы управления мероприятиями.
const events = [
  {id:1,title:'Конференция по веб-разработке',category:'conference',status:'published',date:'2026-12-12',venue:'Зал Альфа',capacity:120},
  {id:2,title:'Мастер-класс по Figma',category:'workshop',status:'published',date:'2026-12-15',venue:'Дизайн-студия',capacity:35},
  {id:3,title:'Хакатон цифровых решений',category:'hackathon',status:'draft',date:'2026-12-20',venue:'Технопарк',capacity:80},
  {id:4,title:'Конференция по информационной безопасности',category:'conference',status:'published',date:'2027-01-14',venue:'Главный зал',capacity:200},
  {id:5,title:'Мастер-класс по JavaScript',category:'workshop',status:'draft',date:'2027-01-21',venue:'Аудитория 302',capacity:30},
  {id:6,title:'Хакатон по искусственному интеллекту',category:'hackathon',status:'published',date:'2027-02-03',venue:'ИТ-центр',capacity:90},
  {id:7,title:'Встреча организаторов',category:'meeting',status:'cancelled',date:'2026-12-08',venue:'Переговорная',capacity:20}
];
const users = [{id:1,name:'Анна Петрова',role:'Организатор'},{id:2,name:'Иван Смирнов',role:'Участник'}];
const ROUTES = {
  'GET /api/events': function(query) { return filterList(events,query); },
  'GET /api/users': function() { return users; },
  'GET /api/events/:id': function(query,body,params) { const item=events.find(x=>x.id===Number(params.id)); return item || {status:404,error:'Мероприятие не найдено'}; },
  'POST /api/events': function(query,body) {
    if (!body || !body.title || body.title.trim().length<3) return {status:422,error:'Название должно содержать не менее 3 символов'};
    if (!body.date || !body.venue || !Number.isInteger(Number(body.capacity)) || Number(body.capacity)<1) return {status:422,error:'Укажите дату, площадку и положительную вместимость'};
    const item={id:Math.max(0,...events.map(x=>x.id))+1,title:body.title.trim(),category:body.category||'meeting',status:'draft',date:body.date,venue:body.venue.trim(),capacity:Number(body.capacity)};
    events.unshift(item); return {status:201,data:item};
  },
  'DELETE /api/events/:id': function(query,body,params) {const index=events.findIndex(x=>x.id===Number(params.id)); if(index<0)return {status:404,error:'Мероприятие не найдено'};events.splice(index,1);return {status:200,data:{success:true}};},
  'PATCH /api/events/:id': function(query,body,params) {const item=events.find(x=>x.id===Number(params.id));if(!item)return {status:404,error:'Мероприятие не найдено'};if(!['draft','published','cancelled'].includes(body?.status))return {status:422,error:'Недопустимый статус'};item.status=body.status;return {status:200,data:item};}
};
