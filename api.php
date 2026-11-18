<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function out(int $s,array $d): never {
    http_response_code($s);
    echo json_encode($d,JSON_UNESCAPED_UNICODE);
    exit;
}
function body(): array {
    $v=json_decode(file_get_contents('php://input')?:'{}',true);
    return is_array($v)?$v:[];
}
function logged(): array {
    if(empty($_SESSION['user'])) out(401,['error'=>'Debes iniciar sesión.']);
    return $_SESSION['user'];
}
function csrf(): void {
    $t=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if(empty($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$t)) out(403,['error'=>'Sesión de seguridad caducada. Recarga la página.']);
}
$a=$_GET['action']??'';
if($a==='session') {
    if(!empty($_SESSION['user'])&&empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    out(200,['user'=>$_SESSION['user']??null,'csrf'=>$_SESSION['csrf']??null]);
}
require_once __DIR__.'/database.php';
try {
    if($a==='register'&&$_SERVER['REQUEST_METHOD']==='POST') {
        $d=body();
        $name=trim((string)($d['name']??''));
        $email=strtolower(trim((string)($d['email']??'')));
        $pass=(string)($d['password']??'');
        $role=(string)($d['role']??'alumno');
        $class=trim((string)($d['class_name']??''));
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<10) out(422,['error'=>'Revisa nombre, correo y contraseña (mínimo 10 caracteres).']);
        if(!in_array($role,['alumno','profesor'],true)) out(422,['error'=>'Tipo de cuenta no válido.']);
        if($role==='alumno'&&$class==='') out(422,['error'=>'Indica tu clase.']);
        if($role==='profesor') $class=null;
        try {
            $q=$pdo->prepare('INSERT INTO users(name,email,password_hash,role,class_name) VALUES(?,?,?,?,?) RETURNING id,name,email,role,class_name');
            $q->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$class]);
            $u=$q->fetch();
        }
        catch(PDOException $e) {
            if($e->getCode()==='23505')out(409,['error'=>'Ya existe una cuenta con ese correo.']);
            throw $e;
        }
        session_regenerate_id(true);
        $_SESSION['user']=$u;
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        out(201,['user'=>$u,'csrf'=>$_SESSION['csrf']]);
    }
    if($a==='login'&&$_SERVER['REQUEST_METHOD']==='POST') {
        $d=body();
        $email=strtolower(trim((string)($d['email']??'')));
        $pass=(string)($d['password']??'');
        $q=$pdo->prepare('SELECT id,name,email,password_hash,role,class_name FROM users WHERE email=?');
        $q->execute([$email]);
        $u=$q->fetch();
        if(!$u||!password_verify($pass,$u['password_hash']))out(401,['error'=>'Correo o contraseña incorrectos.']);
        unset($u['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user']=$u;
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        out(200,['user'=>$u,'csrf'=>$_SESSION['csrf']]);
    }
    if($a==='logout'&&$_SERVER['REQUEST_METHOD']==='POST') {
        logged();
        csrf();
        $_SESSION=[];
        session_destroy();
        out(200,['ok'=>true]);
    }
    if($a==='tasks') {
        $u=logged();
        if($_SERVER['REQUEST_METHOD']==='GET') {
            if($u['role']==='profesor') {
                $q=$pdo->prepare('SELECT id,title,description,class_name,due_date FROM tasks WHERE teacher_id=? ORDER BY due_date,id DESC');
                $q->execute([$u['id']]);
            }
            else {
                $q=$pdo->prepare("SELECT t.id,t.title,t.description,t.class_name,t.due_date,
      s.id AS submission_id,s.comment AS submission_comment,s.file_path AS submission_file,s.submitted_at,
      CASE WHEN s.id IS NOT NULL THEN 'Entregada'
           WHEN t.due_date < CURRENT_DATE THEN 'No entregada'
           ELSE 'Pendiente' END AS status
      FROM tasks t LEFT JOIN submissions s ON s.task_id=t.id AND s.student_id=?
      WHERE t.class_name=? ORDER BY t.due_date,t.id DESC");
                $q->execute([$u['id'],$u['class_name']]);
            }
            out(200,['tasks'=>$q->fetchAll()]);
        }
        if($_SERVER['REQUEST_METHOD']==='POST') {
            csrf();
            if($u['role']!=='profesor')out(403,['error'=>'Solo los profesores pueden crear tareas.']);
            $d=body();
            $title=trim((string)($d['title']??''));
            $desc=trim((string)($d['description']??''));
            $class=trim((string)($d['class_name']??''));
            $date=(string)($d['due_date']??'');
            if($title===''||$class===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))out(422,['error'=>'Completa título, clase y fecha.']);
            $q=$pdo->prepare('INSERT INTO tasks(teacher_id,title,description,class_name,due_date) VALUES(?,?,?,?,?) RETURNING id,title,description,class_name,due_date');
            $q->execute([$u['id'],$title,$desc,$class,$date]);
            out(201,['task'=>$q->fetch()]);
        }
    }
    if($a==='submit_task'&&$_SERVER['REQUEST_METHOD']==='POST') {
        $u=logged();
        csrf();
        if($u['role']!=='alumno')out(403,['error'=>'Solo los alumnos pueden entregar tareas.']);
        $taskId=filter_var($_POST['task_id']??null,FILTER_VALIDATE_INT);
        $comment=trim((string)($_POST['comment']??''));
        if(!$taskId)out(422,['error'=>'Tarea no válida.']);
        $q=$pdo->prepare('SELECT id,class_name,due_date FROM tasks WHERE id=?');
        $q->execute([$taskId]);
        $task=$q->fetch();
        if(!$task)out(404,['error'=>'No se encontró la tarea.']);
        if(trim((string)($u['class_name']??''))!==trim((string)$task['class_name']))out(403,['error'=>'Esta tarea no pertenece a tu clase.']);
        if($task['due_date'] < date('Y-m-d'))out(410,['error'=>'El plazo de esta tarea ha terminado. Ya no puedes enviarla.']);
        $filePath=null;
        if(isset($_FILES['file'])&&$_FILES['file']['error']!==UPLOAD_ERR_NO_FILE) {
            $f=$_FILES['file'];
            if($f['error']!==UPLOAD_ERR_OK)out(422,['error'=>'No se pudo recibir el archivo.']);
            if($f['size']>10*1024*1024)out(422,['error'=>'El archivo supera el límite de 10 MB.']);
            $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            if(!in_array($ext,['pdf','doc','docx','txt','jpg','jpeg','png','zip'],true))out(422,['error'=>'Formato no permitido.']);
            $dir=__DIR__.'/uploads';
            if(!is_dir($dir)&&!mkdir($dir,0750,true))out(500,['error'=>'No se pudo preparar la carpeta de entregas.']);
            $stored=bin2hex(random_bytes(16)).'.'.$ext;
            if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$stored))out(500,['error'=>'No se pudo guardar el archivo.']);
            $filePath='uploads/'.$stored;
        }
        if($comment===''&&$filePath===null)out(422,['error'=>'Escribe una respuesta o adjunta un archivo.']);
        $q=$pdo->prepare("INSERT INTO submissions(task_id,student_id,comment,file_path,status,submitted_at) VALUES(?,?,?,?, 'Entregada', NOW()) ON CONFLICT(task_id,student_id) DO UPDATE SET comment=EXCLUDED.comment,file_path=COALESCE(EXCLUDED.file_path,submissions.file_path),status='Entregada',submitted_at=NOW() RETURNING id,task_id,student_id,comment,file_path,status,submitted_at");
        $q->execute([$taskId,$u['id'],$comment,$filePath]);
        out(201,['submission'=>$q->fetch()]);
    }
    if($a==='submissions'&&$_SERVER['REQUEST_METHOD']==='GET') {
        $u=logged();
        if($u['role']==='alumno') {
            $q=$pdo->prepare('SELECT s.id,s.task_id,s.comment,s.file_path,s.status,s.submitted_at FROM submissions s JOIN tasks t ON t.id=s.task_id WHERE s.student_id=? ORDER BY s.submitted_at DESC');
            $q->execute([$u['id']]);
        }
        else {
            $q=$pdo->prepare('SELECT s.id,s.task_id,s.student_id,s.comment,s.file_path,s.status,s.submitted_at,t.title AS task_title,u.name AS student_name FROM submissions s JOIN tasks t ON t.id=s.task_id JOIN users u ON u.id=s.student_id WHERE t.teacher_id=? ORDER BY s.submitted_at DESC');
            $q->execute([$u['id']]);
        }
        out(200,['submissions'=>$q->fetchAll()]);
    }
    if($a==='update_task'&&$_SERVER['REQUEST_METHOD']==='POST') {
        $u=logged();
        csrf();
        if($u['role']!=='profesor')out(403,['error'=>'Solo los profesores pueden modificar tareas.']);
        $d=body();
        $id=filter_var($d['id']??null,FILTER_VALIDATE_INT);
        $title=trim((string)($d['title']??''));
        $desc=trim((string)($d['description']??''));
        $class=trim((string)($d['class_name']??''));
        $date=(string)($d['due_date']??'');
        if(!$id||$title===''||$class===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))out(422,['error'=>'Completa título, clase y fecha correctamente.']);
        $q=$pdo->prepare('UPDATE tasks SET title=?, description=?, class_name=?, due_date=? WHERE id=? AND teacher_id=? RETURNING id,title,description,class_name,due_date');
        $q->execute([$title,$desc,$class,$date,$id,$u['id']]);
        $task=$q->fetch();
        if(!$task)out(404,['error'=>'No se encontró esa tarea en tu cuenta.']);
        out(200,['task'=>$task]);
    }
    if($a==='delete_task'&&$_SERVER['REQUEST_METHOD']==='POST') {
        $u=logged();
        csrf();
        if($u['role']!=='profesor')out(403,['error'=>'Solo los profesores pueden eliminar tareas.']);
        $d=body();
        $id=filter_var($d['id']??null,FILTER_VALIDATE_INT);
        if(!$id)out(422,['error'=>'Identificador de tarea no válido.']);
        $q=$pdo->prepare('DELETE FROM tasks WHERE id=? AND teacher_id=? RETURNING id');
        $q->execute([$id,$u['id']]);
        if(!$q->fetch())out(404,['error'=>'No se encontró esa tarea en tu cuenta.']);
        out(200,['ok'=>true]);
    }
    out(404,['error'=>'Acción no encontrada.']);
}
catch(Throwable $e) {
    error_log('TaskClass API: '.$e->getMessage());
    out(500,['error'=>'Error interno. Revisa el registro de PHP.']);
}
