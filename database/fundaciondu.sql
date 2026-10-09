-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-09-2026 a las 06:35:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fundaciondu`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenios`
--

CREATE TABLE `convenios` (
  `id` int(11) NOT NULL,
  `institucion` varchar(180) NOT NULL,
  `pais` varchar(100) NOT NULL,
  `ciudad` varchar(120) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `ubicacion_google` varchar(500) DEFAULT NULL,
  `tipo` varchar(120) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `fecha_evento` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `hora_evento` time DEFAULT NULL,
  `lugar` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `link_inscripcion` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `estado` enum('proximo','finalizado','cancelado') NOT NULL DEFAULT 'proximo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `fecha_evento`, `hora_evento`, `lugar`, `imagen`, `descripcion`, `link_inscripcion`, `categoria`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Examen de Admision 2026 - II', '2026-12-06', '09:00:00', 'Sede Principal', NULL, NULL, 'https://www.untels.edu.pe/', NULL, 'proximo', '2026-09-27 23:16:33', '2026-09-27 23:16:33'),
(2, 'Taller de Orientacion Vocacional', '2026-10-14', '18:00:00', 'Campus Virtual', NULL, NULL, 'https://www.untels.edu.pe/', NULL, 'proximo', '2026-09-27 23:17:00', '2026-09-27 23:17:00'),
(3, 'Conferencia: El Futuro del Trabajo y la IA', '2026-09-30', '18:25:00', 'Auditorio Principal', '/fundaciondu/public/uploads/event_4296b0805906b43fea2536b50ba7f126.jpg', '<p>Te invitamos a formar parte del I Congreso Internacional en Ciencia de Datos e Inteligencia Artificial, un espacio donde expertos, investigadores y estudiantes se reunirán para explorar los últimos avances, herramientas e innovación global.</p>\n<p>Fechas: 24, 25 y 26 de noviembre</p>\n<p>Modalidad: 100% Virtual</p>\n<p>¿Qué encontrarás?</p>\n<p>-Conferencias magistrales y mesas de diálogo.</p>\n<p>-Talleres prácticos y experiencias compartidas.</p>\n<p>-Sesiones de networking internacional.</p>\n<p><strong>Ejes temáticos principales:</strong></p>\n<ol>\n<li>Machine Learning y Aplicaciones</li>\n<li>Ciencia de Datos y Aplicaciones</li>\n<li>Inteligencia Artificial y Aplicaciones</li>\n<li>Deep Learning y Aplicaciones</li>\n</ol>\n<p>¡Asegura tu lugar!</p>', 'https://www.untels.edu.pe/', NULL, 'proximo', '2026-09-27 23:17:22', '2026-09-28 19:22:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `popup_config`
--

CREATE TABLE `popup_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL DEFAULT '',
  `link_url` varchar(1000) NOT NULL DEFAULT '',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `display_seconds` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembros`
--

CREATE TABLE `miembros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `cargo` varchar(120) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 99,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `miembros`
--

INSERT INTO `miembros` (`id`, `nombre`, `cargo`, `orden`, `imagen`, `created_at`, `updated_at`) VALUES
(1, 'José Yudberto Vilca Ccolque', 'Vicepresidente', 2, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(2, 'Melissa Fatima Muñante Toledo', 'Secretaria', 3, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(3, 'Manuel Abelardo Alcántara Ramírez', 'Tesorero', 4, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(4, 'Edwin Augusto Vigo Sánchez', 'Vocal', 5, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31'),
(5, 'José Carlos Goicochea Ponce', 'Gerente general', 6, NULL, '2026-09-25 15:36:31', '2026-09-25 15:36:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `autor` varchar(180) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_evento` date DEFAULT NULL,
  `descripcion_corta` varchar(255) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `es_destacada` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `categoria`, `autor`, `imagen`, `fecha_evento`, `descripcion_corta`, `contenido`, `link`, `es_destacada`, `created_at`, `updated_at`) VALUES
(1, 'Ceremonia de Graduación 2026 - I: Una nueva generación de lideres para el país', 'Noticias', NULL, '/fundaciondu/public/uploads/img_6ab9a2227387c2.24987172.jpg', '2026-09-20', 'El evento contara con la participación de destacados ponentes..', 'Titular: Ceremonia de Graduación 2026 - I: Una nueva generación de lideres para el país', 'https://www.untels.edu.pe/', 0, '2026-09-27 23:09:22', '2026-09-28 06:09:22'),
(2, 'Ganadores del concurso “Innovación Sur Lima” presentan prototipos sostenibles', 'Noticias', NULL, '/fundaciondu/public/uploads/img_6ab9a248975e03.26850616.jpg', '2026-09-29', 'Proyectos enfocados en la desalinización de agua y energia para..', 'Descripción: Proyectos enfocados en la desalinización de agua y energia para...', 'https://www.untels.edu.pe/', 0, '2026-09-27 23:10:00', '2026-09-28 06:10:00'),
(3, 'FDU firma convenio historico con la Fundacion Carlos Slim para becas digitales', 'Noticias', NULL, '/fundaciondu/public/uploads/img_6ab9a26cb875e9.68566836.jpg', '2026-10-01', 'Mas de 500 estudiantes se beneficiaran con programas de certificacion', '<p>Descripción: Mas de 500 estudiantes se beneficiaran con programas de certificacion</p>', 'https://www.untels.edu.pe/', 0, '2026-09-27 23:10:36', '2026-09-29 11:02:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `autor` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `lugar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `duracion` varchar(120) DEFAULT NULL,
  `modalidad` varchar(20) DEFAULT NULL,
  `horario` varchar(180) DEFAULT NULL,
  `frecuencia` varchar(120) DEFAULT NULL,
  `dirigido_a` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `temario` longtext DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `certificacion` text DEFAULT NULL,
  `docentes` longtext DEFAULT NULL,
  `inversion` text DEFAULT NULL,
  `descuentos` text DEFAULT NULL,
  `vacantes` int(11) DEFAULT NULL,
  `contacto_telefono` varchar(60) DEFAULT NULL,
  `contacto_whatsapp` varchar(60) DEFAULT NULL,
  `contacto_correo` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `programas`
--

INSERT INTO `programas` (`id`, `titulo`, `categoria`, `imagen`, `autor`, `descripcion`, `link`, `fecha_inicio`, `fecha_fin`, `lugar`, `created_at`, `updated_at`, `duracion`, `modalidad`, `horario`, `frecuencia`, `dirigido_a`, `objetivos`, `temario`, `requisitos`, `certificacion`, `docentes`, `inversion`, `descuentos`, `vacantes`, `contacto_telefono`, `contacto_whatsapp`, `contacto_correo`) VALUES
(1, 'Big Data & Cloud Analytics', 'CIENCIA', '/fundaciondu/public/uploads/img_6ab9a0d56060c2.33980681.webp', 'Amazon AWS Academy', 'Procesamiento masivo de datos para la toma de decisiones corporativas basadas en evidencia.', 'https://www.untels.edu.pe/', NULL, NULL, NULL, '2026-09-27 23:03:49', '2026-09-27 23:03:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Diplomado en Interculturalidad', 'SOCIAL', '/fundaciondu/public/uploads/img_6ab9a10089d896.49830810.jpg', 'UNESCO Partner', 'Herramientas para el dialogo social y la gestión de conflictos en entornos multiculturales', 'https://www.untels.edu.pe/', NULL, NULL, NULL, '2026-09-27 23:04:32', '2026-09-27 23:04:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Ingeniería en Sostenibilidad', 'INGENIERIA', '/fundaciondu/public/uploads/img_6ab9a126d2a6a9.90926589.jpg', 'German Tech Union', 'Diseño de sistemas urbanos resilientes y gestión de energías renovables para comunidades costeras', 'https://www.untels.edu.pe/', NULL, NULL, NULL, '2026-09-27 23:05:10', '2026-09-27 23:05:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Desarrollo de IA Ética', 'GESTION', '/fundaciondu/public/uploads/img_6ab9a163f23333.78898010.jpg', 'MIT Partnership', 'Principios y algoritmos para la implementación de soluciones de inteligencia artificial socialmente responsables', 'https://www.untels.edu.pe/', NULL, NULL, NULL, '2026-09-27 23:06:11', '2026-09-27 23:06:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Desarrollo de IA Ética', 'TECNOLOGIA', '/fundaciondu/public/uploads/img_6ab9a18030d6b7.97553433.jpg', 'MIT Partnership', '<p>Principios y algoritmos para la implementación de soluciones de inteligencia artificial socialmente responsables</p>', 'https://www.untels.edu.pe/', NULL, NULL, NULL, '2026-09-27 23:06:40', '2026-09-29 04:00:08', NULL, NULL, NULL, NULL, NULL, NULL, '[]', NULL, NULL, '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Gestion Hospitalaria Avanzada', 'SALUD', '/fundaciondu/public/uploads/img_6ab9a19fcf60d9.67550403.jpg', 'Fundación Carlos Slim', 'Especialización en optimización de recursos y servicios de salud para el sector público y privado', 'https://www.untels.edu.pe/', '2026-09-28', '2026-10-01', NULL, '2026-09-27 23:07:11', '2026-09-28 17:11:47', '4 días', 'presencial', '08:30', 'Diaria', 'Estudiantes', 'Aprende Salud', '[{\"titulo\":\"Salud\",\"descripcion\":\"Salud\"}]', 'Estudiantes universitarios', 'SI', '[{\"foto\":\"/fundaciondu/public/uploads/img_6aba9fd34fbbf5.91496052.jpg\",\"nombre\":\"Enrique\",\"cargo\":\"Docente\",\"descripcion\":\"Docente\",\"correo\":\"enrique@gmail.com\",\"linkedin\":null}]', 'S/. 300', 'Estudiantes univesitarios', 50, '987654321', '987654321', 'informes@fundacion.org');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reclamaciones`
--

CREATE TABLE `reclamaciones` (
  `id` int(11) NOT NULL,
  `folio` varchar(24) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `numero_documento` varchar(20) DEFAULT NULL,
  `domicilio` varchar(250) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(180) NOT NULL,
  `tipo_bien` enum('producto','servicio') NOT NULL,
  `fecha_compra` date DEFAULT NULL,
  `bien_contratado` varchar(250) NOT NULL,
  `monto_reclamado` decimal(12,2) DEFAULT NULL,
  `tipo` enum('reclamo','queja') NOT NULL,
  `detalle` text NOT NULL,
  `pedido` text NOT NULL,
  `estado` enum('pendiente','respondida') NOT NULL DEFAULT 'pendiente',
  `respuesta` text DEFAULT NULL,
  `acepta_datos` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$ecAU10/.qBdr9jKeY2Y3TecLeNb8H/sn.p1PEQQk.VOi0PNvSmV0G', 'admin', '2026-09-25 15:37:59');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `miembros`
--
ALTER TABLE `miembros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reclamaciones`
--
ALTER TABLE `reclamaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `miembros`
--
ALTER TABLE `miembros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `reclamaciones`
--
ALTER TABLE `reclamaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
