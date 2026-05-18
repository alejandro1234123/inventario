-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-05-2026 a las 21:55:38
-- Versión del servidor: 10.1.28-MariaDB
-- Versión de PHP: 5.6.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_concejo_municpal`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bienes`
--

CREATE TABLE `bienes` (
  `id_articulo` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_departamento` int(11) NOT NULL,
  `serial` varchar(100) NOT NULL,
  `nombre` varchar(150) NOT NULL DEFAULT '',
  `especificaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `estado` enum('excelente','bueno','regular','malo','baja') NOT NULL DEFAULT 'bueno',
  `fecha_adquisicion` date DEFAULT NULL,
  `valor_adquisicion` decimal(10,2) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT '$',
  `observaciones` text,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_registro` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `bienes`
--

INSERT INTO `bienes` (`id_articulo`, `id_categoria`, `id_departamento`, `serial`, `nombre`, `especificaciones`, `estado`, `fecha_adquisicion`, `valor_adquisicion`, `moneda`, `observaciones`, `fecha_registro`, `id_usuario_registro`, `activo`) VALUES
(1, 1, 2, 'SN-2026-001', 'Monitor LED', NULL, 'bueno', '2026-04-10', '300.00', '$', '', '2026-04-10 02:59:35', 1, 1),
(2, 1, 2, 'SN-2026-002', 'Monitor LED 2', NULL, 'regular', '2026-04-10', '200.00', 'EUR', '', '2026-04-10 03:00:35', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(50) NOT NULL,
  `descripcion` text,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`, `descripcion`, `activo`) VALUES
(1, 'Electronicos', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('codigo_admin', 'ADMIN2024', 'Código secreto para registrar administradores');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL,
  `nombre_departamento` varchar(100) NOT NULL,
  `descripcion` text,
  `responsable` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id_departamento`, `nombre_departamento`, `descripcion`, `responsable`, `activo`) VALUES
(1, 'Recursos Humanos', 'Recursos Humanos', 'Juan', 1),
(2, 'Bienes Muebles', '', 'Juana', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id_historial` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `estado_anterior` enum('excelente','bueno','regular','malo','baja') DEFAULT NULL,
  `estado_nuevo` enum('excelente','bueno','regular','malo','baja') NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` text,
  `id_usuario_cambio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id_historial`, `id_articulo`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`, `motivo`, `id_usuario_cambio`) VALUES
(1, 1, 'excelente', 'bueno', '2026-04-10 02:59:53', 'Deterioro', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_seguridad`
--

CREATE TABLE `preguntas_seguridad` (
  `id_ps` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `numero_pregunta` tinyint(1) NOT NULL COMMENT '1 o 2',
  `pregunta` varchar(255) NOT NULL,
  `respuesta_hash` varchar(255) NOT NULL COMMENT 'bcrypt de la respuesta en minúsculas',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Preguntas de seguridad para recuperación de contraseña';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `traslados`
--

CREATE TABLE `traslados` (
  `id_movimiento` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `id_departamento_origen` int(11) NOT NULL,
  `id_departamento_destino` int(11) NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo` text,
  `id_usuario_responsable` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `traslados`
--

INSERT INTO `traslados` (`id_movimiento`, `id_articulo`, `id_departamento_origen`, `id_departamento_destino`, `fecha_movimiento`, `motivo`, `id_usuario_responsable`) VALUES
(3, 2, 1, 2, '2026-04-10 03:01:03', 'Reasignacion', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `nivel_acceso` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `contraseña`, `nombre_completo`, `nivel_acceso`, `fecha_creacion`, `activo`) VALUES
(1, 'admin', '$2y$10$Gv7uzqk5cWJWReLxxybQ8eUpJ55SU5BIA4JxTYmtfx7hLRaZga86G', 'admin', 'admin', '2026-04-10 02:50:42', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bienes`
--
ALTER TABLE `bienes`
  ADD PRIMARY KEY (`id_articulo`),
  ADD UNIQUE KEY `serial` (`serial`),
  ADD KEY `id_departamento` (`id_departamento`),
  ADD KEY `id_usuario_registro` (`id_usuario_registro`),
  ADD KEY `articulos_ibfk_cat` (`id_categoria`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id_departamento`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_articulo` (`id_articulo`),
  ADD KEY `id_usuario_cambio` (`id_usuario_cambio`);

--
-- Indices de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  ADD PRIMARY KEY (`id_ps`),
  ADD UNIQUE KEY `uq_usuario_pregunta` (`id_usuario`,`numero_pregunta`);

--
-- Indices de la tabla `traslados`
--
ALTER TABLE `traslados`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_articulo` (`id_articulo`),
  ADD KEY `id_departamento_origen` (`id_departamento_origen`),
  ADD KEY `id_departamento_destino` (`id_departamento_destino`),
  ADD KEY `id_usuario_responsable` (`id_usuario_responsable`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bienes`
--
ALTER TABLE `bienes`
  MODIFY `id_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  MODIFY `id_ps` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `traslados`
--
ALTER TABLE `traslados`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bienes`
--
ALTER TABLE `bienes`
  ADD CONSTRAINT `bienes_ibfk_2` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bienes_ibfk_3` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bienes_ibfk_cat` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `estados`
--
ALTER TABLE `estados`
  ADD CONSTRAINT `estados_ibfk_1` FOREIGN KEY (`id_articulo`) REFERENCES `bienes` (`id_articulo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `estados_ibfk_2` FOREIGN KEY (`id_usuario_cambio`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  ADD CONSTRAINT `ps_ibfk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `traslados`
--
ALTER TABLE `traslados`
  ADD CONSTRAINT `traslados_ibfk_1` FOREIGN KEY (`id_articulo`) REFERENCES `bienes` (`id_articulo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `traslados_ibfk_2` FOREIGN KEY (`id_departamento_origen`) REFERENCES `departamentos` (`id_departamento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `traslados_ibfk_3` FOREIGN KEY (`id_departamento_destino`) REFERENCES `departamentos` (`id_departamento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `traslados_ibfk_4` FOREIGN KEY (`id_usuario_responsable`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
