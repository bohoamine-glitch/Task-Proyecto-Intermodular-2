<?php
declare(strict_types=1);
$host='127.0.0.1';
$port='5432';
$dbname='taskClass';
// Respeta las mayúsculas de pgAdmin.
$user='postgres';
$password='postgres';
// Pon aquí tu contraseña local de PostgreSQL.
try {
    $pdo=new PDO("pgsql:host=$host;port=$port;dbname=$dbname",$user,$password,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
    ]);
}
catch(PDOException $e) {
    error_log('TaskClass DB: '.$e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa database.php y el registro de PHP.');
}
