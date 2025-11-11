-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-11-2025 a las 12:55:12
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
-- Base de datos: `inspecciones`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id`, `codigo`, `nombre`, `orden`) VALUES
(1, 'S1', 'S1', 1),
(2, 'S2', 'S2', 2),
(3, 'S3', 'S3', 3),
(4, 'S4', 'S4', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist`
--

CREATE TABLE `checklist` (
  `id` int(11) NOT NULL,
  `file_rel` varchar(512) NOT NULL,
  `row_idx` int(11) NOT NULL,
  `estado` enum('si','no') DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `evidencia_path` varchar(512) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `checklist`
--

INSERT INTO `checklist` (`id`, `file_rel`, `row_idx`, `estado`, `observacion`, `evidencia_path`, `updated_at`) VALUES
(1, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 1, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(2, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 2, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(3, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 3, 'no', NULL, NULL, '2025-11-07 15:34:43'),
(4, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 4, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(5, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 5, 'no', NULL, NULL, '2025-11-07 15:34:43'),
(6, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 6, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(7, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 7, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(8, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 8, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(9, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 9, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(10, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 10, 'no', NULL, NULL, '2025-11-07 15:34:43'),
(11, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 11, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(12, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 12, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(13, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 13, 'no', NULL, NULL, '2025-11-07 15:34:43'),
(14, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 14, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(15, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 15, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(16, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 16, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(17, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 17, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(18, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 18, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(19, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 19, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(20, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 20, 'si', NULL, NULL, '2025-11-07 15:34:43'),
(21, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 21, 'no', NULL, NULL, '2025-11-07 15:34:43'),
(22, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 22, NULL, NULL, NULL, '2025-11-07 15:34:43'),
(23, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 23, NULL, NULL, NULL, '2025-11-07 15:34:43'),
(24, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 24, NULL, NULL, NULL, '2025-11-07 15:34:43'),
(25, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 25, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(26, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 26, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(27, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 27, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(28, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 28, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(29, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 29, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(30, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 30, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(31, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 31, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(32, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 32, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(33, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 33, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(34, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 34, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(35, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 35, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(36, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 36, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(37, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 37, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(38, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 38, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(39, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 39, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(40, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 40, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(41, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 41, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(42, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 42, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(43, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 43, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(44, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 44, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(45, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 45, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(46, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 46, NULL, NULL, NULL, '2025-11-07 15:34:44'),
(47, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 47, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(48, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 48, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(49, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 49, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(50, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 50, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(51, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 51, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(52, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 52, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(53, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 53, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(54, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 54, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(55, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 55, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(56, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 56, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(57, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 57, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(58, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 58, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(59, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 59, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(60, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 60, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(61, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 61, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(62, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 62, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(63, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 63, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(64, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 64, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(65, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 65, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(66, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 66, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(67, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 67, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(68, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 68, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(69, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 69, NULL, NULL, NULL, '2025-11-07 15:34:45'),
(70, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 70, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(71, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 71, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(72, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 72, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(73, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 73, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(74, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 74, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(75, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 75, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(76, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 76, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(77, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 77, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(78, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 78, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(79, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 79, NULL, NULL, NULL, '2025-11-07 15:34:46'),
(80, 'storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 80, NULL, NULL, NULL, '2025-11-07 15:34:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `ruta` varchar(255) DEFAULT NULL,
  `hash_sha1` varchar(64) DEFAULT NULL,
  `estado_ingesta` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos`
--

INSERT INTO `documentos` (`id`, `area_id`, `nombre_archivo`, `ruta`, `hash_sha1`, `estado_ingesta`) VALUES
(1, 1, 'Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección. (1).pdf', 'storage/pdf_ok/S1/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección. (1).pdf', 'c3fcfc5898ddbeed1738edb6729afb81110a63d6', 'ok'),
(2, 1, 'Lista Control Nro 1 - 0003 - Formación de Tropas.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0003 - Formación de Tropas.pdf', 'f52721525ce4d1d38249b5766fc609359ffc166f', 'ok'),
(3, 1, 'Lista Control Nro 1 - 0004 - Desfile a Pie.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0004 - Desfile a Pie.pdf', '76a38d7abda0d84ef0029130151460a23eb53585', 'ok'),
(4, 1, 'Lista Control Nro 1 - 0005 - Revista de Vehículos.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0005 - Revista de Vehículos.pdf', 'c7749e1f641b2e4f8625bf1bd735d9727483b070', 'ok'),
(5, 1, 'Lista Control Nro 1 - 0006 - Presentación de Cuadros.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0006 - Presentación de Cuadros.pdf', 'a81ce63567b20ae7a86323fccae0c9b9f3bc3771', 'ok'),
(6, 1, 'Lista Control Nro 1 - 0007 - Exposición del Jefe de Elemento.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0007 - Exposición del Jefe de Elemento.pdf', '593cbaffc8baf1b2b15ef46392ba2bc8cc3c9fcf', 'ok'),
(7, 1, 'Lista Control Nro 1 - 0009 - Recorrida de la Jefatura.pdf', 'storage/pdf_ok/S1/Lista Control Nro 1 - 0009 - Recorrida de la Jefatura.pdf', 'db38f3f9b3bd66bddd56acfd724ac2a8f21917ac', 'ok'),
(8, 1, 'Lista de Control Nro 1 - 0008 - Recorrida de Instalaciones..pdf', 'storage/pdf_ok/S1/Lista de Control Nro 1 - 0008 - Recorrida de Instalaciones..pdf', 'be5bc6a3ff8fbbc7fa0c3c33e370b46dfa75ee36', 'ok'),
(9, 1, 'Apresto de la FRI.pdf', 'storage/pdf_ok/S1/Apresto de la FRI.pdf', 'db77eebd312a5b26ffd2dccf60183010dee7d621', 'ok'),
(10, 1, 'Aviación de Ejército.pdf', 'storage/pdf_ok/S1/Aviación de Ejército.pdf', 'aed7b7e4f09589616efe5defd7935224b054ef9c', 'ok'),
(11, 1, 'BIENESTAR (Guarnicional).pdf', 'storage/pdf_ok/S1/BIENESTAR (Guarnicional).pdf', 'dcaa8221d594c53c427a656c84310dccf2448d65', 'ok'),
(12, 1, 'Bromatología y Salud Pública.pdf', 'storage/pdf_ok/S1/Bromatología y Salud Pública.pdf', '0319194151301b034f926426d1d12d7a8d3a22bb', 'ok'),
(13, 1, 'Dirección de Intendencia.pdf', 'storage/pdf_ok/S1/Dirección de Intendencia.pdf', '39f208cdb0f64675ed5278a6cea5111391cc1b40', 'ok'),
(14, 1, 'Divisiones; Secciones Intendencia Jurisdiccional - GGUU.pdf', 'storage/pdf_ok/S1/Divisiones; Secciones Intendencia Jurisdiccional - GGUU.pdf', '87ee1c077421964af9a79c545ffeaa74fbeb1d95', 'ok'),
(15, 1, 'Helipuerto de campaña.pdf', 'storage/pdf_ok/S1/Helipuerto de campaña.pdf', '2659e774230bdb17fe7884c7ca113a6fc54faeb8', 'ok'),
(16, 1, 'Informática.pdf', 'storage/pdf_ok/S1/Informática.pdf', '7f27e9b704d92485f2a393003341ff749403897d', 'ok'),
(17, 1, 'Justicia Militar - Pel(s) Just(s) Elem(s).pdf', 'storage/pdf_ok/S1/Justicia Militar - Pel(s) Just(s) Elem(s).pdf', 'b3e05f571d2720a1e42a5c148ba8d65d4e88a9b3', 'ok'),
(18, 1, 'Lista de control de Farmacias y depósito de Efectos Cl II Y IV San.pdf', 'storage/pdf_ok/S1/Lista de control de Farmacias y depósito de Efectos Cl II Y IV San.pdf', 'fb38a30ae2863fe0757cda576a552236f9dbfcc8', 'ok'),
(19, 1, 'LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 01.pdf', 'storage/pdf_ok/S1/LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 01.pdf', '198b4869a56720e8b42e2fa1265d090681be3a32', 'ok'),
(20, 1, 'LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 02.pdf', 'storage/pdf_ok/S1/LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 02.pdf', '55ea1a88b7f2008f74335716714205643cee41ca', 'ok'),
(21, 1, 'LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 03.pdf', 'storage/pdf_ok/S1/LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 03.pdf', '9e93ec9ec9435590416420e7edb522e795307fd2', 'ok'),
(22, 1, 'LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 04.pdf', 'storage/pdf_ok/S1/LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 04.pdf', 'bcaa7a911190ffa9d81a171e4ceaf1ba1f20e585', 'ok'),
(23, 1, 'Núcleo Instrucción Básico.pdf', 'storage/pdf_ok/S1/Núcleo Instrucción Básico.pdf', 'f414b86200c112cbcb320f3102a6433637d2120e', 'ok'),
(24, 1, 'ORGANIZACIÓN Y FUNCIONAMIENTO DE LA SECCIÓN SANIDAD DE UNIDAD.pdf', 'storage/pdf_ok/S1/ORGANIZACIÓN Y FUNCIONAMIENTO DE LA SECCIÓN SANIDAD DE UNIDAD.pdf', 'f76cc49597bb5fc859e29c501e89b0c7e86e8033', 'ok'),
(25, 1, 'Organización y funcionamiento de la Sección Sanidad.pdf', 'storage/pdf_ok/S1/Organización y funcionamiento de la Sección Sanidad.pdf', 'c1c416bfb0a18f618d1b40964077e8bc3b96cc1b', 'ok'),
(26, 1, 'Personal Civil - Cdo(s) - UU - Org(S).pdf', 'storage/pdf_ok/S1/Personal Civil - Cdo(s) - UU - Org(S).pdf', '0b6ecf05c7f5efdc1bca0756954db501a807bcef', 'ok'),
(27, 1, 'Personal Docente Civil.pdf', 'storage/pdf_ok/S1/Personal Docente Civil.pdf', '69c98e4c18d6b4a1215f3bc8b29179f40bb6727c', 'ok'),
(28, 1, 'Preservación del Medio Ambiente.pdf', 'storage/pdf_ok/S1/Preservación del Medio Ambiente.pdf', 'e5b439ab4bcd942e2f59268a7b0298e6a9f7f565', 'ok'),
(29, 1, 'Programación de AMI.pdf', 'storage/pdf_ok/S1/Programación de AMI.pdf', 'e009e88fa6a1aace0cb818aea364ff86a07eb398', 'ok'),
(30, 1, 'Protección Civil (GUB - GUC - Elementos).pdf', 'storage/pdf_ok/S1/Protección Civil (GUB - GUC - Elementos).pdf', '8c2c19e66398223dcc07076b34492f839f18b77f', 'ok'),
(31, 1, 'Red Guarnicional Movil.pdf', 'storage/pdf_ok/S1/Red Guarnicional Movil.pdf', '8ca225fb2e34ce456c079da19801d3fab6b4aa22', 'ok'),
(32, 1, 'Relaciones Institucionales y Ceremonial.pdf', 'storage/pdf_ok/S1/Relaciones Institucionales y Ceremonial.pdf', 'dea959007b8e0f56aaa544afa584460e70be7517', 'ok'),
(33, 1, 'Sección-Gupo Intendencia - Direcciones; Unidades,Subunidades Independientes.pdf', 'storage/pdf_ok/S1/Sección-Gupo Intendencia - Direcciones; Unidades,Subunidades Independientes.pdf', '4c5097bca9eaff9158337bbaedaa16860d201336', 'ok'),
(34, 1, 'SUCOIGE.pdf', 'storage/pdf_ok/S1/SUCOIGE.pdf', '7e1f6473e27850b4e4462ffb74dbfde7805f9d08', 'ok'),
(35, 1, 'UU con Perros de Guerra.pdf', 'storage/pdf_ok/S1/UU con Perros de Guerra.pdf', '59c74957485f3ef8dbbeaaa52302c3726dd58bcc', 'ok'),
(36, 1, 'Área Jurídica (Para todos los Elementos que tengan Oficial Auditor).pdf', 'storage/pdf_ok/S1/Área Jurídica (Para todos los Elementos que tengan Oficial Auditor).pdf', '0443523c9dbfe1a5133b922a25d3263a1bc6a161', 'ok'),
(37, 1, 'Lista de Control Nro 13-0001 - Seguridad y salud ocupacional.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0001 - Seguridad y salud ocupacional.pdf', '4ca68ce3b36863f71df34d240bd602536fb5f675', 'ok'),
(38, 1, 'Lista de Control Nro 13-0003 - Precursores químicos.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0003 - Precursores químicos.pdf', 'ff795c374d34ca625b8ce0eda9d1830f0ebb0271', 'ok'),
(39, 1, 'Lista de Control Nro 13-0005 - Seguridad e higiene en el trabajo - Establecimientos Rurales.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0005 - Seguridad e higiene en el trabajo - Establecimientos Rurales.pdf', '275741619294f85febb314e8fe633028c8fc7aa6', 'ok'),
(40, 1, 'Lista de Control Nro 13-0006 - Control en centros de producción.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0006 - Control en centros de producción.pdf', '6b37df209e98b959d5a23b8d61afb5eebd1afd21', 'ok'),
(41, 1, 'Lista de Control Nro 13-0007 - Control de producción - Direcciones.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0007 - Control de producción - Direcciones.pdf', 'ee631e8e76fda4e9a250691137d3ead81835950b', 'ok'),
(42, 1, 'Lista de Control Nro 13-0008 - Control de producción de Sastrería Militar.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0008 - Control de producción de Sastrería Militar.pdf', '1e27b85b0d5158efa32cbd491ebd439b590684ab', 'ok'),
(43, 1, 'Lista de Control Nro 13-0009 - Control de producción del B Int 601 ANTONIO DEL PINO (Compañía Autoabastecimiento).pdf', 'storage/pdf_ok/S1/Lista de Control Nro 13-0009 - Control de producción del B Int 601 ANTONIO DEL PINO (Compañía Autoabastecimiento).pdf', 'bdeb41b054ada5f8714aac10cad094a4c2d97803', 'ok'),
(44, 1, 'Lista de Control Nro 14-0001 - Supervisión Funcional DGE - CAAE.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0001 - Supervisión Funcional DGE - CAAE.pdf', '9135e95cc1893d35bdf6ad7ee11e1a0b8d671ea3', 'ok'),
(45, 1, 'Lista de Control Nro 14-0002 - Supervisión Funcional GGUU-Equivalentes.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0002 - Supervisión Funcional GGUU-Equivalentes.pdf', 'bd522ca4643727adbf0fea552845c05a9e285eec', 'ok'),
(46, 1, 'Lista de Control Nro 14-0003 - Supervisión Funcional Elementos dependientes de la DGE.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0003 - Supervisión Funcional Elementos dependientes de la DGE.pdf', '39dd8e19fce3a410af04328bf7c64ee0b2790589', 'ok'),
(47, 1, 'Lista de Control Nro 14-0004 - Supervisión Funcional Unidades y Subunidades Independientes de la FO.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0004 - Supervisión Funcional Unidades y Subunidades Independientes de la FO.pdf', '024ca1a7af6ea66e794d2abd2561f9c9f1de8943', 'ok'),
(48, 1, 'Lista de Control Nro 14-0005 - Pruebas de Aptitud Física Básica.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0005 - Pruebas de Aptitud Física Básica.pdf', 'b114eb3a8d2c403da5f63de643e6372903d18ee3', 'ok'),
(49, 1, 'Lista de Control Nro 14-0006 - Pasaje de la Pista de Combate.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0006 - Pasaje de la Pista de Combate.pdf', 'e6213a170f3ad87a3b3fc92c86bb6bc8e877dd01', 'ok'),
(50, 1, 'Lista de Control Nro 14-0007 - Marcha a Pie con Equipo.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0007 - Marcha a Pie con Equipo.pdf', 'd77e396444664907b12e1076810f13e17074ff17', 'ok'),
(51, 1, 'Lista de Control Nro 14-0008 - Lanzamiento de Granada de Mano.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0008 - Lanzamiento de Granada de Mano.pdf', '48a77b530e07bfff02e0a1dcc3121dee46d17c64', 'ok'),
(52, 1, 'Lista de Control Nro 14-0009 - Trepar la Cuerda.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0009 - Trepar la Cuerda.pdf', '1d3cfcda9a2686d1000cb5cfffe2d2a758bfe9e2', 'ok'),
(53, 1, 'Lista de Control Nro 14-0010 - PAFO-Transporte de Heridos.pdf', 'storage/pdf_ok/S1/Lista de Control Nro 14-0010 - PAFO-Transporte de Heridos.pdf', 'f15df45aee79a8fcbe3fe1cf4267f68419d7cf57', 'ok'),
(54, 2, 'Lista de Control Nro 15-0001 - Cursos del Ser Bda(s) Mil(s) (CMN - ESESC).pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0001 - Cursos del Ser Bda(s) Mil(s) (CMN - ESESC).pdf', '76261f9d6ab48459efb62ed55084d05dd43eb63f', 'ok'),
(55, 2, 'Lista de Control Nro 15-0002 - Bandas y Fanfarrias Militares - Elementos.pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0002 - Bandas y Fanfarrias Militares - Elementos.pdf', '018d8e7c32ec332d399a6bfba44c026892bfdd50', 'ok'),
(56, 2, 'Lista de Control Nro 15-0003 - Para Div Bda Mil (GGUUB - DGE - Cdo Guar Mil Bs As).pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0003 - Para Div Bda Mil (GGUUB - DGE - Cdo Guar Mil Bs As).pdf', 'e9a8746f5566227f5925e5fb69d78b600b0462a7', 'ok'),
(57, 2, 'Lista de Control Nro 15-0004 - Div Bda(S) Mil(s) - Dir Int.pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0004 - Div Bda(S) Mil(s) - Dir Int.pdf', 'dbd1744c80bc1a84b4c0e7d8ebc7d3f2c8421b6e', 'ok'),
(58, 2, 'Lista de Control Nro 15-0005 - Servicio Bda(s) Mil(s) - DGOD.pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0005 - Servicio Bda(s) Mil(s) - DGOD.pdf', '09659ba6a26d1c4b214b1a5dadc96a2421d3bcd1', 'ok'),
(59, 2, 'Lista de Control Nro 15-0006 - Instrumentos y Accesorios Musicales - Bandas y Fanfarrias Militares.pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0006 - Instrumentos y Accesorios Musicales - Bandas y Fanfarrias Militares.pdf', '0cca5af7ddae57ce4e1e88d67156ec9f48efd873', 'ok'),
(60, 2, 'Lista de Control Nro 15-0007 - TALLER CENTRAL DE INSTRUMENTOS Y ACCESORIOS MUSICALES – B Int 601.pdf', 'storage/pdf_ok/S2/Lista de Control Nro 15-0007 - TALLER CENTRAL DE INSTRUMENTOS Y ACCESORIOS MUSICALES – B Int 601.pdf', '3fbfd96d100ce921c59c2d3a32b5333d878c6826', 'ok'),
(61, 2, 'Lista de Control 12-0001 - Construcciones - Elemento.pdf', 'storage/pdf_ok/S2/Lista de Control 12-0001 - Construcciones - Elemento.pdf', '9404ef35c356ab4d364ea447c2f882dba8cc029f', 'ok'),
(62, 2, 'Lista de Control 12-0002 - Bienes Raíces.pdf', 'storage/pdf_ok/S2/Lista de Control 12-0002 - Bienes Raíces.pdf', '8a04464b9ac7560899e2227ee61c2ddeb6ce3e97', 'ok'),
(63, 2, 'Lista de Control 12-0003 - Construcciones Barrios Militares.pdf', 'storage/pdf_ok/S2/Lista de Control 12-0003 - Construcciones Barrios Militares.pdf', '835617e1285d257d4660c365f385f837ad606a06', 'ok'),
(64, 2, 'Lista de Control 12-0004 - Aspectos Relevantes para TODOS los Barrios Militares, BBRR y Construcciones..pdf', 'storage/pdf_ok/S2/Lista de Control 12-0004 - Aspectos Relevantes para TODOS los Barrios Militares, BBRR y Construcciones..pdf', '32dee2780620f68731a55b1aab1161dd1bdfeb01', 'ok'),
(65, 2, 'Lista Control Nro 16-0001 - Gestión Logística (GGUU, Organismos y Elementos).pdf', 'storage/pdf_ok/S2/Lista Control Nro 16-0001 - Gestión Logística (GGUU, Organismos y Elementos).pdf', '6af7bd0aa00913e3429286d28ca6e35c423a8ce5', 'ok'),
(66, 2, 'Lista Control Nro 16-0002 - Control de Gestión Logístico Elementos Usuarios.pdf', 'storage/pdf_ok/S2/Lista Control Nro 16-0002 - Control de Gestión Logístico Elementos Usuarios.pdf', 'eb6c9394ab01c3a18ea50990833a57154e66753d', 'ok'),
(67, 2, 'Lista Control Nro 16-0003 - Finanzas - Unidades e Institutos - Barrios Militares.pdf', 'storage/pdf_ok/S2/Lista Control Nro 16-0003 - Finanzas - Unidades e Institutos - Barrios Militares.pdf', 'd226f2e2a786b9659c66cb9ad4c5227db11010db', 'ok'),
(68, 2, 'Lista Control Nro 16-0004 - Finanzas - CGE.pdf', 'storage/pdf_ok/S2/Lista Control Nro 16-0004 - Finanzas - CGE.pdf', 'e57cfa2d7acce9de41c0bf37f83a01d1e82abf3b', 'ok'),
(69, 2, 'Lista Control Nro 16-0005 - Control de Gestión Logística - Funciones del G5 (GGUU, Direcciones, Agrupaciones, y Otros organismos).pdf', 'storage/pdf_ok/S2/Lista Control Nro 16-0005 - Control de Gestión Logística - Funciones del G5 (GGUU, Direcciones, Agrupaciones, y Otros organismos).pdf', 'bd850d1a600a60022623163c00cb4d34fd7b0db2', 'ok');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) DEFAULT NULL,
  `nro` varchar(50) DEFAULT NULL,
  `texto` text DEFAULT NULL,
  `obligatorio` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas`
--

CREATE TABLE `respuestas` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `estado` enum('si','no','na') DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `evidencia` varchar(255) DEFAULT NULL,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `xlsx_prefs`
--

CREATE TABLE `xlsx_prefs` (
  `file_rel` varchar(512) NOT NULL,
  `mode_num_is` enum('title','item') NOT NULL DEFAULT 'title',
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `xlsx_prefs`
--

INSERT INTO `xlsx_prefs` (`file_rel`, `mode_num_is`, `updated_at`) VALUES
('storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección. (1).xlsx', 'item', '2025-11-07 14:36:37'),
('storage/listas_control/S1/1. Área Formal/Liasta de Control Nro 1 - 0001 - Actividades previas a la Inspección..xlsx', 'item', '2025-11-10 14:54:41'),
('storage/listas_control/S1/1. Área Formal/Lista Control Nro 1 - 0003 - Formación de Tropas.xlsx', 'item', '2025-11-10 14:55:13'),
('storage/listas_control/S1/2. Campos de la Conducción/Apresto de la FRI.xlsx', 'item', '2025-11-07 13:20:50'),
('storage/listas_control/S1/2. Campos de la Conducción/LISTA DE CONTROL DEL PROCESO A CONTROLAR DE PERSONAL Nro 01.xlsx', 'item', '2025-11-07 15:53:57'),
('storage/visitas_de_estado_mayor/S1/2. Campos de la Conducción/BIENESTAR (Guarnicional).xlsx', 'item', '2025-11-11 11:12:32');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklist`
--
ALTER TABLE `checklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_file_row` (`file_rel`,`row_idx`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`);

--
-- Indices de la tabla `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documento_id` (`documento_id`);

--
-- Indices de la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indices de la tabla `xlsx_prefs`
--
ALTER TABLE `xlsx_prefs`
  ADD PRIMARY KEY (`file_rel`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `checklist`
--
ALTER TABLE `checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuestas`
--
ALTER TABLE `respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`);

--
-- Filtros para la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD CONSTRAINT `respuestas_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
