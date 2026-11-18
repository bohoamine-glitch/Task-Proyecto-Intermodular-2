-- Ejecuta en pgAdmin dentro de la base taskClass. Haz copia de seguridad antes de modificar una base con datos.

CREATE TABLE IF NOT EXISTS users (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role VARCHAR(20) NOT NULL CHECK(role IN ('alumno',
    'profesor')),
    class_name VARCHAR(80),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CHECK(role <> 'alumno' OR class_name IS NOT NULL)
);

ALTER TABLE users ADD COLUMN IF NOT EXISTS class_name VARCHAR(80);

CREATE TABLE IF NOT EXISTS tasks (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    teacher_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    class_name VARCHAR(80) NOT NULL,
    due_date DATE NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS tasks_class_due_idx ON tasks(class_name,
    due_date);

CREATE INDEX IF NOT EXISTS tasks_teacher_idx ON tasks(teacher_id);

CREATE TABLE IF NOT EXISTS submissions (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
    student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    comment TEXT NOT NULL DEFAULT '',
    file_path TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'Entregada',
    submitted_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT submissions_task_student_unique UNIQUE(task_id,
    student_id)
);
