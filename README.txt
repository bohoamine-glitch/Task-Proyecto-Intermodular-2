# TaskClass

**Aplicación web para gestionar tareas escolares entre profesores y alumnos.**

## 1. ¿Qué es TaskClass?

TaskClass es una aplicación web que permite a profesores y alumnos organizar y gestionar las tareas de clase desde un mismo lugar.

Los profesores pueden crear tareas, establecer fechas de entrega, modificarlas y eliminarlas.

Los alumnos pueden consultar las tareas de su clase y entregar sus trabajos mediante una respuesta escrita o un archivo adjunto.

El objetivo es facilitar la organización de las actividades escolares y ayudar a controlar las fechas de entrega.

## 2. Funcionalidades principales

### Para profesores

- Crear nuevas tareas.
- Asignar tareas a una clase.
- Establecer fechas de entrega.
- Modificar las tareas creadas.
- Eliminar sus propias tareas.
- Consultar las entregas realizadas por los alumnos.

### Para alumnos

- Crear una cuenta e iniciar sesión.
- Consultar las tareas de su clase.
- Ver las descripciones y fechas de entrega.
- Entregar tareas mediante texto o archivos.
- Consultar el estado de sus tareas.
- Ver las tareas no realizadas cuando ha terminado el plazo.

## 3. Estados de las tareas

TaskClass utiliza tres estados:

- **Pendiente:** la tarea todavía no se ha entregado y su fecha límite no ha pasado.
- **Entregada:** el alumno ha enviado su trabajo.
- **No entregada:** la fecha límite ha pasado y el alumno no ha realizado la entrega.

Las tareas no entregadas aparecen en un apartado llamado **Tareas no realizadas**.

## 4. Tecnologías utilizadas

El proyecto está desarrollado con las siguientes tecnologías:

| Tecnología     | Utilidad                                      |
| -------------- | --------------------------------------------- |
| HTML           | Estructura de las páginas                     |
| CSS            | Diseño de la aplicación                       |
| JavaScript     | Interactividad y comunicación con el servidor |
| PHP            | Lógica del servidor                           |
| PostgreSQL     | Almacenamiento de datos                       |
| Apache (XAMPP) | Ejecución de la aplicación localmente         |
| pgAdmin        | Administración de PostgreSQL                  |

## 5. Estructura del proyecto

Los principales archivos de TaskClass son:

```text
TaskClass/
│
├── index.php
├── api.php
├── database.php
├── schema.sql
├── script.js
├── style.css
│
└── uploads/
    └── .htaccess
```

### ¿Para qué sirve cada archivo?

- **index.php:** contiene la estructura principal y las diferentes vistas de la aplicación.
- **api.php:** gestiona el registro, el inicio de sesión, las tareas y las entregas.
- **database.php:** establece la conexión entre PHP y PostgreSQL.
- **schema.sql:** contiene las instrucciones para crear las tablas de la base de datos.
- **script.js:** controla la interacción de la aplicación y las peticiones al servidor.
- **style.css:** contiene los estilos y el diseño visual.
- **uploads/:** almacena los archivos enviados por los alumnos.

## 6. Base de datos

TaskClass utiliza PostgreSQL y trabaja con tres tablas principales:

### users

Almacena la información de los usuarios:

- Nombre.
- Correo electrónico.
- Contraseña protegida.
- Tipo de cuenta (alumno o profesor).
- Clase del alumno.

### tasks

Almacena las tareas creadas por los profesores:

- Profesor que ha creado la tarea.
- Título.
- Descripción.
- Clase asignada.
- Fecha de entrega.

### submissions

Almacena las entregas de los alumnos:

- Tarea correspondiente.
- Alumno que realiza la entrega.
- Respuesta escrita.
- Archivo adjunto.
- Estado de la entrega.
- Fecha de envío.

## 7. Instalación

Para ejecutar TaskClass en un ordenador local, sigue estos pasos:

### Paso 1. Instalar las herramientas

Necesitas tener instalados:

- XAMPP.
- PostgreSQL.
- pgAdmin.

### Paso 2. Copiar el proyecto

Copia la carpeta `TaskClass` dentro de:

```text
C:\xampp\htdocs\
```

### Paso 3. Crear la base de datos

1. Abre pgAdmin.
2. Crea una base de datos llamada `taskClass`.
3. Selecciona la base de datos.
4. Abre **Query Tool**.
5. Abre el archivo `schema.sql`.
6. Ejecuta las instrucciones SQL.

**Importante:** si ya tienes datos en las tablas, haz una copia de seguridad antes de ejecutar cambios en el esquema.

### Paso 4. Configurar la conexión

Abre `database.php` y configura los datos de conexión a PostgreSQL.

```php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'taskClass';
$user = 'postgres';
$password = 'postgres';
```

Sustituye `TU_CONTRASEÑA` por tu contraseña local de PostgreSQL.

No publiques contraseñas reales en GitHub.

### Paso 5. Iniciar la aplicación

1. Abre XAMPP.
2. Inicia Apache.
3. Comprueba que PostgreSQL está en ejecución.
4. Abre el navegador.
5. Accede a:

```text
http://localhost/TaskClass/
```

Si has utilizado otro nombre para la carpeta, cambia la dirección según corresponda.

## 8. Seguridad

TaskClass incorpora diferentes medidas de seguridad:

- Las contraseñas se almacenan mediante `password_hash()`.
- Las contraseñas se comprueban mediante `password_verify()`.
- Se utilizan sesiones para identificar a los usuarios.
- Las operaciones sensibles comprueban un token CSRF.
- Las consultas a PostgreSQL utilizan consultas preparadas.
- Los profesores solo pueden modificar y eliminar sus propias tareas.
- Los alumnos solo pueden consultar las tareas de su clase.
- Solo los profesores pueden crear tareas.
- Los archivos adjuntos tienen un límite de 10 MB y se comprueban sus extensiones permitidas.

**Nota:** esta versión está pensada para pruebas en un entorno local. Antes de publicarla en Internet será necesario añadir medidas adicionales de seguridad, como HTTPS, limitación de intentos de acceso y aprobación administrativa de las cuentas de profesor.

## 9. Entrega de tareas

Los alumnos pueden entregar sus trabajos de dos maneras:

- Escribiendo una respuesta en el formulario.
- Adjuntando un archivo.

Se permiten estos formatos:

- PDF
- DOC
- DOCX
- TXT
- JPG
- JPEG
- PNG
- ZIP

El tamaño máximo permitido es de 10 MB.

Si un alumno vuelve a enviar una tarea, la nueva entrega actualiza la anterior para esa misma tarea.

Los profesores pueden consultar las entregas de las tareas que han creado.

## 10. Datos de prueba

La aplicación no crea automáticamente usuarios ni tareas de demostración.

Para empezar a utilizarla:

1. Crea una cuenta de profesor.
2. Crea una cuenta de alumno.
3. Utiliza la misma clase para ambos.
4. Inicia sesión como profesor y crea una tarea.
5. Inicia sesión como alumno y consulta la tarea.
6. Realiza una entrega para comprobar su funcionamiento.

## 11. Objetivo del proyecto

El objetivo de TaskClass es desarrollar una herramienta sencilla que facilite la gestión de las tareas escolares, mejore la organización de alumnos y profesores y permita controlar las entregas y sus fechas límite.

---

**Proyecto desarrollado por Amine Ayar y Amine Boho.**

**Ciclo:** 2º Desarrollo de Aplicaciones Web (DAW).

