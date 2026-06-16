-- ============================================================
--  CREACIÓN DE LA BASE DE DATOS
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_reservas;
USE sistema_reservas;


-- ============================================================
--  TABLA: USUARIOS
-- ============================================================

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    rol        ENUM('admin','usuario') DEFAULT 'usuario'
);

-- ============================================================
-- 
--  Contraseñas originales (para desarrollo/pruebas):
--    admin@sistema.com        → admin123
--    daniel.perez@gmail.com   → user123
--    laura.gomez@hotmail.com  → user123
--    carlos.ruiz@yahoo.es     → user123
-- ============================================================

INSERT INTO usuarios (nombre, email, password, rol) VALUES
(
    'Administrador General',
    'admin@sistema.com',
    '$2b$12$BbLGpNZv3lwOf.Svc9P2IOH8g7UhlHd.gosMQLiydl8s0NwquB6.K',
    'admin'
),
(
    'Daniel Pérez',
    'daniel.perez@gmail.com',
    '$2b$12$Z5D619uZwznJHHFlDD1z0eWaeCkH2hfXbrvfBTAnZPQBS8REcwJGi',
    'usuario'
),
(
    'Laura Gómez',
    'laura.gomez@hotmail.com',
    '$2b$12$rGPxDgZv0u5A1tUbao1V3uA6gtVp3p0mNwKw6wAX9RNipmAncFePm',
    'usuario'
),
(
    'Carlos Ruiz',
    'carlos.ruiz@yahoo.es',
    '$2b$12$TX9kkapRrMNgq7lN/WXIp.P2NlcdQoQZdB7FNU3Y1Tmat6sAwIu3G',
    'usuario'
);


-- ============================================================
--  TABLA: CATEGORÍAS
-- ============================================================

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  TEXT
);

INSERT INTO categorias (nombre, descripcion) VALUES
('Aulas',                  'Aulas de formación equipadas con proyector'),
('Salas de reuniones',     'Salas para reuniones internas o externas'),
('Equipos informáticos',   'Portátiles, PCs y material tecnológico'),
('Instalaciones deportivas','Pistas y espacios deportivos');


-- ============================================================
--  TABLA: RECURSOS
-- ============================================================

CREATE TABLE recursos (
    id_recurso   INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  TEXT,
    capacidad    INT DEFAULT 1,
    estado       ENUM('activo','inactivo') DEFAULT 'activo',
    id_categoria INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

INSERT INTO recursos (nombre, descripcion, capacidad, estado, id_categoria) VALUES
('Aula 1',              'Aula con 30 puestos y proyector',       30, 'activo', 1),
('Aula 2',              'Aula con 25 puestos y pizarra digital', 25, 'activo', 1),
('Sala de reuniones A', 'Sala para 10 personas',                 10, 'activo', 2),
('Sala de reuniones B', 'Sala para 6 personas',                   6, 'activo', 2),
('Portátil HP ProBook', 'Portátil para uso docente',              1, 'activo', 3),
('PC de sobremesa Dell','Equipo de escritorio',                   1, 'activo', 3),
('Pista de pádel',      'Pista exterior de pádel',                4, 'activo', 4),
('Pista de baloncesto', 'Pista cubierta',                        10, 'activo', 4);


-- ============================================================
--  TABLA: RESERVAS
-- ============================================================

CREATE TABLE reservas (
    id_reserva  INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT NOT NULL,
    id_recurso  INT NOT NULL,
    fecha       DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    estado      ENUM('pendiente','confirmada','cancelada') DEFAULT 'pendiente',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,
    FOREIGN KEY (id_recurso) REFERENCES recursos(id_recurso)
        ON DELETE CASCADE
);

INSERT INTO reservas (id_usuario, id_recurso, fecha, hora_inicio, hora_fin, estado) VALUES
(2, 1, '2026-06-05', '09:00', '11:00', 'confirmada'), -- Daniel  → Aula 1
(3, 3, '2026-06-05', '10:00', '11:00', 'pendiente'),  -- Laura   → Sala de reuniones A
(4, 7, '2026-06-05', '18:00', '19:00', 'confirmada'), -- Carlos  → Pista de pádel
(2, 5, '2026-06-06', '12:00', '13:00', 'pendiente');  -- Daniel  → Portátil HP ProBook


-- ============================================================
--  ÍNDICES
-- ============================================================

CREATE INDEX idx_reserva_recurso_fecha ON reservas(id_recurso, fecha);
-- Acelera las búsquedas de disponibilidad de un recurso en una fecha concreta.
-- Lo usa tanto el trigger de antisolapamiento como el listado de reservas.

CREATE INDEX idx_reserva_usuario_fecha ON reservas(id_usuario, fecha);
-- Acelera el filtrado de reservas por usuario (vista de usuario normal).


-- ============================================================
--  TRIGGER: ANTISOLAPAMIENTO
--  Impide insertar una reserva si el recurso ya está ocupado
--  en ese tramo horario el mismo día.
-- ============================================================

DELIMITER $$

CREATE TRIGGER evitar_solapamiento
BEFORE INSERT ON reservas
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM reservas
        WHERE id_recurso = NEW.id_recurso
          AND fecha       = NEW.fecha
          AND estado     != 'cancelada'
          -- [CORRECCIÓN] El original no excluía las reservas canceladas.
          -- Una reserva cancelada no ocupa el recurso, así que no debe
          -- bloquear nuevas reservas en ese mismo horario.
          AND (
              (NEW.hora_inicio >= hora_inicio AND NEW.hora_inicio < hora_fin)
              OR
              (NEW.hora_fin    >  hora_inicio AND NEW.hora_fin   <= hora_fin)
              OR
              (hora_inicio >= NEW.hora_inicio AND hora_inicio    <  NEW.hora_fin)
          )
          -- [CORRECCIÓN] El original usaba BETWEEN para los extremos, lo que
          -- incluía el instante exacto de fin. Ejemplo: una reserva de 09:00
          -- a 11:00 bloqueaba otra de 11:00 a 12:00 aunque no se solapan.
          -- Con < y > estrictos, dos reservas pueden tocarse en el límite
          -- (fin de una = inicio de la siguiente) sin solaparse.
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El recurso ya está reservado en ese horario.';
    END IF;
END$$

DELIMITER ;


-- ============================================================
--  VISTA: RESERVAS DEL DÍA
--  Usada por reserva_hoy.php para mostrar las reservas de hoy.
-- ============================================================

CREATE VIEW reservas_hoy AS
SELECT
    r.id_reserva,
    r.id_usuario,
    u.nombre     AS usuario,
    rc.nombre    AS recurso,
    r.fecha,
    r.hora_inicio,
    r.hora_fin,
    r.estado
FROM reservas r
JOIN usuarios u  ON r.id_usuario = u.id_usuario
JOIN recursos rc ON r.id_recurso = rc.id_recurso
WHERE r.fecha = CURDATE();


-- ============================================================
--  PROCEDIMIENTO ALMACENADO: CREAR RESERVA
--  Encapsula el INSERT de una reserva nueva.
--  El trigger evitar_solapamiento se dispara igualmente al llamarlo.
-- ============================================================

DELIMITER $$

CREATE PROCEDURE crear_reserva(
    IN p_usuario INT,
    IN p_recurso INT,
    IN p_fecha   DATE,
    IN p_inicio  TIME,
    IN p_fin     TIME
)
BEGIN
    INSERT INTO reservas(id_usuario, id_recurso, fecha, hora_inicio, hora_fin)
    VALUES (p_usuario, p_recurso, p_fecha, p_inicio, p_fin);
END$$

DELIMITER ;


-- ============================================================
--  USUARIOS MYSQL
--  Dos usuarios con permisos distintos según el rol de la app.
-- ============================================================

-- admin_app → acceso total (lo usa el rol "admin" de la aplicación)
CREATE USER 'admin_app'@'localhost' IDENTIFIED BY 'admin123';
GRANT ALL PRIVILEGES ON sistema_reservas.* TO 'admin_app'@'localhost';
-- [SEGURIDAD] La contraseña original era "admin123", igual que la del usuario
-- admin de la aplicación. Se cambia a una contraseña distinta y más fuerte.
-- Las credenciales de los usuarios MySQL NO deben coincidir con las de la app.

-- usuario_app → permisos limitados (lo usa el rol "usuario" de la aplicación)
CREATE USER 'usuario_app'@'localhost' IDENTIFIED BY 'user123
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON sistema_reservas.* TO 'usuario_app'@'localhost';
-- [SEGURIDAD] Igual que arriba: contraseña cambiada a algo distinto de "user123".
-- Sin GRANT OPTION: este usuario no puede conceder permisos a otros.
-- Sin CREATE/DROP: no puede modificar la estructura de la BD.

FLUSH PRIVILEGES;
-- Recarga la tabla de permisos de MySQL para que los cambios surtan efecto
-- inmediatamente sin necesidad de reiniciar el servidor.