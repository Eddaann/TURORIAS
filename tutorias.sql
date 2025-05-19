-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-05-2025 a las 16:53:10
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
-- Base de datos: `tutorias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avances`
--

CREATE TABLE `avances` (
  `ID_avance` int(11) NOT NULL,
  `ID_tutoria` int(11) DEFAULT NULL,
  `ID_tutor` int(11) DEFAULT NULL,
  `descripcion_avance` text DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `ID_estudiante` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`ID_estudiante`, `nombre`, `apellido`, `correo`, `contraseña`) VALUES
(1, 'Edgar Ivan Gomez Barragan', NULL, 'ivanedg29@gmail.com', '$2y$10$7/D2igQmQVhEvHp2xKIjV.DvnJGGETZ7in.2GqV9QxCtMZF9Pwtuy'),
(2, 'Francisco Tapia Armijo', NULL, 'holahola@gmail.com', '$2y$10$x3BwkXJm8tukVwK294FNcuHu0YuDZE7ki0Vpi/3fRCvSQCIoXwRXm'),
(3, 'Fernando Mojica', NULL, 'holaque@gmail.com', '$2y$10$PVeetkgO8X/kH4re3NTr7.EIewf.m897KzFCRG2vHaUk5W5sfK2qu');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `ID_materia` int(11) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `ID_reporte` int(11) NOT NULL,
  `ID_tutor` int(11) DEFAULT NULL,
  `fecha_realizacion` date DEFAULT NULL,
  `contenido` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutores`
--

CREATE TABLE `tutores` (
  `ID_tutor` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `area_especializacion` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutorías`
--

CREATE TABLE `tutorías` (
  `ID_tutoria` int(11) NOT NULL,
  `ID_estudiante` int(11) DEFAULT NULL,
  `ID_tutor` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `estado_tutoria` enum('Programada','Completada','Cancelada','Reprogramada') DEFAULT 'Programada',
  `modalidad` enum('Presencial','Online') DEFAULT 'Presencial',
  `ID_materia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `avances`
--
ALTER TABLE `avances`
  ADD PRIMARY KEY (`ID_avance`),
  ADD KEY `ID_tutoria` (`ID_tutoria`),
  ADD KEY `ID_tutor` (`ID_tutor`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`ID_estudiante`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`ID_materia`),
  ADD UNIQUE KEY `nombre_materia` (`nombre_materia`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`ID_reporte`),
  ADD KEY `ID_tutor` (`ID_tutor`);

--
-- Indices de la tabla `tutores`
--
ALTER TABLE `tutores`
  ADD PRIMARY KEY (`ID_tutor`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `tutorías`
--
ALTER TABLE `tutorías`
  ADD PRIMARY KEY (`ID_tutoria`),
  ADD KEY `ID_estudiante` (`ID_estudiante`),
  ADD KEY `ID_tutor` (`ID_tutor`),
  ADD KEY `tutorías_ibfk_3` (`ID_materia`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `avances`
--
ALTER TABLE `avances`
  MODIFY `ID_avance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `ID_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `ID_materia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `ID_reporte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tutores`
--
ALTER TABLE `tutores`
  MODIFY `ID_tutor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tutorías`
--
ALTER TABLE `tutorías`
  MODIFY `ID_tutoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `avances`
--
ALTER TABLE `avances`
  ADD CONSTRAINT `avances_ibfk_1` FOREIGN KEY (`ID_tutoria`) REFERENCES `tutorías` (`ID_tutoria`),
  ADD CONSTRAINT `avances_ibfk_2` FOREIGN KEY (`ID_tutor`) REFERENCES `tutores` (`ID_tutor`);

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`ID_tutor`) REFERENCES `tutores` (`ID_tutor`);

--
-- Filtros para la tabla `tutorías`
--
ALTER TABLE `tutorías`
  ADD CONSTRAINT `tutorías_ibfk_1` FOREIGN KEY (`ID_estudiante`) REFERENCES `estudiantes` (`ID_estudiante`),
  ADD CONSTRAINT `tutorías_ibfk_2` FOREIGN KEY (`ID_tutor`) REFERENCES `tutores` (`ID_tutor`),
  ADD CONSTRAINT `tutorías_ibfk_3` FOREIGN KEY (`ID_materia`) REFERENCES `materias` (`ID_materia`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
