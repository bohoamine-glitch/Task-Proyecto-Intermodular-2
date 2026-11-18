const $=s=>document.querySelector(s);
let state= {
    user:null,csrf:null
}, editingId=null, savingTask=false;
function note(s,err=false) {
    const n=$('#notice');
    n.textContent=s;
    n.classList.toggle('error',err);
    n.hidden=!s
}
function view(n) {
    document.querySelectorAll('.view').forEach(x=>x.hidden=true);
    $('#'+n+'View').hidden=false;
    note('')
}
function bind(root=document) {
    root.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>view(b.dataset.view)))
}
function esc(s) {
    return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
async function api(a,opt={}){const h={'Content-Type':'application/json',...(opt.headers||{})};if(state.csrf)h['X-CSRF-Token']=state.csrf;const r=await fetch('api.php?action='+a,{...opt,headers:h,credentials:'same-origin'});let d;try{d=await r.json()}catch{throw Error('Respuesta no válida del servidor.')}if(!r.ok)throw Error(d.error||'No se pudo completar la operación.');return d}
function nav(){const n=$('#nav');if(state.user){n.innerHTML=`<span>Hola, ${esc(state.user.name)}</span> <button class="secondary" id="logout">Cerrar sesión</button>`;$('#logout').onclick=logout}else{n.innerHTML='<button class="secondary" data-view="login">Iniciar sesión</button>';bind(n)}}
async function refresh(){const d=await api('session');state={user:d.user,csrf:d.csrf};nav();if(state.user)await dashboard();else view('home')}
function resetTaskForm(){editingId=null;const f=$('#taskForm');f.reset();$('#createPanel').querySelector('h2').textContent='Nueva tarea';$('#taskSubmit').textContent='Guardar tarea';$('#createPanel').hidden=true}
async function dashboard(){
 const u=state.user;
 $('#dashTitle').textContent=u.role==='profesor'?'Tareas que has creado':'Tareas de tu clase';
 $('#dashSubtitle').textContent=u.role==='profesor'?'Solo ves las tareas creadas por tu cuenta.':'Clase: '+u.class_name;
 $('#newTaskBtn').hidden=u.role!=='profesor';resetTaskForm();view('dashboard');
 try{
  const d=await api('tasks');$('#tasks').innerHTML='';$('#missedTasks').innerHTML='';
  const active=u.role==='alumno'?d.tasks.filter(t=>t.status!=='No entregada'):d.tasks;
  const missed=u.role==='alumno'?d.tasks.filter(t=>t.status==='No entregada'):[];
  $('#empty').hidden=active.length>0;
  $('#missedSection').hidden=missed.length===0;
  for(const t of active){
   const el=document.createElement('article');el.className='task';
   let status='';
   if(u.role==='alumno'){
    status=`<span class="status ${t.status==='Entregada'?'done':'pending'}">${esc(t.status)}</span>`;
    if(t.status==='Entregada'){
      status+=`<p class="submission-summary">Tu entrega: ${esc(t.submission_comment|| (t.submission_file?'Archivo adjunto enviado':'Enviada'))}</p>`;
      if(t.submission_file) status+=`<p><a href="${esc(t.submission_file)}" target="_blank" rel="noopener">Ver archivo enviado</a></p>`;
    }
   }
   el.innerHTML=`<h3>${esc(t.title)}</h3><p>${esc(t.description)}</p><small>Clase: ${esc(t.class_name)} · Entrega: ${esc(t.due_date)}</small><div class="status-row">${status}</div>${u.role==='profesor'?`<div class="task-actions"><button type="button" class="secondary edit-task">Modificar</button> <button type="button" class="danger delete-task">Eliminar</button></div>`:t.status==='Entregada'?'':`<div class="task-actions"><button type="button" class="submit-task">Hacer y enviar tarea</button></div>`}`;
   if(u.role==='profesor'){el.querySelector('.edit-task').onclick=()=>editTask(t);el.querySelector('.delete-task').onclick=()=>deleteTask(t)}
   else if(t.status!=='Entregada'){el.querySelector('.submit-task').onclick=()=>openSubmission(t)}
   $('#tasks').append(el);
  }
  for(const t of missed){
   const el=document.createElement('article');el.className='task missed-task';
   el.innerHTML=`<h3>${esc(t.title)}</h3><p>${esc(t.description)}</p><small>Fecha límite: ${esc(t.due_date)}</small><p><span class="status missed">No entregada</span></p>`;
   $('#missedTasks').append(el);
  }
  await loadSubmissions();
 }catch(e){note(e.message,true)}
}
function editTask(t){editingId=t.id;const f=$('#taskForm');f.elements.title.value=t.title;f.elements.description.value=t.description||'';f.elements.class_name.value=t.class_name;f.elements.due_date.value=String(t.due_date).slice(0,10);$('#createPanel').querySelector('h2').textContent='Modificar tarea';$('#taskSubmit').textContent='Guardar cambios';$('#createPanel').hidden=false;$('#createPanel').scrollIntoView({behavior:'smooth',block:'start'})}
async function deleteTask(t){if(!confirm(`¿Seguro que quieres eliminar la tarea «${t.title}»? Esta acción no se puede deshacer.`))return;try{await api('delete_task',{method:'POST',body:JSON.stringify({id:t.id})});await dashboard();note('Tarea eliminada.')}catch(e){note(e.message,true)}}
function openSubmission(t){$('#submissionForm').dataset.taskId=t.id;$('#submissionTaskTitle').textContent=t.title+' · Fecha límite: '+t.due_date;$('#submissionPanel').hidden=false;$('#submissionPanel').scrollIntoView({behavior:'smooth',block:'start'})}
async function loadSubmissions(){try{const d=await api('submissions');const section=$('#submissionsSection');const list=$('#submissionsList');$('#submissionsHeading').textContent=state.user.role==='profesor'?'Entregas recibidas de tus alumnos':'Historial de mis entregas';list.innerHTML='';section.hidden=d.submissions.length===0;for(const s of d.submissions){const el=document.createElement('div');el.className='submission-item';const file=s.file_path?`<p><a href="${esc(s.file_path)}" target="_blank" rel="noopener">Abrir archivo adjunto</a></p>`:'';el.innerHTML=`<strong>${esc(s.task_title||('Tarea #'+s.task_id))}</strong><p>${esc(s.student_name||'Tu entrega')} · ${esc(s.status)} · ${esc(s.submitted_at)}</p><p>${esc(s.comment||'')}</p>${file}`;list.append(el)}}catch(e){note(e.message,true)}}
async function submit(form,action){const d=Object.fromEntries(new FormData(form));try{const r=await api(action,{method:'POST',body:JSON.stringify(d)});state.user=r.user;state.csrf=r.csrf;nav();await dashboard();note(action==='register'?'Cuenta creada.':'Sesión iniciada.')}catch(e){note(e.message,true)}}
async function logout(){try{await api('logout',{method:'POST',body:'{}'});state={user:null,csrf:null};nav();view('home');note('Sesión cerrada.')}catch(e){note(e.message,true)}}
document.addEventListener('DOMContentLoaded',async()=>{bind();$('#loginForm').onsubmit=e=>{e.preventDefault();submit(e.currentTarget,'login')};$('#registerForm').onsubmit=e=>{e.preventDefault();submit(e.currentTarget,'register')};$('#newTaskBtn').onclick=()=>{resetTaskForm();$('#createPanel').hidden=false};$('#cancelTask').onclick=()=>resetTaskForm();$('#taskForm').onsubmit=async e=>{e.preventDefault();if(savingTask)return;savingTask=true;const form=e.currentTarget;const button=$('#taskSubmit');button.disabled=true;const d=Object.fromEntries(new FormData(form));try{if(editingId){d.id=editingId;await api('update_task',{method:'POST',body:JSON.stringify(d)});await dashboard();note('Tarea modificada correctamente.')}else{await api('tasks',{method:'POST',body:JSON.stringify(d)});await dashboard();note('Tarea creada correctamente.')}}catch(err){note(err.message,true)}finally{savingTask=false;button.disabled=false}};
$('#cancelSubmission').onclick=()=>{$('#submissionPanel').hidden=true;$('#submissionForm').reset()};
$('#submissionForm').onsubmit=async e=>{e.preventDefault();const form=e.currentTarget;const fd=new FormData(form);fd.set('task_id',form.dataset.taskId||'');const button=$('#submissionSubmit');button.disabled=true;try{const r=await fetch('api.php?action=submit_task',{method:'POST',body:fd,credentials:'same-origin',headers:state.csrf?{'X-CSRF-Token':state.csrf}:{}});let d;try{d=await r.json()}catch{throw Error('Respuesta no válida del servidor.')}if(!r.ok)throw Error(d.error||'No se pudo enviar la entrega.');form.reset();$('#submissionPanel').hidden=true;note('Entrega enviada correctamente.');await loadSubmissions()}catch(err){note(err.message,true)}finally{button.disabled=false}};
try{await refresh()}catch(e){note('No se pudo cargar la sesión. Comprueba database.php.',true)}});
