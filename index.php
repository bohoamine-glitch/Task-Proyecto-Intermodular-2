<?php
session_start();
$u=$_SESSION['user']??null;
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>TaskClass</title>
    <link rel="stylesheet" href="style.css" />
    <script defer src="script.js"></script>
  </head>
  <body>
    <header>
      <a class="brand" href="index.php">TaskClass</a>
      <nav id="nav"></nav>
    </header>
    <main>
      <div id="notice" hidden></div>
      <section id="homeView" class="view">
        <div class="hero">
          <h1>Gestión de tareas para alumnos y profesores</h1>
          <p>Organiza, asigna y entrega tareas de forma sencilla.</p>
          <button data-view="login">Iniciar sesión</button>
          <button class="secondary" data-view="register">Crear cuenta</button>
        </div>
        <div class="features">
          <article>
            <h3>Organiza</h3>
            <p>Crea y gestiona tareas fácilmente.</p>
          </article>
          <article>
            <h3>Colabora</h3>
            <p>Alumnos y profesores en un mismo lugar.</p>
          </article>
          <article>
            <h3>Cumple plazos</h3>
            <p>Consulta las fechas de entrega.</p>
          </article>
        </div>
        <p class="muted">
          Las tareas solo se muestran después de iniciar sesión.
        </p>
      </section>
      <section id="loginView" class="view card" hidden>
        <h1>Iniciar sesión</h1>
        <form id="loginForm">
          <label>Correo<input type="email" name="email" required /> </label>
          <label
            >Contraseña<input type="password" name="password" required />
          </label>
          <button>Entrar</button>
        </form>
        <p>
          ¿No tienes cuenta?
          <button type="button" class="textbtn" data-view="register">
            Crear cuenta
          </button>
        </p>
      </section>
      <section id="registerView" class="view card" hidden>
        <h1>Crear cuenta</h1>
        <form id="registerForm">
          <label>Nombre<input name="name" maxlength="120" required /> </label>
          <label
            >Correo<input type="email" name="email" maxlength="254" required />
          </label>
          <label
            >Contraseña (mínimo 10 caracteres)<input
              type="password"
              name="password"
              minlength="10"
              required
            />
          </label>
          <label
            >Tipo de cuenta<select name="role">
              <option value="alumno">Alumno</option>
              <option value="profesor">Profesor</option>
            </select>
          </label>
          <label
            >Clase (para alumnos)<input
              name="class_name"
              maxlength="80"
              placeholder="Ej. 3ºA"
            />
          </label>
          <button>Crear cuenta</button>
        </form>
        <p>
          <small
            >Entorno local: para una web pública, las cuentas de profesor deben
            aprobarse por un administrador.</small
          >
        </p>
      </section>
      <section id="dashboardView" class="view" hidden>
        <div class="dashhead">
          <div>
            <h1 id="dashTitle">Mis tareas</h1>
            <p id="dashSubtitle"></p>
          </div>
          <button id="newTaskBtn" hidden>Crear tarea</button>
        </div>
        <div id="createPanel" class="card" hidden>
          <h2>Nueva tarea</h2>
          <form id="taskForm">
            <label
              >Título<input name="title" maxlength="180" required />
            </label>
            <label
              >Descripción<textarea name="description" rows="3"></textarea>
            </label>
            <label
              >Clase<input name="class_name" maxlength="80" required />
            </label>
            <label
              >Fecha de entrega<input type="date" name="due_date" required />
            </label>
            <button id="taskSubmit" type="submit">Guardar tarea</button>
            <button type="button" id="cancelTask" class="secondary">
              Cancelar
            </button>
          </form>
        </div>
        <div id="tasks" class="tasks"></div>
        <p id="empty" class="muted" hidden>
          No hay tareas disponibles para tu cuenta todavía.
        </p>
        <section id="missedSection" class="status-section" hidden>
          <h2>Tareas no realizadas</h2>
          <p class="muted">
            El plazo ha terminado y no consta ninguna entrega.
          </p>
          <div id="missedTasks" class="tasks"></div>
        </section>
        <div id="submissionPanel" class="card submission-panel" hidden>
          <h2>Entregar tarea</h2>
          <p id="submissionTaskTitle"></p>
          <form id="submissionForm" enctype="multipart/form-data">
            <label
              >Respuesta o comentario<textarea
                name="comment"
                rows="4"
                placeholder="Escribe tu respuesta (opcional si adjuntas un archivo)"
              ></textarea>
            </label>
            <label
              >Adjuntar archivo (máximo 10 MB)<input
                type="file"
                name="file"
                accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip"
              />
            </label>
            <button id="submissionSubmit" type="submit">Enviar entrega</button>
            <button type="button" class="secondary" id="cancelSubmission">
              Cancelar
            </button>
          </form>
        </div>
        <section
          id="submissionsSection"
          class="card submissions-section"
          hidden
        >
          <h2 id="submissionsHeading">Mis entregas</h2>
          <div id="submissionsList"></div>
        </section>
      </section>
    </main>
    <footer>TaskClass · Aplicación local</footer>
  </body>
</html>
