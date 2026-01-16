-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-01-2026 a las 00:12:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `eqf_helpdesk`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analyst_schedules`
--

CREATE TABLE `analyst_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift` varchar(50) NOT NULL,
  `sat_pattern` varchar(50) NOT NULL,
  `lunch_start` time DEFAULT NULL,
  `lunch_end` time DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `analyst_schedules`
--

INSERT INTO `analyst_schedules` (`id`, `user_id`, `shift`, `sat_pattern`, `lunch_start`, `lunch_end`, `created_at`, `updated_at`) VALUES
(1, 4, '8_1730', '1y3', '15:00:00', '16:00:00', '2025-12-24 12:44:53', '2025-12-29 15:54:03'),
(3, 3, '9_1830', 'todos', '14:00:00', '15:00:00', '2025-12-24 12:46:05', '2026-01-06 17:49:45'),
(4, 7, '8_1730', '2y4', '15:00:00', '16:00:00', '2025-12-24 12:46:20', '2025-12-25 21:43:10'),
(5, 5, '8_1730', '2y4', '13:00:00', '14:00:00', '2025-12-24 12:46:25', '2025-12-27 13:17:37'),
(10, 6, '8_1730', '1y3', '15:00:00', '16:00:00', '2025-12-25 21:43:51', NULL),
(11, 2, '8_1730', '1y3', '11:52:00', '11:55:00', '2025-12-25 21:44:04', '2026-01-12 11:50:47'),
(66, 1, '8_1730', '2y4', '14:00:00', '15:00:00', '2026-01-02 15:22:55', NULL),
(67, 8, '8_1730', '1y3', '14:00:00', '15:00:00', '2026-01-02 15:22:55', NULL),
(68, 12, '8_1730', '2y4', '15:00:00', '16:00:00', '2026-01-02 15:22:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analyst_status_overrides`
--

CREATE TABLE `analyst_status_overrides` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('AUTO','DISPONIBLE','NO_DISPONIBLE','VACACIONES','INCAPACIDAD','PERMISO','SUCURSAL') NOT NULL DEFAULT 'AUTO',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `until_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `analyst_status_overrides`
--

INSERT INTO `analyst_status_overrides` (`id`, `user_id`, `status`, `starts_at`, `ends_at`, `note`, `until_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'PERMISO', '2026-01-02 12:16:00', '2026-01-02 12:18:00', NULL, NULL, '2025-12-26 16:55:57', '2026-01-02 12:14:44'),
(2, 5, 'DISPONIBLE', '2025-12-26 17:11:00', '2025-12-26 21:11:00', NULL, NULL, '2025-12-26 17:11:39', '2025-12-26 17:11:43'),
(9, 7, 'VACACIONES', '2026-01-02 08:00:00', '2026-01-06 08:00:00', NULL, NULL, '2026-01-02 10:25:57', NULL),
(11, 3, 'DISPONIBLE', '2026-01-09 10:55:00', '2026-01-09 14:55:00', NULL, NULL, '2026-01-09 10:55:34', '2026-01-09 10:55:39'),
(13, 2, 'SUCURSAL', '2026-01-13 08:00:00', '2026-01-15 17:30:00', NULL, NULL, '2026-01-12 11:51:23', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` varchar(600) NOT NULL,
  `level` enum('INFO','WARN','CRITICAL') NOT NULL DEFAULT 'INFO',
  `target_area` varchar(20) NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_by_area` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `level`, `target_area`, `starts_at`, `ends_at`, `is_active`, `created_at`, `updated_at`, `created_by_user_id`, `created_by_area`) VALUES
(7, 'INTERNET', 'FUCKIN', 'WARN', 'Sucursal', NULL, NULL, 0, '2026-01-02 17:32:04', '2026-01-02 17:34:23', NULL, NULL),
(8, 'CARAJO', 'PRUEBA 2', 'CRITICAL', 'Sucursal', NULL, NULL, 0, '2026-01-02 17:32:12', '2026-01-02 17:34:10', NULL, NULL),
(9, 'INTERNET', 'SE REINCIARA EL INTERNET DEL CORPORATIVO', 'CRITICAL', 'Sucursal', '2026-01-02 17:38:00', NULL, 0, '2026-01-02 17:33:41', '2026-01-04 21:34:39', NULL, NULL),
(10, 'XD', 'SISIS', 'INFO', 'Sucursal', NULL, NULL, 0, '2026-01-02 18:04:12', '2026-01-04 21:34:43', NULL, NULL),
(11, 'REINICIO DEL INTERNET', 'SE REINICIARA EL INTERNET', 'INFO', 'Sucursal', '2026-01-04 21:37:00', NULL, 0, '2026-01-04 21:35:15', '2026-01-04 23:01:14', NULL, NULL),
(12, 'INTERNET', 'OKA', 'WARN', 'Corporativo', '2026-01-05 09:00:00', NULL, 0, '2026-01-05 08:28:38', '2026-01-05 10:28:45', NULL, 'TI'),
(13, 'INTERNET', 'X', 'INFO', 'Corporativo', NULL, NULL, 0, '2026-01-05 10:29:03', '2026-01-05 11:04:32', 1, 'TI'),
(14, 'okaya', 'sss', 'CRITICAL', 'Corporativo', NULL, NULL, 0, '2026-01-05 10:54:10', '2026-01-05 11:04:31', 1, 'TI'),
(15, 'INTERNET 4', 'P5', 'INFO', 'Corporativo', '2026-01-05 11:00:00', NULL, 0, '2026-01-05 11:04:21', '2026-01-05 11:09:27', 1, 'TI'),
(16, 'INTERNET', 'P6', 'CRITICAL', 'Corporativo', NULL, NULL, 0, '2026-01-05 11:13:11', '2026-01-05 11:22:52', 2, 'TI'),
(17, 'SAP NO FUNCIONA', 'NI FUNCIOANRA HOY', 'INFO', 'Corporativo', NULL, NULL, 0, '2026-01-05 11:14:02', '2026-01-05 11:22:27', 11, 'SAP'),
(18, 'SAP NO FUNCIONA', 'NI FUNCIOANRA', 'WARN', 'Corporativo', NULL, NULL, 0, '2026-01-05 11:23:26', '2026-01-05 11:24:41', 11, 'SAP'),
(19, 'SAP', 'NO HAY AREA DE SAP', 'WARN', 'Corporativo', NULL, NULL, 0, '2026-01-05 11:51:31', '2026-01-05 11:55:19', 11, 'SAP'),
(20, 'LEGADO LENTO', 'EL PUNTO DE VENTA ACTUALMENTE SE ENCUENTRA LENTO, FAVOR DE ESPERAR INDICACIONES', 'CRITICAL', 'Sucursal', NULL, NULL, 0, '2026-01-05 13:47:58', '2026-01-05 13:54:53', 2, 'TI'),
(21, 'ti', 't4', 'INFO', 'ALL', NULL, NULL, 0, '2026-01-05 13:58:55', '2026-01-05 15:15:44', 2, 'TI'),
(22, 'tit', 't4et', 'INFO', 'Sucursal', NULL, NULL, 0, '2026-01-05 13:59:19', '2026-01-05 15:15:47', 2, 'TI'),
(23, 'xd', 'xd', 'INFO', 'Sucursal', NULL, NULL, 0, '2026-01-05 15:15:58', '2026-01-05 15:18:41', 2, 'TI'),
(24, 'jmmmmmmmmmm', 'hhhh', 'CRITICAL', 'ALL', NULL, NULL, 0, '2026-01-05 15:17:26', '2026-01-05 15:18:44', 2, 'TI'),
(25, 's', 's', 'WARN', 'Sucursal', NULL, NULL, 0, '2026-01-05 15:51:31', '2026-01-05 15:52:04', 2, 'TI'),
(26, 's', 's', 'WARN', 'Sucursal', NULL, NULL, 0, '2026-01-05 15:53:52', '2026-01-05 15:53:59', 2, 'TI'),
(27, 'xx', 'xxx', 'INFO', 'ALL', NULL, NULL, 0, '2026-01-05 15:54:08', '2026-01-05 15:54:12', 2, 'TI'),
(28, 'NO HYA LEGADO', 'DD', 'INFO', 'Sucursal', NULL, NULL, 0, '2026-01-06 13:48:08', '2026-01-06 13:48:36', 2, 'TI'),
(29, 'NO FUNCIONA', 'NO FU', 'WARN', 'Sucursal', NULL, NULL, 0, '2026-01-06 17:46:11', '2026-01-06 17:46:42', 11, 'SAP');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actor_user_id` int(11) DEFAULT NULL,
  `actor_name` varchar(120) DEFAULT NULL,
  `actor_email` varchar(140) DEFAULT NULL,
  `actor_rol` tinyint(4) DEFAULT NULL,
  `actor_area` varchar(50) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity` varchar(60) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id`, `created_at`, `actor_user_id`, `actor_name`, `actor_email`, `actor_rol`, `actor_area`, `action`, `entity`, `entity_id`, `ip_address`, `user_agent`, `details`) VALUES
(1, '2025-12-17 18:27:40', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(2, '2025-12-17 18:28:31', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(3, '2025-12-17 18:28:36', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30660\",\"email\":\"gerente-ti@eqf.mx\",\"rol\":\"2\",\"area\":\"TI\"}'),
(4, '2025-12-17 18:30:35', 4, 'Dafne Lailson', 'ti5@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(5, '2025-12-17 18:30:52', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"26551\",\"email\":\"ti5@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(6, '2025-12-17 18:37:44', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(7, '2025-12-17 19:21:29', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"naucalpan@eqf.mx\"}'),
(8, '2025-12-17 19:21:47', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(9, '2025-12-17 19:26:02', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(10, '2025-12-17 19:26:10', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"ti6@eqf.mx\"}'),
(11, '2025-12-17 19:26:17', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(12, '2025-12-17 19:26:17', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\"}'),
(13, '2025-12-17 19:26:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(14, '2025-12-17 19:27:03', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(15, '2025-12-17 19:27:03', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(16, '2025-12-17 19:27:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(17, '2025-12-17 19:27:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(18, '2025-12-17 19:27:09', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(19, '2025-12-17 19:27:09', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(20, '2025-12-17 19:27:11', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(21, '2025-12-17 19:27:11', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(22, '2025-12-17 19:27:20', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(23, '2025-12-17 19:27:20', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(24, '2025-12-17 19:27:21', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(25, '2025-12-17 19:27:21', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 28, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(26, '2025-12-17 22:18:24', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"sa@sa.mx\"}'),
(27, '2025-12-17 22:18:35', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(28, '2025-12-18 15:37:04', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30660\",\"email\":\"gerente-ti@eqf.mx\",\"rol\":\"2\",\"area\":\"TI\"}'),
(29, '2025-12-18 15:38:10', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(30, '2025-12-18 15:38:10', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\"}'),
(31, '2025-12-18 15:38:33', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(32, '2025-12-18 16:24:29', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(33, '2025-12-18 16:24:37', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"administracion@eqf.mx\"}'),
(34, '2025-12-18 16:24:53', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"administracion@eqf.mx\"}'),
(35, '2025-12-18 16:25:02', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGIN_OK', 'users', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion@eqf.mx\",\"rol\":2,\"area\":\"SAP\"}'),
(36, '2025-12-18 16:26:09', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(37, '2025-12-18 16:29:01', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(38, '2025-12-18 16:29:55', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"sa@sa.mx\"}'),
(39, '2025-12-18 16:30:04', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(40, '2025-12-18 16:30:27', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"29366\",\"email\":\"administracion3@eqf.mx\",\"rol\":\"3\",\"area\":\"SAP\"}'),
(41, '2025-12-18 16:31:54', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(42, '2025-12-18 16:34:45', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(43, '2025-12-18 16:46:50', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(44, '2025-12-18 16:46:59', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(45, '2025-12-18 16:47:02', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(46, '2025-12-18 16:47:12', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"gerente-ti@eqf.mx\"}'),
(47, '2025-12-18 16:47:22', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(48, '2025-12-18 16:47:33', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(49, '2025-12-18 16:47:43', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(50, '2025-12-18 16:47:50', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(51, '2025-12-18 16:48:01', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(52, '2025-12-18 16:48:32', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(53, '2025-12-18 16:48:44', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(54, '2025-12-18 16:48:44', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\"}'),
(55, '2025-12-18 16:49:09', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(56, '2025-12-18 16:49:19', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(57, '2025-12-18 16:50:07', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(58, '2025-12-18 16:50:16', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"21643\",\"email\":\"administracion@eqf.mx\",\"rol\":\"2\",\"area\":\"SAP\"}'),
(59, '2025-12-18 16:50:20', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"29366\",\"email\":\"administracion3@eqf.mx\",\"rol\":\"3\",\"area\":\"SAP\"}'),
(60, '2025-12-18 16:50:39', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"26551\",\"email\":\"ti5@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(61, '2025-12-18 16:50:43', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30376\",\"email\":\"ti2@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(62, '2025-12-18 16:50:48', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"28822\",\"email\":\"ti@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(63, '2025-12-18 16:50:52', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"22983\",\"email\":\"ti4@eqf.mx\",\"rol\":\"2\",\"area\":\"TI\"}'),
(64, '2025-12-18 16:50:58', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"27889\",\"email\":\"ti3@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(65, '2025-12-18 17:06:23', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(66, '2025-12-18 17:06:34', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(67, '2025-12-18 17:11:02', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(68, '2025-12-18 17:11:10', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(69, '2025-12-18 17:19:28', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(70, '2025-12-18 17:19:38', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(71, '2025-12-18 17:19:46', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(72, '2025-12-18 17:20:11', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(73, '2025-12-18 17:20:14', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(74, '2025-12-18 17:20:22', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(75, '2025-12-18 17:21:24', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(76, '2025-12-18 17:21:32', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(77, '2025-12-18 17:21:52', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"sa@sa.mx\"}'),
(78, '2025-12-18 17:22:01', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(79, '2025-12-18 18:04:16', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(80, '2025-12-18 18:04:20', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(81, '2025-12-18 18:04:25', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_FAILED', 'password_recovery_requests', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(82, '2025-12-18 19:26:30', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(83, '2025-12-18 19:26:31', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(84, '2025-12-18 19:29:53', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(85, '2025-12-18 19:29:58', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(86, '2025-12-18 19:29:59', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(87, '2025-12-18 19:30:00', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(88, '2025-12-18 19:32:07', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 12, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(89, '2025-12-18 19:32:09', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 12, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(90, '2025-12-18 19:35:31', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(91, '2025-12-18 19:36:34', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 13, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(92, '2025-12-18 19:36:36', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 13, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti5@eqf.mx\"}'),
(93, '2025-12-18 19:36:46', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(94, '2025-12-18 19:58:41', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 14, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"ti@eqf.mx\"}'),
(95, '2025-12-18 19:58:43', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 14, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti@eqf.mx\"}'),
(96, '2025-12-18 19:58:48', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti@eqf.mx\"}'),
(97, '2025-12-18 20:00:26', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 15, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"ti2@eqf.mx\"}'),
(98, '2025-12-18 20:00:27', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 15, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"to\":\"sa_helpdesk@outlook.mx\",\"requester_email\":\"ti2@eqf.mx\"}'),
(99, '2025-12-18 20:00:32', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti2@eqf.mx\"}'),
(100, '2025-12-18 21:41:05', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(101, '2025-12-18 21:46:56', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(102, '2025-12-22 14:35:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(103, '2025-12-22 15:09:10', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"sa@sa.mx\"}'),
(104, '2025-12-22 15:09:18', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(105, '2025-12-22 15:33:30', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(106, '2025-12-22 15:42:09', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(107, '2025-12-22 15:42:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(108, '2025-12-22 16:51:33', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(109, '2025-12-22 16:51:40', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(110, '2025-12-22 16:51:45', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(111, '2025-12-22 16:51:58', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(112, '2025-12-22 18:02:16', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(113, '2025-12-22 18:02:25', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(114, '2025-12-22 18:11:00', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(115, '2025-12-22 18:11:07', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(116, '2025-12-22 18:11:45', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(117, '2025-12-22 18:18:07', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 30, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(118, '2025-12-22 18:18:07', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 30, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":null,\"to\":null}'),
(119, '2025-12-23 15:04:07', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(120, '2025-12-23 15:04:27', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(121, '2025-12-23 15:05:36', 46, 'Maria Sanchez', 'naucalpan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"naucalpan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(122, '2025-12-23 15:05:36', 46, 'Maria Sanchez', 'naucalpan@eqf.mx', 4, 'Sucursal', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"naucalpan@eqf.mx\"}'),
(123, '2025-12-23 15:05:48', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(124, '2025-12-23 15:14:15', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(125, '2025-12-23 15:14:28', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(126, '2025-12-23 15:14:28', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\"}'),
(127, '2025-12-23 15:14:49', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(128, '2025-12-23 15:16:14', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(129, '2025-12-23 15:17:55', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(130, '2025-12-23 22:02:26', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL),
(131, '2025-12-23 22:02:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(132, '2025-12-23 22:03:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_FAILED', 'password_recovery_requests', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(133, '2025-12-23 22:03:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(134, '2025-12-23 22:04:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_FAILED', 'password_recovery_requests', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(135, '2025-12-23 22:04:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(136, '2025-12-23 22:04:51', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(137, '2025-12-23 22:05:02', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(138, '2025-12-23 22:05:07', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(139, '2025-12-23 22:05:50', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(140, '2025-12-23 22:05:50', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(141, '2025-12-23 22:05:57', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL),
(142, '2025-12-23 22:06:01', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(143, '2025-12-23 22:06:08', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(144, '2025-12-23 22:07:01', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_FAILED', 'password_recovery_requests', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(145, '2025-12-23 22:07:08', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(146, '2025-12-23 22:12:00', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(147, '2025-12-23 22:12:01', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(148, '2025-12-23 22:12:58', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"sa@sa.mx\"}'),
(149, '2025-12-23 22:13:10', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"sa@sa.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(150, '2025-12-23 22:13:14', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(151, '2025-12-23 22:13:36', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"11111\",\"email\":\"soporte@eqf.mx\",\"rol\":\"1\",\"area\":\"Corporativo\"}'),
(152, '2025-12-23 22:13:47', 51, 'S A', 'sa@sa.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(153, '2025-12-23 22:13:52', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(154, '2025-12-23 22:13:53', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti5@eqf.mx\"}'),
(155, '2025-12-23 22:14:20', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(156, '2025-12-23 22:14:21', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(157, '2025-12-23 22:14:26', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(158, '2025-12-23 22:14:28', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(159, '2025-12-23 22:14:30', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(160, '2025-12-23 22:15:59', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(161, '2025-12-23 22:16:05', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti@eqf.mx\"}'),
(162, '2025-12-23 22:16:06', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti@eqf.mx\"}'),
(163, '2025-12-23 22:16:21', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(164, '2025-12-23 22:16:22', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti@eqf.mx\"}'),
(165, '2025-12-23 22:16:24', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(166, '2025-12-23 22:16:27', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(167, '2025-12-23 22:16:45', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"administracion3@eqf.mx\"}'),
(168, '2025-12-23 22:16:46', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"administracion3@eqf.mx\"}'),
(169, '2025-12-23 22:16:58', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(170, '2025-12-23 22:16:59', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"administracion3@eqf.mx\"}'),
(171, '2025-12-23 23:10:40', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(172, '2025-12-23 23:11:07', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(173, '2025-12-23 23:11:18', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(174, '2025-12-23 23:28:02', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(175, '2025-12-23 23:28:06', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(176, '2025-12-23 23:28:07', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(177, '2025-12-23 23:29:13', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(178, '2025-12-23 23:29:14', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(179, '2025-12-23 23:29:23', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(180, '2025-12-23 23:29:24', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(181, '2025-12-23 23:29:39', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(182, '2025-12-23 23:29:42', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(183, '2025-12-23 23:29:45', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}');
INSERT INTO `audit_log` (`id`, `created_at`, `actor_user_id`, `actor_name`, `actor_email`, `actor_rol`, `actor_area`, `action`, `entity`, `entity_id`, `ip_address`, `user_agent`, `details`) VALUES
(184, '2025-12-23 23:29:46', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(185, '2025-12-23 23:30:15', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(186, '2025-12-23 23:32:36', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(187, '2025-12-23 23:32:37', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(188, '2025-12-23 23:32:48', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(189, '2025-12-23 23:32:49', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti5@eqf.mx\"}'),
(190, '2025-12-23 23:33:00', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(191, '2025-12-23 23:33:17', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(192, '2025-12-23 23:33:19', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(193, '2025-12-24 16:54:37', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(194, '2025-12-24 16:54:42', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(195, '2025-12-24 16:54:43', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(196, '2025-12-24 17:00:22', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(197, '2025-12-24 17:00:23', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti5@eqf.mx\"}'),
(198, '2025-12-24 17:01:25', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(199, '2025-12-24 17:01:26', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(200, '2025-12-24 17:06:36', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(201, '2025-12-24 17:06:37', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(202, '2025-12-24 17:07:58', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(203, '2025-12-24 17:08:01', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"gerente-ti@eqf.mx\"}'),
(204, '2025-12-24 17:08:04', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti5@eqf.mx\"}'),
(205, '2025-12-24 17:08:05', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(206, '2025-12-24 17:08:06', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(207, '2025-12-24 17:12:12', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(208, '2025-12-24 17:12:17', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti2@eqf.mx\"}'),
(209, '2025-12-24 17:12:18', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti2@eqf.mx\"}'),
(210, '2025-12-24 17:12:33', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(211, '2025-12-24 17:12:37', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti2@eqf.mx\"}'),
(212, '2025-12-26 03:13:28', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"gerente-ti@eqf.mx\"}'),
(213, '2025-12-26 03:13:40', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(214, '2025-12-27 19:14:50', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(215, '2025-12-28 05:49:36', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(216, '2025-12-28 05:49:43', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(217, '2025-12-28 19:06:20', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(218, '2025-12-28 19:09:28', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":null,\"to\":null}'),
(219, '2025-12-28 19:10:25', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(220, '2025-12-28 19:10:37', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"soporte@eqf.mx\"}'),
(221, '2025-12-28 19:10:47', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(222, '2025-12-28 19:11:20', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(223, '2025-12-28 19:11:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(224, '2025-12-28 19:15:25', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":null,\"to\":null}'),
(225, '2025-12-28 20:05:05', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 34, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(226, '2025-12-29 02:20:18', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(227, '2025-12-29 02:20:28', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(228, '2025-12-29 03:05:17', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(229, '2025-12-29 03:05:23', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"ti5@eqf.mx\"}'),
(230, '2025-12-29 03:06:14', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"ti6@eqf.mx\"}'),
(231, '2025-12-29 03:06:23', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(232, '2025-12-29 04:06:51', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(233, '2025-12-29 04:07:00', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(234, '2025-12-29 04:18:22', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(235, '2025-12-29 04:18:31', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(236, '2025-12-29 04:22:27', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(237, '2025-12-29 04:22:38', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(238, '2025-12-29 04:48:57', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(239, '2025-12-29 05:07:14', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(240, '2025-12-29 05:07:54', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(241, '2025-12-29 05:07:58', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 38, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(242, '2025-12-29 05:13:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 39, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(243, '2025-12-29 14:49:02', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(244, '2025-12-29 15:00:32', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(245, '2025-12-29 15:06:32', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 40, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(246, '2025-12-29 15:14:14', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 41, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(247, '2025-12-29 15:43:49', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 42, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(248, '2025-12-29 16:00:52', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 43, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(249, '2025-12-29 16:20:35', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 44, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(250, '2025-12-29 16:24:41', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(251, '2025-12-29 16:24:52', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(252, '2025-12-29 16:29:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(253, '2025-12-29 16:30:14', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(254, '2025-12-29 16:31:28', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(255, '2025-12-29 16:31:29', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(256, '2025-12-29 19:34:12', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(257, '2025-12-29 19:34:22', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(258, '2025-12-29 19:53:45', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(259, '2025-12-29 19:53:55', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(260, '2025-12-29 19:53:57', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 35, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(261, '2025-12-29 19:53:59', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(262, '2025-12-29 20:01:38', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(263, '2025-12-29 22:13:23', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(264, '2025-12-29 22:13:31', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(265, '2025-12-29 22:15:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 45, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(266, '2025-12-29 22:19:32', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(267, '2025-12-29 22:19:49', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(268, '2025-12-29 22:26:25', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(269, '2025-12-29 22:26:36', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(270, '2025-12-29 22:35:51', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(271, '2025-12-29 22:38:13', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(272, '2025-12-29 22:38:23', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(273, '2025-12-29 22:41:26', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"11111\",\"email\":\"rrhh4@eqf.mx\",\"rol\":\"4\",\"area\":\"Corporativo\"}'),
(274, '2025-12-29 22:42:30', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"11111\",\"email\":\"juridico2@eqf.mx\",\"rol\":\"4\",\"area\":\"Corporativo\"}'),
(275, '2025-12-29 22:43:02', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 36, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"requester_email\":\"juridico2@eqf.mx\"}'),
(276, '2025-12-29 22:43:03', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 36, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"juridico2@eqf.mx\"}'),
(277, '2025-12-29 22:43:08', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 36, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"juridico2@eqf.mx\"}'),
(278, '2025-12-29 22:56:13', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(279, '2025-12-29 22:56:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(280, '2025-12-29 22:56:28', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(281, '2025-12-30 05:01:42', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(282, '2025-12-30 05:01:51', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"administracion3@eqf.mx\"}'),
(283, '2025-12-30 05:02:02', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(284, '2025-12-30 05:31:11', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(285, '2025-12-30 18:40:15', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(286, '2025-12-30 18:40:39', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 50, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(287, '2025-12-30 19:41:42', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(288, '2025-12-30 19:44:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(289, '2025-12-30 21:10:58', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 53, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(290, '2025-12-30 21:16:21', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(291, '2025-12-30 23:21:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(292, '2025-12-31 16:09:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(293, '2025-12-31 16:09:17', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(294, '2025-12-31 16:09:28', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(295, '2025-12-31 16:09:35', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL),
(296, '2025-12-31 16:09:42', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(297, '2025-12-31 16:09:45', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(298, '2025-12-31 16:12:38', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 57, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(299, '2025-12-31 16:18:38', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 58, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(300, '2025-12-31 16:27:46', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL),
(301, '2025-12-31 16:28:41', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(302, '2025-12-31 16:28:44', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"abierto\",\"to\":\"en_proceso\"}'),
(303, '2025-12-31 16:29:52', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 59, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(304, '2025-12-31 16:33:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 60, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(305, '2025-12-31 16:34:26', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 61, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(306, '2025-12-31 17:46:56', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 62, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(307, '2025-12-31 17:47:43', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 63, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(308, '2026-01-02 04:01:58', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(309, '2026-01-02 04:02:08', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(310, '2026-01-02 04:18:05', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(311, '2026-01-02 04:18:13', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(312, '2026-01-02 04:18:23', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 64, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(313, '2026-01-02 04:29:35', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 65, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(314, '2026-01-02 04:30:13', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 66, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(315, '2026-01-02 15:17:34', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(316, '2026-01-02 15:17:46', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(317, '2026-01-02 15:18:37', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(318, '2026-01-02 15:20:53', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(319, '2026-01-02 15:24:25', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(320, '2026-01-02 15:47:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(321, '2026-01-02 16:18:57', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 69, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(322, '2026-01-02 16:24:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(323, '2026-01-02 16:24:12', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(324, '2026-01-02 16:24:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(325, '2026-01-02 16:24:37', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(326, '2026-01-02 16:41:28', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(327, '2026-01-02 16:41:36', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"soporte@eqf.mx\"}'),
(328, '2026-01-02 16:41:45', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(329, '2026-01-02 19:27:13', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(330, '2026-01-02 19:27:23', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(331, '2026-01-02 19:43:07', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(332, '2026-01-02 19:43:39', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(333, '2026-01-02 19:59:00', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(334, '2026-01-02 19:59:13', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(335, '2026-01-02 21:08:46', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(336, '2026-01-02 21:34:07', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(337, '2026-01-02 21:34:15', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"soporte@eqf.mx\"}'),
(338, '2026-01-02 21:34:24', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(339, '2026-01-02 22:14:26', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(340, '2026-01-02 22:14:38', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"gerente-ti@eqf.mx\"}'),
(341, '2026-01-02 22:14:52', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(342, '2026-01-02 22:14:56', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(343, '2026-01-02 22:15:07', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(344, '2026-01-03 00:04:24', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(345, '2026-01-03 00:05:04', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(346, '2026-01-03 00:12:27', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 70, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(347, '2026-01-03 00:13:14', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(348, '2026-01-03 00:13:28', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(349, '2026-01-03 00:19:55', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 71, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(350, '2026-01-05 03:32:59', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(351, '2026-01-05 03:33:25', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(352, '2026-01-05 03:33:57', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(353, '2026-01-05 03:34:05', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(354, '2026-01-05 03:37:10', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(355, '2026-01-05 03:37:20', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(356, '2026-01-05 03:54:29', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(357, '2026-01-05 03:54:37', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(358, '2026-01-05 14:28:14', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(359, '2026-01-05 14:29:02', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(360, '2026-01-05 14:29:19', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(361, '2026-01-05 14:29:19', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\"}'),
(362, '2026-01-05 14:29:39', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(363, '2026-01-05 14:29:49', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(364, '2026-01-05 14:30:00', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(365, '2026-01-05 14:30:02', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(366, '2026-01-05 14:30:14', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(367, '2026-01-05 14:39:52', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(368, '2026-01-05 14:40:02', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(369, '2026-01-05 17:13:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL);
INSERT INTO `audit_log` (`id`, `created_at`, `actor_user_id`, `actor_name`, `actor_email`, `actor_rol`, `actor_area`, `action`, `entity`, `entity_id`, `ip_address`, `user_agent`, `details`) VALUES
(370, '2026-01-05 17:13:40', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(371, '2026-01-05 17:14:14', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(372, '2026-01-05 17:14:23', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"administracion@eqf.mx\"}'),
(373, '2026-01-05 17:14:30', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGIN_OK', 'users', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion@eqf.mx\",\"rol\":2,\"area\":\"SAP\"}'),
(374, '2026-01-05 17:14:30', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion@eqf.mx\"}'),
(375, '2026-01-05 17:14:52', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGIN_OK', 'users', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion@eqf.mx\",\"rol\":2,\"area\":\"SAP\"}'),
(376, '2026-01-05 17:22:37', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(377, '2026-01-05 17:22:46', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(378, '2026-01-05 17:22:57', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(379, '2026-01-05 17:23:06', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(380, '2026-01-05 17:23:43', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(381, '2026-01-05 17:23:52', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGIN_OK', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion@eqf.mx\",\"rol\":2,\"area\":\"SAP\"}'),
(382, '2026-01-05 17:49:53', 8, 'Darla Sanchez', 'administracion@eqf.mx', 2, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(383, '2026-01-05 17:50:07', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(384, '2026-01-05 17:50:13', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(385, '2026-01-05 17:51:09', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(386, '2026-01-05 19:09:34', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(387, '2026-01-05 19:10:00', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(388, '2026-01-05 19:42:51', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(389, '2026-01-05 19:44:04', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(390, '2026-01-05 19:47:20', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(391, '2026-01-05 21:19:54', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(392, '2026-01-05 21:20:05', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(393, '2026-01-05 21:51:57', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(394, '2026-01-05 21:51:59', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(395, '2026-01-05 22:13:09', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(396, '2026-01-05 22:13:19', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(397, '2026-01-05 23:08:41', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(398, '2026-01-05 23:08:46', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(399, '2026-01-05 23:09:06', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 72, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(400, '2026-01-05 23:09:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 72, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"abierto\",\"to\":\"en_proceso\"}'),
(401, '2026-01-05 23:09:14', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 73, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(402, '2026-01-05 23:09:17', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 73, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"abierto\",\"to\":\"en_proceso\"}'),
(403, '2026-01-05 23:30:16', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(404, '2026-01-05 23:30:26', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(405, '2026-01-05 23:31:02', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 73, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(406, '2026-01-05 23:31:21', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 72, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(407, '2026-01-05 23:36:51', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(408, '2026-01-05 23:37:03', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(409, '2026-01-05 23:38:05', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(410, '2026-01-05 23:38:19', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(411, '2026-01-05 23:38:36', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30533\",\"email\":\"juridico2@eqf.mx\",\"rol\":\"4\",\"area\":\"Corporativo\"}'),
(412, '2026-01-05 23:38:47', 33, 'Andrecito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(413, '2026-01-05 23:38:59', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(414, '2026-01-05 23:39:21', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30533\",\"email\":\"juridico2@eqf.mx\",\"rol\":\"4\",\"area\":\"Corporativo\"}'),
(415, '2026-01-05 23:39:24', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(416, '2026-01-05 23:39:40', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email_attempt\":\"juridico2@eqf.mx\"}'),
(417, '2026-01-05 23:39:49', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(418, '2026-01-05 23:39:49', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\"}'),
(419, '2026-01-05 23:40:13', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 33, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"juridico2@eqf.mx\",\"rol\":4,\"area\":\"Corporativo\"}'),
(420, '2026-01-06 16:54:10', NULL, NULL, NULL, NULL, NULL, 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"21338\",\"email\":\"mkt@eqf.mx\",\"rol\":\"3\",\"area\":\"MKT\"}'),
(421, '2026-01-06 16:57:52', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(422, '2026-01-06 16:58:45', 33, 'Andresito Lindo', 'juridico2@eqf.mx', 4, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(423, '2026-01-06 16:58:55', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(424, '2026-01-06 17:00:32', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 73, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(425, '2026-01-06 17:12:47', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(426, '2026-01-06 17:12:50', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(427, '2026-01-06 17:13:15', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(428, '2026-01-06 19:00:03', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(429, '2026-01-06 19:00:17', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(430, '2026-01-06 19:03:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(431, '2026-01-06 19:23:53', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(432, '2026-01-06 19:23:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(433, '2026-01-06 19:24:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(434, '2026-01-06 19:25:00', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(435, '2026-01-06 19:45:51', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 76, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(436, '2026-01-06 19:45:54', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"soporte\",\"to\":\"en_proceso\"}'),
(437, '2026-01-06 19:45:56', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(438, '2026-01-06 19:46:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"soporte\",\"to\":\"cerrado\"}'),
(439, '2026-01-06 19:46:10', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 73, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"soporte\",\"to\":\"cerrado\"}'),
(440, '2026-01-06 19:46:15', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(441, '2026-01-06 19:46:18', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(442, '2026-01-06 19:57:31', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(443, '2026-01-06 21:37:48', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(444, '2026-01-06 22:58:36', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(445, '2026-01-06 23:20:52', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(446, '2026-01-06 23:20:55', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(447, '2026-01-06 23:21:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(448, '2026-01-06 23:21:44', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(449, '2026-01-06 23:23:29', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(450, '2026-01-06 23:23:31', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(451, '2026-01-06 23:40:14', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(452, '2026-01-06 23:40:47', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(453, '2026-01-06 23:40:49', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(454, '2026-01-06 23:42:41', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(455, '2026-01-06 23:42:43', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(456, '2026-01-06 23:43:28', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(457, '2026-01-06 23:44:27', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(458, '2026-01-06 23:46:59', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"soporte\",\"to\":\"cerrado\"}'),
(459, '2026-01-06 23:47:40', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(460, '2026-01-06 23:48:06', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(461, '2026-01-06 23:48:09', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(462, '2026-01-06 23:48:54', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(463, '2026-01-06 23:48:56', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(464, '2026-01-06 23:49:06', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(465, '2026-01-06 23:49:08', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(466, '2026-01-06 23:49:10', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(467, '2026-01-06 23:50:00', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(468, '2026-01-06 23:50:03', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(469, '2026-01-06 23:52:54', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(470, '2026-01-06 23:52:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(471, '2026-01-06 23:53:40', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(472, '2026-01-06 23:53:43', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(473, '2026-01-07 14:38:40', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(474, '2026-01-07 14:38:42', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(475, '2026-01-07 14:38:57', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 74, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(476, '2026-01-07 14:39:00', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 74, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(477, '2026-01-07 14:39:05', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(478, '2026-01-07 14:39:07', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(479, '2026-01-07 14:39:23', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(480, '2026-01-07 14:39:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(481, '2026-01-07 14:39:43', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(482, '2026-01-07 14:40:18', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(483, '2026-01-07 14:40:20', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(484, '2026-01-07 14:46:10', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(485, '2026-01-07 14:46:25', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(486, '2026-01-07 14:46:28', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(487, '2026-01-07 14:46:33', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(488, '2026-01-07 15:02:08', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 79, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"soporte\"}'),
(489, '2026-01-07 15:02:10', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 79, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"soporte\",\"to\":\"cerrado\"}'),
(490, '2026-01-07 15:02:21', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(491, '2026-01-07 15:02:29', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(492, '2026-01-07 15:02:35', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(493, '2026-01-07 15:02:52', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(494, '2026-01-07 15:02:56', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(495, '2026-01-07 15:03:01', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(496, '2026-01-07 15:03:23', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(497, '2026-01-07 15:03:29', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(498, '2026-01-07 15:12:40', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(499, '2026-01-07 15:12:49', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(500, '2026-01-07 17:41:45', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 80, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(501, '2026-01-07 17:41:46', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(502, '2026-01-07 17:41:49', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(503, '2026-01-07 17:43:01', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(504, '2026-01-07 17:43:03', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(505, '2026-01-07 17:43:14', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(506, '2026-01-07 17:43:16', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(507, '2026-01-07 18:01:11', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 81, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(508, '2026-01-07 18:01:22', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(509, '2026-01-07 18:01:26', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(510, '2026-01-07 18:01:40', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(511, '2026-01-07 18:01:42', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(512, '2026-01-07 18:01:46', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 82, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"abierto\",\"to\":\"en_proceso\"}'),
(513, '2026-01-07 18:06:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(514, '2026-01-07 18:06:32', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(515, '2026-01-07 18:06:45', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(516, '2026-01-07 18:06:49', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(517, '2026-01-07 18:39:20', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(518, '2026-01-07 18:39:24', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(519, '2026-01-07 18:44:11', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'TICKET_STATUS_CHANGE', 'tickets', 82, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(520, '2026-01-07 18:44:26', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(521, '2026-01-07 18:44:30', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(522, '2026-01-07 18:44:37', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(523, '2026-01-07 18:44:40', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(524, '2026-01-07 19:05:12', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 83, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(525, '2026-01-07 19:36:44', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 84, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(526, '2026-01-07 19:43:02', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 85, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(527, '2026-01-07 19:43:12', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(528, '2026-01-07 19:43:23', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(529, '2026-01-07 19:53:29', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(530, '2026-01-07 19:53:37', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(531, '2026-01-07 21:47:51', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 86, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(532, '2026-01-07 21:48:16', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 86, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(533, '2026-01-07 21:56:43', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 86, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"abierto\"}'),
(534, '2026-01-07 22:20:43', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'TICKET_STATUS_CHANGE', 'tickets', 86, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"from\":\"en_proceso\",\"to\":\"cerrado\"}'),
(535, '2026-01-07 23:21:09', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(536, '2026-01-07 23:21:14', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(537, '2026-01-07 23:21:16', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(538, '2026-01-07 23:21:18', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(539, '2026-01-07 23:22:04', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(540, '2026-01-07 23:36:02', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(541, '2026-01-07 23:40:47', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(542, '2026-01-08 04:52:52', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(543, '2026-01-08 14:24:46', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(544, '2026-01-08 14:25:04', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(545, '2026-01-08 19:03:19', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(546, '2026-01-08 19:03:25', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(547, '2026-01-08 19:20:33', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(548, '2026-01-08 19:20:38', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(549, '2026-01-08 19:22:02', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(550, '2026-01-08 22:40:48', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(551, '2026-01-08 22:41:40', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(552, '2026-01-09 16:37:05', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(553, '2026-01-09 16:37:08', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(554, '2026-01-09 16:37:19', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(555, '2026-01-09 16:37:27', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(556, '2026-01-09 16:37:36', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(557, '2026-01-09 16:37:43', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(558, '2026-01-09 16:37:47', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(559, '2026-01-09 16:37:49', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(560, '2026-01-09 16:37:50', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(561, '2026-01-09 16:37:56', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(562, '2026-01-09 16:37:57', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL),
(563, '2026-01-09 16:38:04', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}');
INSERT INTO `audit_log` (`id`, `created_at`, `actor_user_id`, `actor_name`, `actor_email`, `actor_rol`, `actor_area`, `action`, `entity`, `entity_id`, `ip_address`, `user_agent`, `details`) VALUES
(564, '2026-01-09 18:52:31', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email_attempt\":\"ti5@eqf.mx\"}'),
(565, '2026-01-09 18:52:39', 4, 'Dafne Lailson', 'ti5@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"ti5@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(566, '2026-01-09 18:52:39', 4, 'Dafne Lailson', 'ti5@eqf.mx', 3, 'TI', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"ti5@eqf.mx\"}'),
(567, '2026-01-09 18:52:56', 4, 'Dafne Lailson', 'ti5@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"email\":\"ti5@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(568, '2026-01-11 04:09:03', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(569, '2026-01-11 22:45:18', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(570, '2026-01-11 22:45:29', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(571, '2026-01-12 01:09:45', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGIN_OK', 'users', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"cuautitlan@eqf.mx\",\"rol\":4,\"area\":\"Sucursal\"}'),
(572, '2026-01-12 01:12:31', 48, 'Hector Martinez', 'cuautitlan@eqf.mx', 4, 'Sucursal', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(573, '2026-01-12 01:12:37', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(574, '2026-01-12 01:15:23', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(575, '2026-01-12 01:15:35', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(576, '2026-01-12 01:17:51', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(577, '2026-01-12 01:18:12', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(578, '2026-01-12 01:19:05', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":null,\"email\":null,\"rol\":null,\"area\":null}'),
(579, '2026-01-12 01:19:05', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_UPDATE', 'users', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"changed\":[\"number_sap\",\"name\",\"last_name\",\"email\",\"rol\",\"area\"],\"reset_password\":false}'),
(580, '2026-01-12 01:19:20', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"00000\",\"email\":\"soporte@eqf.mx\",\"rol\":\"1\",\"area\":\"Corporativo\"}'),
(581, '2026-01-12 01:24:10', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(582, '2026-01-12 01:24:22', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_REQUEST_CREATED', 'password_recovery_requests', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(583, '2026-01-12 01:24:24', NULL, NULL, NULL, NULL, NULL, 'RECOVERY_EMAIL_SENT', 'password_recovery_requests', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"to\":\"soporte@eqf.mx\",\"requester_email\":\"ti6@eqf.mx\"}'),
(584, '2026-01-12 01:24:32', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(585, '2026-01-12 01:26:07', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"30378\",\"email\":\"ti6@eqf.mx\",\"rol\":\"3\",\"area\":\"TI\"}'),
(586, '2026-01-12 01:26:18', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'RECOVERY_REQUEST_ATTENDED', 'password_recovery_requests', 37, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"requester_email\":\"ti6@eqf.mx\"}'),
(587, '2026-01-12 01:26:44', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(588, '2026-01-12 01:26:51', NULL, NULL, NULL, NULL, NULL, 'AUTH_LOGIN_FAIL', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email_attempt\":\"ti6@eqf.mx\"}'),
(589, '2026-01-12 01:27:01', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(590, '2026-01-12 01:27:01', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_FORCE_PASSWORD_CHANGE', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\"}'),
(591, '2026-01-12 01:27:27', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGIN_OK', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"ti6@eqf.mx\",\"rol\":3,\"area\":\"TI\"}'),
(592, '2026-01-12 01:46:14', 2, 'Brandon Suarez', 'ti6@eqf.mx', 3, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(593, '2026-01-12 01:46:23', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGIN_OK', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"administracion3@eqf.mx\",\"rol\":3,\"area\":\"SAP\"}'),
(594, '2026-01-12 04:55:34', 11, 'Aidee Jimenez', 'administracion3@eqf.mx', 3, 'SAP', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(595, '2026-01-12 04:55:37', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(596, '2026-01-12 04:57:52', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(597, '2026-01-12 04:57:53', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(598, '2026-01-12 05:20:39', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(599, '2026-01-12 05:20:42', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(600, '2026-01-12 05:40:51', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(601, '2026-01-12 05:45:46', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(602, '2026-01-12 17:21:42', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGIN_OK', 'users', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"soporte@eqf.mx\",\"rol\":1,\"area\":\"Corporativo\"}'),
(603, '2026-01-12 17:23:22', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":null,\"email\":null,\"rol\":null,\"area\":null}'),
(604, '2026-01-12 17:23:22', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_UPDATE', 'users', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"changed\":[\"number_sap\",\"name\",\"last_name\",\"email\",\"rol\",\"area\"],\"reset_password\":false}'),
(605, '2026-01-12 17:23:44', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":\"11111\",\"email\":\"soporte@eqf.mx\",\"rol\":\"1\",\"area\":\"Corporativo\"}'),
(606, '2026-01-12 17:24:27', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_CREATE', 'users', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"sap\":null,\"email\":null,\"rol\":null,\"area\":null}'),
(607, '2026-01-12 17:24:27', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'USER_UPDATE', 'users', 52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"changed\":[\"number_sap\",\"name\",\"last_name\",\"email\",\"rol\",\"area\"],\"reset_password\":false}'),
(608, '2026-01-12 17:48:02', 51, 'S A', 'soporte@eqf.mx', 1, 'Corporativo', 'AUTH_LOGOUT', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL),
(609, '2026-01-12 17:48:05', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(610, '2026-01-12 19:48:55', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}'),
(611, '2026-01-12 19:49:13', 1, 'Israel Rico', 'gerente-ti@eqf.mx', 2, 'TI', 'AUTH_LOGIN_OK', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"email\":\"gerente-ti@eqf.mx\",\"rol\":2,\"area\":\"TI\"}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `actor_user_id` int(11) DEFAULT NULL,
  `actor_email` varchar(190) DEFAULT NULL,
  `actor_rol` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity` varchar(80) NOT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_absence_reasons`
--

CREATE TABLE `catalog_absence_reasons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_absence_reasons`
--

INSERT INTO `catalog_absence_reasons` (`id`, `code`, `label`, `active`, `sort_order`, `created_at`) VALUES
(1, 'VACACIONES', 'Vacaciones', 1, 10, '2026-01-02 11:26:40'),
(2, 'INCAPACIDAD', 'Incapacidad', 1, 20, '2026-01-02 11:26:40'),
(3, 'PERMISO', 'Permiso', 1, 30, '2026-01-02 11:26:40'),
(4, 'SUCURSAL', 'Sucursal', 1, 40, '2026-01-02 11:26:40'),
(5, 'DISPONIBLE', 'Disponibble', 1, 50, '2026-01-02 11:26:40'),
(6, 'NO_DISPONIBLE', 'No disponible', 1, 60, '2026-01-02 11:26:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_areas`
--

CREATE TABLE `catalog_areas` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_areas`
--

INSERT INTO `catalog_areas` (`id`, `code`, `label`, `active`, `sort_order`, `created_at`) VALUES
(1, 'TI', 'TI', 1, 10, '2025-12-16 15:12:25'),
(2, 'SAP', 'SAP', 1, 20, '2025-12-16 15:12:25'),
(3, 'MKT', 'MKT', 1, 30, '2025-12-16 15:12:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_priorities`
--

CREATE TABLE `catalog_priorities` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `sla_hours` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_priorities`
--

INSERT INTO `catalog_priorities` (`id`, `code`, `label`, `sla_hours`, `active`, `sort_order`, `created_at`) VALUES
(1, 'baja', 'Baja', 24, 1, 10, '2025-12-16 14:03:13'),
(2, 'media', 'Media', 3, 1, 20, '2025-12-16 14:03:13'),
(3, 'alta', 'Alta', 1, 1, 30, '2025-12-16 14:03:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_problems`
--

CREATE TABLE `catalog_problems` (
  `id` int(11) NOT NULL,
  `area_code` varchar(50) NOT NULL,
  `code` varchar(80) NOT NULL,
  `label` varchar(160) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_problems`
--

INSERT INTO `catalog_problems` (`id`, `area_code`, `code`, `label`, `active`, `sort_order`, `created_at`) VALUES
(1, 'TI', 'cierre', 'Cierre de día', 1, 10, '2025-12-16 15:21:26'),
(2, 'TI', 'internet', 'Sin internet', 1, 20, '2025-12-16 15:21:26'),
(3, 'SAP', 'replica', 'Replica', 1, 10, '2025-12-16 15:21:26'),
(4, 'SAP', 'precios', 'Precios diferentes entre Legacy y SAP', 1, 20, '2025-12-16 15:21:26'),
(5, 'MKT', 'cliente', 'Alta / Actualización de cliente', 1, 10, '2025-12-16 15:21:26'),
(6, 'MKT', 'descuentos', 'Problema con descuentos', 1, 20, '2025-12-16 15:21:26'),
(7, 'TI', 'legado', 'Problemas con el punto de Legacy', 1, 50, '2025-12-16 17:04:20'),
(8, 'SAP', 'preci0', 'Precios en 0', 1, 40, '2025-12-16 17:28:13'),
(9, 'SAP', 'server', 'Error Internal Server', 1, 30, '2025-12-16 17:28:38'),
(10, 'TI', 'impresora', 'No sirve mi impresora', 0, 50, '2025-12-16 17:45:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_sat_patterns`
--

CREATE TABLE `catalog_sat_patterns` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_sat_patterns`
--

INSERT INTO `catalog_sat_patterns` (`id`, `code`, `label`, `active`, `sort_order`, `created_at`) VALUES
(1, '1y3', '1° y 3° sábado', 1, 10, '2026-01-02 11:32:19'),
(2, '2y4', '2° y 4° sábado', 1, 20, '2026-01-02 11:32:19'),
(3, 'all', 'Todos los sábados', 0, 30, '2026-01-02 11:32:19'),
(4, 'todos', 'Todos los sabados', 1, 50, '2026-01-02 12:22:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_shifts`
--

CREATE TABLE `catalog_shifts` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_shifts`
--

INSERT INTO `catalog_shifts` (`id`, `code`, `label`, `start_time`, `end_time`, `active`, `sort_order`, `created_at`) VALUES
(1, '8_1730', '08:00 – 17:30', '08:00:00', '17:30:00', 1, 10, '2026-01-02 11:32:01'),
(2, '9_1830', '09:00 – 18:30', '09:00:00', '18:30:00', 1, 20, '2026-01-02 11:32:01'),
(4, '8_1900', '08:00 - 19:00', '08:00:00', '19:00:00', 1, 30, '2026-01-02 12:21:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalog_status`
--

CREATE TABLE `catalog_status` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(80) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalog_status`
--

INSERT INTO `catalog_status` (`id`, `code`, `label`, `active`, `sort_order`, `created_at`) VALUES
(1, 'abierto', 'Abierto', 1, 10, '2025-12-16 13:59:02'),
(2, 'en_proceso', 'En proceso', 1, 20, '2025-12-16 13:59:02'),
(4, 'cerrado', 'Cerrado', 1, 40, '2025-12-16 13:59:02'),
(5, 'soporte', 'soporteViSo', 1, 50, '2026-01-02 09:18:25'),
(6, 'ro', 're abierto', 0, 50, '2026-01-06 10:59:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `display_name` varchar(180) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `size_bytes` int(11) NOT NULL,
  `visibility` enum('ALL','TI','SAP','MKT','SUCURSAL','CORPORATIVO') NOT NULL DEFAULT 'ALL',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documents`
--

INSERT INTO `documents` (`id`, `display_name`, `stored_name`, `original_name`, `mime_type`, `size_bytes`, `visibility`, `uploaded_by`, `created_at`) VALUES
(1, 'DIRECTORIO GENERAL', 'doc_20251217_205216_9af000e3ac8f.xlsx', 'DIRECTORIO GENERAL 2025.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 92707, 'ALL', 51, '2025-12-17 13:52:16'),
(5, 'PROPUESTAS DE PROYECTOS', 'doc_20260102_223515_8d7a569e7513.pptx', 'PROPUESTAS DE PROYECTOS.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 3875701, 'SUCURSAL', 51, '2026-01-02 15:35:15'),
(6, 'PROPUESTA DE PROYECTOS', 'doc_20260107_004847_37d576d11117.pptx', 'PROPUESTAS DE PROYECTOS.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 3875701, 'SAP', 51, '2026-01-06 17:48:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_ide` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notifications`
--

INSERT INTO `notifications` (`id`, `user_ide`, `type`, `title`, `body`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'ticket_status', 'Estado actualizado', 'El ticket #76 cambió a: En proceso', '/HelpDesk_EQF/modules/dashboard/analyst/analyst.php?open_ticket=76', 0, '2026-01-06 13:24:06'),
(2, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #76 canalizado a tu área (SAP). Motivo: ayuda es primero alla', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 13:24:53'),
(3, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #76 canalizado a tu área (SAP). Motivo: ayuda es primero alla', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 13:24:53'),
(4, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #76 canalizado a tu área (SAP). Motivo: ayuda es primero alla', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 13:24:53'),
(5, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #76 canalizado a tu área (SAP). Motivo: ayuda es primero alla', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 13:24:53'),
(6, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #75 canalizado a tu área (SAP). Motivo: tiene replicas pendientes', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:21:19'),
(7, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #75 canalizado a tu área (SAP). Motivo: tiene replicas pendientes', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:21:19'),
(8, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #75 canalizado a tu área (SAP). Motivo: tiene replicas pendientes', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:21:19'),
(9, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #75 canalizado a tu área (SAP). Motivo: tiene replicas pendientes', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:21:19'),
(10, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #74 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:53:13'),
(11, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #74 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:53:13'),
(12, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #74 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:53:13'),
(13, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #74 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-06 17:53:13'),
(14, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #79 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 08:46:45'),
(15, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #79 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 08:46:45'),
(16, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #79 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 08:46:45'),
(17, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #79 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 08:46:45'),
(18, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #80 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 09:03:40'),
(19, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #80 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 09:03:40'),
(20, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #80 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 09:03:40'),
(21, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #80 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 09:03:40'),
(22, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #81 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 11:43:10'),
(23, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #81 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 11:43:10'),
(24, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #81 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 11:43:10'),
(25, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #81 canalizado a tu área (SAP).', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 11:43:10'),
(26, 2, 'ticket_assigned', 'Ticket asignado', 'Se te asignó el ticket #82.', '/HelpDesk_EQF/modules/dashboard/analyst/analyst.php?open_ticket=82', 0, '2026-01-07 12:01:37'),
(27, 8, 'ticket_transfer', 'Ticket canalizado', 'Ticket #82 canalizado a tu área (SAP). Motivo: ayuda con su cierre', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 12:06:44'),
(28, 9, 'ticket_transfer', 'Ticket canalizado', 'Ticket #82 canalizado a tu área (SAP). Motivo: ayuda con su cierre', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 12:06:44'),
(29, 10, 'ticket_transfer', 'Ticket canalizado', 'Ticket #82 canalizado a tu área (SAP). Motivo: ayuda con su cierre', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 12:06:44'),
(30, 11, 'ticket_transfer', 'Ticket canalizado', 'Ticket #82 canalizado a tu área (SAP). Motivo: ayuda con su cierre', '/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto', 0, '2026-01-07 12:06:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_recovery_requests`
--

CREATE TABLE `password_recovery_requests` (
  `id` int(11) NOT NULL,
  `requester_email` varchar(160) NOT NULL,
  `status` enum('PENDIENTE','ATENDIDO') NOT NULL DEFAULT 'PENDIENTE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `attended_at` datetime DEFAULT NULL,
  `attended_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_recovery_requests`
--

INSERT INTO `password_recovery_requests` (`id`, `requester_email`, `status`, `created_at`, `attended_at`, `attended_by`) VALUES
(1, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-15 16:14:17', '2025-12-15 17:09:44', 51),
(2, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-15 16:14:19', '2025-12-15 17:09:40', 51),
(3, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-15 16:14:21', '2025-12-15 17:02:53', 51),
(4, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-15 17:41:46', '2025-12-15 17:42:21', 51),
(5, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-16 10:27:02', '2025-12-16 10:30:22', 51),
(6, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-17 12:37:49', '2025-12-17 12:37:59', 51),
(7, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-18 11:19:38', '2025-12-18 11:20:14', 51),
(8, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-18 11:19:46', '2025-12-18 11:20:22', 51),
(9, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-18 11:21:32', '2025-12-18 13:30:00', 51),
(10, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-18 12:04:20', '2025-12-18 13:29:59', 51),
(11, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-18 13:26:30', '2025-12-18 13:29:58', 51),
(12, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-18 13:32:07', '2025-12-18 13:35:31', 51),
(13, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-18 13:36:34', '2025-12-18 13:36:46', 51),
(14, 'ti@eqf.mx', 'ATENDIDO', '2025-12-18 13:58:41', '2025-12-18 13:58:48', 51),
(15, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-18 14:00:26', '2025-12-18 14:00:32', 51),
(16, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 16:02:49', '2025-12-23 16:07:08', 51),
(17, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 16:03:49', '2025-12-23 16:06:08', 51),
(18, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 16:04:49', '2025-12-23 16:05:07', 51),
(19, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 16:06:01', '2025-12-23 16:16:24', 51),
(20, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 16:12:00', '2025-12-23 16:13:14', 51),
(21, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-23 16:13:52', '2025-12-23 16:14:28', 51),
(22, 'gerente-ti@eqf.mx', 'ATENDIDO', '2025-12-23 16:14:20', '2025-12-23 16:14:30', 51),
(23, 'ti@eqf.mx', 'ATENDIDO', '2025-12-23 16:16:05', '2025-12-23 16:16:22', 51),
(24, 'administracion3@eqf.mx', 'ATENDIDO', '2025-12-23 16:16:45', '2025-12-23 16:16:59', 51),
(25, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 17:28:06', '2025-12-23 17:29:45', 51),
(26, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 17:29:13', '2025-12-23 17:29:46', 51),
(27, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 17:29:23', '2025-12-23 17:29:42', 51),
(28, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-23 17:32:36', '2025-12-23 17:33:19', 51),
(29, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-23 17:32:48', '2025-12-23 17:33:17', 51),
(30, 'gerente-ti@eqf.mx', 'ATENDIDO', '2025-12-24 10:54:42', '2025-12-24 11:08:01', 51),
(31, 'ti5@eqf.mx', 'ATENDIDO', '2025-12-24 11:00:22', '2025-12-24 11:08:04', 51),
(32, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-24 11:01:25', '2025-12-24 11:08:05', 51),
(33, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-24 11:06:36', '2025-12-24 11:08:06', 51),
(34, 'ti2@eqf.mx', 'ATENDIDO', '2025-12-24 11:12:17', '2025-12-24 11:12:37', 51),
(35, 'ti6@eqf.mx', 'ATENDIDO', '2025-12-29 10:31:28', '2025-12-29 13:53:57', 51),
(36, 'juridico2@eqf.mx', 'ATENDIDO', '2025-12-29 16:43:02', '2025-12-29 16:43:08', 51),
(37, 'ti6@eqf.mx', 'ATENDIDO', '2026-01-11 19:24:22', '2026-01-11 19:26:18', 51);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `area` varchar(50) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `staff_notifications`
--

CREATE TABLE `staff_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` varchar(255) NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `staff_notifications`
--

INSERT INTO `staff_notifications` (`id`, `user_id`, `title`, `body`, `leido`, `created_at`) VALUES
(1, 1, 'Tarea enterada', 'El analista Brandon Suarez marcó ENTERADO la tarea #16: CAMARAS', 1, '2026-01-07 13:44:41'),
(2, 1, 'Tarea finalizada', 'El analista Brandon Suarez marcó FINALIZADA la tarea #16: CAMARAS', 1, '2026-01-07 16:04:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `created_by_admin_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `notes` text DEFAULT NULL,
  `priority_id` int(11) NOT NULL,
  `due_at` datetime NOT NULL,
  `status` enum('ASIGNADA','EN_PROCESO','FINALIZADA','VALIDADA','CANCELADA') NOT NULL DEFAULT 'ASIGNADA',
  `acknowledged_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `ack_at` datetime DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tasks`
--

INSERT INTO `tasks` (`id`, `created_by_admin_id`, `assigned_to_user_id`, `title`, `description`, `notes`, `priority_id`, `due_at`, `status`, `acknowledged_at`, `finished_at`, `ack_at`, `validated_at`, `canceled_at`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 'SERVIDRES', 'XD', NULL, 2, '2026-01-26 13:00:00', 'FINALIZADA', NULL, '2026-01-08 11:58:00', NULL, NULL, NULL, '2026-01-08 09:54:58', '2026-01-08 11:58:00'),
(3, 1, 2, 'HELPDESK', 'DEBE DE ESTAR TERMINADA', NULL, 3, '2026-01-22 15:00:00', 'FINALIZADA', NULL, '2026-01-08 11:57:57', NULL, NULL, NULL, '2026-01-08 10:50:58', '2026-01-08 11:57:57'),
(4, 1, 2, 'DOCUMENTACION', 'DOCUEMNTA LA MESA DE AYUDA, NO SE TE OLVIDE', NULL, 3, '2026-01-22 13:00:00', 'FINALIZADA', NULL, '2026-01-08 13:22:06', NULL, NULL, NULL, '2026-01-08 13:21:13', '2026-01-08 13:22:06'),
(5, 1, 2, 'YA ACABA PLS', 'TIENES QUE TERMINAR', NULL, 2, '2026-01-30 17:00:00', 'FINALIZADA', '2026-01-08 13:45:49', '2026-01-08 13:56:46', NULL, NULL, NULL, '2026-01-08 13:45:23', '2026-01-08 13:56:46'),
(6, 1, 2, 'Implementación de Política de Respaldo Crítico y Actualización de Seguridad en Estaciones de Trabajo.', 'Se ha detectado que varios equipos del departamento de Finanzas no están cumpliendo con los protocolos de redundancia de datos. Tu misión es asegurar la integridad de la información y cerrar una vulnerabilidad detectada recientemente en el sistema operativo.', NULL, 2, '2026-01-13 18:00:00', 'FINALIZADA', '2026-01-08 15:13:46', '2026-01-08 15:13:58', NULL, NULL, NULL, '2026-01-08 15:13:33', '2026-01-08 15:13:58'),
(7, 1, 2, 'Configuración de Portal Cautivo y Segmentación de Red Wi-Fi para Visitantes (VLAN 30).', 'El departamento de Seguridad de la Información ha solicitado separar el tráfico de internet de los visitantes de la red corporativa interna. Actualmente, los invitados están recibiendo acceso a través de la red de empleados, lo cual representa un riesgo de seguridad crítico.', NULL, 1, '2026-02-19 17:00:00', 'FINALIZADA', '2026-01-08 15:25:50', '2026-01-08 16:33:47', NULL, NULL, NULL, '2026-01-08 15:24:11', '2026-01-08 16:33:47'),
(8, 1, 2, 'Despliegue de Autenticación de Doble Factor (MFA) y Gestión de Bajas de Usuarios.', 'El departamento de Ciberseguridad ha reportado varios intentos de inicio de sesión sospechosos desde ubicaciones inusuales. Para mitigar este riesgo, se requiere forzar el uso de MFA para todo el personal y realizar una auditoría de cuentas que ya no deberían estar activas.', NULL, 2, '2026-01-22 17:30:00', 'FINALIZADA', '2026-01-08 15:47:17', '2026-01-08 16:33:45', NULL, NULL, NULL, '2026-01-08 15:46:58', '2026-01-08 16:33:45'),
(9, 1, 2, 'PORTAL CAUTIVO', 'S', NULL, 1, '2026-01-20 14:00:00', 'FINALIZADA', '2026-01-08 16:34:42', '2026-01-08 16:47:51', NULL, NULL, NULL, '2026-01-08 16:34:21', '2026-01-08 16:47:51'),
(10, 1, 2, 'ddfsf', 'hola como estas', NULL, 1, '2026-01-08 17:40:00', 'CANCELADA', '2026-01-09 09:33:50', NULL, NULL, NULL, '2026-01-09 09:34:45', '2026-01-08 16:58:21', '2026-01-09 09:34:45'),
(11, 1, 4, 'srg', 'xd', NULL, 1, '2026-01-16 06:06:00', 'FINALIZADA', NULL, NULL, NULL, NULL, NULL, '2026-01-09 09:35:36', '2026-01-09 10:32:50'),
(12, 1, 2, 'MANTENIMIENTO EQUIPOS DE RRHH', 'XXZX', NULL, 3, '2026-01-12 09:00:00', 'CANCELADA', '2026-01-09 10:59:57', '2026-01-09 11:27:54', NULL, NULL, '2026-01-09 11:34:23', '2026-01-09 10:59:29', '2026-01-09 11:34:23'),
(13, 1, 2, '📋 Plan de Ejecución: Despliegue de MFA - Departamento RRHH', '1. Comunicación Previa (Soft Skill)\r\nAntes de tocar cualquier configuración técnica, debes informar. RRHH suele estar bajo mucha presión de tiempo.\r\n\r\nAcción: Enviar un correo breve avisando que a partir de mañana a las 9:00 AM se les solicitará un segundo factor de autenticación.\r\n\r\nInstrucción: Incluir una guía visual de cómo descargar Microsoft/Google Authenticator.\r\n\r\n2. Configuración Técnica (En el Panel de Administración)\r\nPara no afectar a toda la empresa de golpe, aplicaremos una Directiva de Acceso Condicional dirigida:\r\n\r\nAlcance: Grupo de Seguridad \"RRHH_Users\".\r\n\r\nAplicación de destino: \"Todas las aplicaciones en la nube\" (Office 365, SharePoint, etc.).\r\n\r\nCondición: Cualquier ubicación (fuera y dentro de la oficina).\r\n\r\nControl de acceso: \"Conceder acceso\" pero \"Requerir autenticación multifactor\".\r\n\r\n3. Ejecución de \"Limpieza\" en RRHH\r\nMientras se propaga la política de MFA, debes cumplir con la parte de la tarea sobre cuentas inactivas:\r\n\r\nAuditoría: Filtrar en el panel de usuarios: Departamento == \"Recursos Humanos\" + Último inicio de sesión > 90 días.\r\n\r\nAcción: Bloquear el inicio de sesión de esas cuentas encontradas y retirarles la licencia de Office 365 para ahorrar presupuesto.', NULL, 3, '2026-01-10 10:55:00', 'FINALIZADA', '2026-01-09 11:38:26', '2026-01-09 11:38:51', NULL, NULL, NULL, '2026-01-09 11:35:28', '2026-01-09 11:38:51'),
(14, 1, 3, 'prueba a uno solo', 'aZass', NULL, 1, '2026-01-15 15:00:00', 'FINALIZADA', NULL, '2026-01-09 13:58:04', NULL, NULL, '2026-01-09 13:40:00', '2026-01-09 13:34:35', '2026-01-09 13:58:04'),
(15, 1, 2, 'fr', 'rerer', NULL, 3, '2026-01-22 15:00:00', 'FINALIZADA', NULL, '2026-01-09 15:14:03', NULL, NULL, '2026-01-09 13:40:02', '2026-01-09 13:35:09', '2026-01-09 15:14:03'),
(16, 1, 2, 'PRUEBA DE ENTREGA A 1 SOLA PERSONA', 'SI JALO, NO NO JALO?', NULL, 2, '2026-01-16 09:00:00', 'FINALIZADA', NULL, '2026-01-09 15:16:28', NULL, NULL, NULL, '2026-01-09 15:15:08', '2026-01-09 15:16:28'),
(17, 1, 4, 'PRUEBA 2', 'PRUEBA ENVIO A 2 ANALISTAS', NULL, 1, '2026-01-22 09:00:00', 'FINALIZADA', NULL, '2026-01-09 15:18:36', NULL, NULL, NULL, '2026-01-09 15:17:49', '2026-01-09 15:18:36'),
(18, 1, 2, 'PRESENTACION HELPDESK', 'CAPACITAR A REGIONALES DE LA MESA DE AYUDA PARA QUE ENTIENDAN COMO FUNCIONA', NULL, 3, '2026-01-19 08:00:00', 'FINALIZADA', '2026-01-11 12:29:53', '2026-01-11 19:30:11', NULL, NULL, NULL, '2026-01-11 12:29:38', '2026-01-11 12:30:11'),
(19, 1, 2, 'YA GG EZzzzz', 'Quedo la helpDesk hecha completia gigi perra', 'Ya quedo guapuras', 3, '2026-01-22 09:00:00', 'FINALIZADA', '2026-01-11 16:25:33', '2026-01-11 23:26:03', NULL, NULL, NULL, '2026-01-11 16:25:23', '2026-01-11 16:26:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_events`
--

CREATE TABLE `task_events` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `actor_user_id` int(11) NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_events`
--

INSERT INTO `task_events` (`id`, `task_id`, `actor_user_id`, `event_type`, `note`, `old_value`, `new_value`, `created_at`) VALUES
(2, 3, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"icon_helpdesk.png\"}', '2026-01-08 10:51:33'),
(3, 3, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 11:57:57'),
(4, 2, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 11:58:00'),
(5, 4, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"Ticket_86_11104.pdf\"}', '2026-01-08 13:21:41'),
(6, 4, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 13:22:06'),
(7, 5, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 13:56:46'),
(8, 6, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 15:13:58'),
(9, 8, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"ticket_bien.pdf\"}', '2026-01-08 16:30:49'),
(10, 8, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 16:33:45'),
(11, 7, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 16:33:47'),
(12, 9, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"Gemini_Generated_Image_ic18ruic18ruic18.png\"}', '2026-01-08 16:34:54'),
(13, 9, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"ticket_mal.pdf\"}', '2026-01-08 16:42:10'),
(14, 9, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-08 16:47:51'),
(15, 10, 1, 'REASSIGNED', 'Reasignada: #2 -> #4', '{\"assigned_to_user_id\":2,\"status\":\"EN_PROCESO\"}', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '2026-01-08 17:39:00'),
(16, 10, 1, 'REASSIGNED', 'Reasignada: #4 -> #2', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '2026-01-08 17:39:11'),
(17, 10, 1, 'REASSIGNED', 'Reasignada: #2 -> #3', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":3,\"status\":\"ASIGNADA\"}', '2026-01-09 09:29:53'),
(18, 10, 1, 'REASSIGNED', 'Reasignada: #3 -> #2', '{\"assigned_to_user_id\":3,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '2026-01-09 09:30:06'),
(19, 10, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"REPORTE TAREA.pdf\"}', '2026-01-09 09:31:40'),
(20, 10, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"Ticket_86_11104.pdf\"}', '2026-01-09 09:31:44'),
(21, 10, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"ESTADO DE CUENTA.pdf\"}', '2026-01-09 09:31:48'),
(22, 10, 1, 'ADMIN_FILE_ADDED', 'Adjunto agregado', NULL, '{\"file\":\"Gemini_Generated_Image_ic18ruic18ruic18.png\"}', '2026-01-09 09:31:53'),
(23, 10, 1, 'REASSIGNED', 'Reasignada: #2 -> #4', '{\"assigned_to_user_id\":2,\"status\":\"EN_PROCESO\"}', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '2026-01-09 09:33:29'),
(24, 10, 1, 'REASSIGNED', 'Reasignada: #4 -> #2', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '2026-01-09 09:33:40'),
(25, 10, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"ticket_mal.pdf\"}', '2026-01-09 09:34:19'),
(26, 10, 1, 'FILE_DELETED', 'Archivo eliminado', NULL, '{\"file_id\":11,\"original\":\"ESTADO DE CUENTA.pdf\"}', '2026-01-09 09:34:29'),
(27, 10, 1, 'CANCELED', 'Cancelada (afectó a analista #2)', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"CANCELADA\"}', '2026-01-09 09:34:45'),
(28, 11, 1, 'REASSIGNED', 'Reasignada: #2 -> #4', '{\"assigned_to_user_id\":2,\"status\":\"EN_PROCESO\"}', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '2026-01-09 09:42:37'),
(29, 11, 1, 'REASSIGNED', 'Reasignada: #4 -> #2', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '2026-01-09 09:42:40'),
(30, 11, 1, 'REASSIGNED', 'Reasignada: #2 -> #4', '{\"assigned_to_user_id\":2,\"status\":\"EN_PROCESO\"}', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '2026-01-09 10:32:06'),
(31, 12, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-09 11:27:54'),
(32, 12, 1, 'CANCELED', 'Cancelada (afectó a analista #2)', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"CANCELADA\"}', '2026-01-09 11:34:23'),
(33, 13, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"pp_diseno.jpg\"}', '2026-01-09 11:38:41'),
(34, 13, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\"}', '{\"status\":\"FINALIZADA\"}', '2026-01-09 11:38:51'),
(35, 15, 1, 'REASSIGNED', 'Reasignada: #4 -> #2', '{\"assigned_to_user_id\":4,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '2026-01-09 13:35:22'),
(36, 14, 1, 'REASSIGNED', 'Reasignada: #2 -> #3', '{\"assigned_to_user_id\":2,\"status\":\"ASIGNADA\"}', '{\"assigned_to_user_id\":3,\"status\":\"ASIGNADA\"}', '2026-01-09 13:36:30'),
(37, 14, 1, 'CANCELED', 'Cancelada (afectó a analista #3)', '{\"status\":\"ASIGNADA\"}', '{\"status\":\"CANCELADA\"}', '2026-01-09 13:40:00'),
(38, 15, 1, 'CANCELED', 'Cancelada (afectó a analista #2)', '{\"status\":\"ASIGNADA\"}', '{\"status\":\"CANCELADA\"}', '2026-01-09 13:40:02'),
(39, 14, 2, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":16}', '2026-01-09 13:58:04'),
(40, 15, 2, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":18}', '2026-01-09 13:58:06'),
(41, 15, 4, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":17}', '2026-01-09 15:14:03'),
(42, 16, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"pp_ti.jpg\",\"assignee_id\":19}', '2026-01-09 15:15:36'),
(43, 16, 1, 'REASSIGNED', 'Tarea reasignada (retirada al analista)', '{\"assignee_id\":19,\"analyst_id\":2,\"status\":\"EN_PROCESO\"}', '{\"status\":\"RETIRADA\",\"new_analyst_id\":4}', '2026-01-09 15:15:47'),
(44, 16, 1, 'REASSIGNED', 'Tarea reasignada a nuevo analista', NULL, '{\"new_analyst_id\":4}', '2026-01-09 15:15:47'),
(45, 16, 4, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":20}', '2026-01-09 15:16:28'),
(46, 17, 4, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"Licencia de uso de suelo 1.pdf\",\"assignee_id\":22}', '2026-01-09 15:18:17'),
(47, 17, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"pp_sa.jpg\",\"assignee_id\":23}', '2026-01-09 15:18:22'),
(48, 17, 2, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":23}', '2026-01-09 15:18:33'),
(49, 17, 4, 'FINISHED', 'Analista finalizó su tarea', '{\"assignee_status\":\"EN_PROCESO\"}', '{\"assignee_status\":\"FINALIZADA\",\"assignee_id\":22}', '2026-01-09 15:18:36'),
(50, 18, 2, 'ACKNOWLEDGED', 'Analista marcó como EN PROCESO', '{\"status\":\"ASIGNADA\"}', '{\"status\":\"EN_PROCESO\"}', '2026-01-11 12:29:53'),
(51, 18, 2, 'EVIDENCE_ADDED', 'Evidencia agregada', NULL, '{\"file\":\"ticket_bien.pdf\"}', '2026-01-11 12:30:02'),
(52, 18, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\",\"finished_at\":null}', '{\"status\":\"FINALIZADA\",\"finished_at\":\"2026-01-11 19:30:11\"}', '2026-01-11 12:30:11'),
(53, 19, 2, 'ACKNOWLEDGED', 'Analista marcó como EN PROCESO', '{\"status\":\"ASIGNADA\"}', '{\"status\":\"EN_PROCESO\"}', '2026-01-11 16:25:33'),
(54, 19, 2, 'FINISHED', 'Analista finalizó tarea', '{\"status\":\"EN_PROCESO\",\"finished_at\":null}', '{\"status\":\"FINALIZADA\",\"finished_at\":\"2026-01-11 23:26:03\"}', '2026-01-11 16:26:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_files`
--

CREATE TABLE `task_files` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `task_assignee_id` int(11) DEFAULT NULL,
  `uploaded_by_user_id` int(11) NOT NULL,
  `file_type` enum('ADMIN_ATTACHMENT','EVIDENCE') NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_files`
--

INSERT INTO `task_files` (`id`, `task_id`, `task_assignee_id`, `uploaded_by_user_id`, `file_type`, `original_name`, `stored_name`, `mime`, `size_bytes`, `is_deleted`, `deleted_at`, `deleted_by_user_id`, `created_at`) VALUES
(2, 3, NULL, 1, 'ADMIN_ATTACHMENT', 'icon_helpdesk.png', 'icon_helpdesk_20260108_175133_f8ffbf15ecd3.png', 'image/png', 452330, 0, NULL, NULL, '2026-01-08 10:51:33'),
(3, 4, NULL, 1, 'ADMIN_ATTACHMENT', 'Ticket_86_11104.pdf', 'Ticket_86_11104_20260108_202141_0e9fc3166faf.pdf', 'application/pdf', 25892, 0, NULL, NULL, '2026-01-08 13:21:41'),
(4, 8, NULL, 1, 'ADMIN_ATTACHMENT', 'ACTIVIDADES ROL.docx', 'admin_8_faeaa6bc1b9cd1e9.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 21251, 0, NULL, NULL, '2026-01-08 15:46:58'),
(5, 8, NULL, 2, 'EVIDENCE', 'ticket_bien.pdf', 'ticket_bien_20260108_233049_4554fc53646d.pdf', 'application/pdf', 25602, 0, NULL, NULL, '2026-01-08 16:30:49'),
(6, 9, NULL, 1, 'ADMIN_ATTACHMENT', 'ChatGPT Image 11 dic 2025, 11_32_40 a.m..png', 'admin_9_b86cd80619e4b82e.png', 'image/png', 1041001, 0, NULL, NULL, '2026-01-08 16:34:21'),
(7, 9, NULL, 1, 'ADMIN_ATTACHMENT', 'Gemini_Generated_Image_ic18ruic18ruic18.png', 'Gemini_Generated_Image_ic18ruic18ruic18_20260108_233454_a0d273a1fc9c.png', 'image/png', 967337, 0, NULL, NULL, '2026-01-08 16:34:54'),
(9, 10, NULL, 1, 'ADMIN_ATTACHMENT', 'REPORTE TAREA.pdf', 'REPORTE_TAREA_20260109_163140_7e8f2e552f76.pdf', 'application/pdf', 106227, 0, NULL, NULL, '2026-01-09 09:31:40'),
(11, 10, NULL, 1, 'ADMIN_ATTACHMENT', 'ESTADO DE CUENTA.pdf', 'ESTADO_DE_CUENTA_20260109_163148_2e37a7772b6d.pdf', 'application/pdf', 131300, 1, '2026-01-09 09:34:29', 1, '2026-01-09 09:31:48'),
(17, 16, NULL, 2, 'EVIDENCE', 'pp_ti.jpg', 'pp_ti_20260109_221536_508431736b0e.jpg', 'image/jpeg', 122821, 0, NULL, NULL, '2026-01-09 15:15:36'),
(18, 17, NULL, 1, 'ADMIN_ATTACHMENT', 'Licencia de uso de suelo.pdf', 'admin_17_be8d65591833ce03.pdf', 'application/pdf', 10960832, 0, NULL, NULL, '2026-01-09 15:17:49'),
(19, 17, NULL, 4, 'EVIDENCE', 'Licencia de uso de suelo 1.pdf', 'Licencia_de_uso_de_suelo_1_20260109_221817_636533901d7d.pdf', 'application/pdf', 10966722, 0, NULL, NULL, '2026-01-09 15:18:17'),
(20, 17, NULL, 2, 'EVIDENCE', 'pp_sa.jpg', 'pp_sa_20260109_221822_a29a7b66e481.jpg', 'image/jpeg', 122678, 0, NULL, NULL, '2026-01-09 15:18:22'),
(21, 18, NULL, 1, 'ADMIN_ATTACHMENT', 'reporte_tarea_7.pdf', 'admin_18_f5c5c3b6276ed2fe.pdf', 'application/pdf', 27031, 0, NULL, NULL, '2026-01-11 12:29:38'),
(22, 18, NULL, 2, 'EVIDENCE', 'ticket_bien.pdf', 'ticket_bien_20260111_193002_f8dde8319a0a.pdf', 'application/pdf', 25602, 0, NULL, NULL, '2026-01-11 12:30:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sap` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `area` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `problema` varchar(50) NOT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `descripcion` text NOT NULL,
  `fecha_envio` datetime NOT NULL,
  `estado` enum('abierto','en_proceso','soporte','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  `asignado_a` int(11) DEFAULT NULL,
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_primera_respuesta` datetime DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `creado_por_ip` varchar(45) DEFAULT NULL,
  `creado_por_navegador` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `parent_ticket_id` int(11) DEFAULT NULL,
  `transferred_from_area` varchar(20) DEFAULT NULL,
  `transferred_by` int(11) DEFAULT NULL,
  `transferred_at` datetime DEFAULT NULL,
  `needs_feedback` tinyint(1) NOT NULL DEFAULT 0,
  `feedback_done` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `sap`, `nombre`, `area`, `email`, `problema`, `prioridad`, `descripcion`, `fecha_envio`, `estado`, `asignado_a`, `fecha_asignacion`, `fecha_primera_respuesta`, `fecha_resolucion`, `creado_por_ip`, `creado_por_navegador`, `created_at`, `updated_at`, `parent_ticket_id`, `transferred_from_area`, `transferred_by`, `transferred_at`, `needs_feedback`, `feedback_done`) VALUES
(1, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'cierre_dia', 'media', '12345688\r\n3r324242424\r\n', '2025-11-28 22:53:15', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 21:53:15', '2025-11-30 19:35:37', NULL, NULL, NULL, NULL, 0, 0),
(2, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'otro', 'media', 'Adjunto prueba de archivos', '2025-11-28 22:58:23', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 21:58:23', '2025-12-15 17:53:35', NULL, NULL, NULL, NULL, 0, 0),
(3, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'cierre_dia', 'media', 'sdasd', '2025-11-28 23:08:48', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:08:48', '2025-12-15 17:53:39', NULL, NULL, NULL, NULL, 0, 0),
(4, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'cierre_dia', 'media', 'ewrwrwrw', '2025-11-28 23:12:52', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:12:52', '2025-12-15 17:53:43', NULL, NULL, NULL, NULL, 0, 0),
(5, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_internet', 'media', 'prueba alertas', '2025-11-28 23:19:01', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:19:01', '2025-12-15 17:53:46', NULL, NULL, NULL, NULL, 0, 0),
(6, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'cierre_dia', 'media', 'prueba 3', '2025-11-28 23:20:34', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:20:34', '2025-12-15 17:53:50', NULL, NULL, NULL, NULL, 0, 0),
(7, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'cierre_dia', 'media', 'prueba 4', '2025-11-28 23:22:21', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:22:21', '2025-12-15 17:53:54', NULL, NULL, NULL, NULL, 0, 0),
(8, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_internet', 'media', 'no hay', '2025-11-28 23:30:59', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:30:59', '2025-12-15 17:53:57', NULL, NULL, NULL, NULL, 0, 0),
(9, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_checador', 'media', 'no funciona xdd lol', '2025-11-28 23:45:39', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 22:45:39', '2025-11-30 19:35:49', NULL, NULL, NULL, NULL, 0, 0),
(10, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_checador', 'media', 'ssa', '2025-11-29 00:03:25', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 23:03:25', '2025-12-15 17:54:03', NULL, NULL, NULL, NULL, 0, 0),
(11, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_checador', 'media', 'saad', '2025-11-29 00:03:34', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 23:03:34', '2025-12-15 17:54:08', NULL, NULL, NULL, NULL, 0, 0),
(12, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'no_checador', 'media', 'a', '2025-11-29 00:10:46', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 23:10:46', '2025-12-15 17:54:12', NULL, NULL, NULL, NULL, 0, 0),
(13, 46, '26282', 'Maria Sanchez', 'Sucursal', 'naucalpan@eqf.mx', 'rastreo', 'media', 'del colaborador', '2025-11-30 19:57:52', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 18:57:52', '2025-11-30 19:36:03', NULL, NULL, NULL, NULL, 0, 0),
(14, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_legado', 'alta', 'me marca que no hay acceso', '2025-12-01 01:43:50', 'cerrado', 3, '2025-11-30 22:18:32', NULL, '2025-12-09 10:28:33', NULL, NULL, '2025-12-01 00:43:50', '2025-12-09 16:28:33', NULL, NULL, NULL, NULL, 0, 0),
(15, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_legado', 'alta', 'pues no hay xdd', '2025-12-01 02:08:55', 'cerrado', 3, '2025-12-03 13:39:28', NULL, '2025-12-09 10:28:32', NULL, NULL, '2025-12-01 01:08:55', '2025-12-09 16:28:32', NULL, NULL, NULL, NULL, 0, 0),
(16, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'rastreo', 'alta', 'de un wey todo cachetes', '2025-12-01 03:53:35', 'cerrado', 3, '2025-11-30 22:18:46', NULL, '2025-12-09 10:28:30', NULL, NULL, '2025-12-01 02:53:35', '2025-12-09 16:28:30', NULL, NULL, NULL, NULL, 0, 0),
(17, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_internet', 'alta', 'xddddd', '2025-12-01 03:54:19', 'cerrado', 3, '2025-11-30 22:20:03', NULL, '2025-12-09 10:28:28', NULL, NULL, '2025-12-01 02:54:19', '2025-12-09 16:28:28', NULL, NULL, NULL, NULL, 0, 0),
(18, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'cierre', 'alta', 'no hice cierre soy menso', '2025-12-01 18:06:33', 'cerrado', 3, '2025-12-03 13:39:35', NULL, '2025-12-09 10:28:26', NULL, NULL, '2025-12-01 17:06:33', '2025-12-09 16:28:26', NULL, NULL, NULL, NULL, 0, 0),
(19, 46, '26282', 'Maria Sanchez', 'SAP', 'naucalpan@eqf.mx', 'replica', 'alta', 'por favor apoyo con una replica', '2025-12-01 20:11:05', 'cerrado', 11, '2025-12-08 15:10:10', NULL, '2025-12-08 17:15:54', NULL, NULL, '2025-12-01 19:11:05', '2025-12-08 23:15:54', NULL, NULL, NULL, NULL, 0, 0),
(20, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_legado', 'alta', 'no hay', '2025-12-02 19:29:27', 'cerrado', 3, '2025-12-03 13:39:34', NULL, '2025-12-09 10:28:24', NULL, NULL, '2025-12-02 18:29:27', '2025-12-09 16:28:24', NULL, NULL, NULL, NULL, 0, 0),
(21, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_internet', 'alta', 'z', '2025-12-03 22:01:39', 'cerrado', 3, '2025-12-03 15:01:57', NULL, '2025-12-09 10:28:21', NULL, NULL, '2025-12-03 21:01:39', '2025-12-17 18:32:14', NULL, NULL, NULL, NULL, 0, 0),
(22, 46, '26282', 'Maria Sanchez', 'SAP', 'naucalpan@eqf.mx', 'no_sap', 'alta', 'xd', '2025-12-08 23:58:22', 'cerrado', 11, '2025-12-08 16:58:30', NULL, NULL, NULL, NULL, '2025-12-08 22:58:22', '2025-12-15 17:54:21', NULL, NULL, NULL, NULL, 0, 0),
(23, 46, '26282', 'Maria Sanchez', 'SAP', 'naucalpan@eqf.mx', 'otro', 'media', 'xd', '2025-12-09 00:15:45', 'cerrado', 11, '2025-12-08 17:15:49', NULL, NULL, NULL, NULL, '2025-12-08 23:15:45', '2025-12-15 17:54:25', NULL, NULL, NULL, NULL, 0, 0),
(24, 46, '26282', 'Maria Sanchez', 'SAP', 'naucalpan@eqf.mx', 'otro', 'media', 'sisisi', '2025-12-09 00:16:10', 'cerrado', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-08 23:16:10', '2025-12-15 17:54:29', NULL, NULL, NULL, NULL, 0, 0),
(25, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_checador', 'alta', 'no jalaaa', '2025-12-09 01:13:43', 'cerrado', 3, '2025-12-08 18:14:04', NULL, '2025-12-09 10:28:18', NULL, NULL, '2025-12-09 00:13:43', '2025-12-09 16:28:18', NULL, NULL, NULL, NULL, 0, 0),
(26, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_checador', 'alta', 'apoyo de favor', '2025-12-09 22:21:49', 'cerrado', 3, '2025-12-09 15:22:03', NULL, '2025-12-09 15:23:02', NULL, NULL, '2025-12-09 21:21:49', '2025-12-09 21:23:02', NULL, NULL, NULL, NULL, 0, 0),
(27, 46, '26282', 'Maria Sanchez', 'TI', 'naucalpan@eqf.mx', 'no_internet', 'alta', 'sisisi', '2025-12-10 00:38:37', 'cerrado', 2, '2025-12-09 17:39:17', NULL, '2025-12-09 17:39:36', NULL, NULL, '2025-12-09 23:38:37', '2025-12-09 23:39:36', NULL, NULL, NULL, NULL, 0, 0),
(28, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'media', 'no pude hacer mi cierre', '2025-12-17 20:25:57', 'cerrado', 2, '2025-12-17 13:26:34', NULL, '2025-12-17 13:27:21', NULL, NULL, '2025-12-17 19:25:57', '2025-12-17 19:27:21', NULL, NULL, NULL, NULL, 0, 0),
(29, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '9', 'media', 'no sirveeee che gente', '2025-12-18 17:48:29', 'cerrado', 11, '2025-12-18 10:49:14', NULL, NULL, NULL, NULL, '2025-12-18 16:48:29', '2025-12-18 21:38:33', NULL, NULL, NULL, NULL, 0, 0),
(30, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '1', 'media', 'por favor, hacer mi cierre', '2025-12-22 19:02:46', 'cerrado', 11, '2025-12-23 09:15:28', NULL, NULL, NULL, NULL, '2025-12-22 18:02:46', '2025-12-23 17:02:10', NULL, NULL, NULL, NULL, 0, 0),
(31, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '7', 'media', 'no sirveeee', '2025-12-24 00:11:29', 'cerrado', 2, '2025-12-23 17:11:33', NULL, NULL, NULL, NULL, '2025-12-23 23:11:29', '2025-12-24 17:54:25', NULL, NULL, NULL, NULL, 0, 0),
(32, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'media', 'Todo bien', '2025-12-28 20:07:56', 'cerrado', 2, '2025-12-28 13:08:10', NULL, '2025-12-28 13:09:28', NULL, NULL, '2025-12-28 19:07:56', '2025-12-28 19:09:28', NULL, NULL, NULL, NULL, 0, 0),
(33, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'media', 'no tengo UnU', '2025-12-28 20:14:41', 'cerrado', 2, '2025-12-28 13:14:56', NULL, '2025-12-28 13:15:25', NULL, NULL, '2025-12-28 19:14:41', '2025-12-28 19:15:25', NULL, NULL, NULL, NULL, 0, 0),
(34, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '7', 'media', 'error', '2025-12-28 21:04:49', 'cerrado', 2, '2025-12-28 14:05:00', NULL, '2025-12-28 14:05:05', NULL, NULL, '2025-12-28 20:04:49', '2025-12-28 20:05:05', NULL, NULL, NULL, NULL, 0, 0),
(35, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'TIREN PARO', '2025-12-29 05:07:13', 'cerrado', 2, '2025-12-28 22:18:10', NULL, '2025-12-28 22:48:57', NULL, NULL, '2025-12-29 04:07:13', '2025-12-29 04:48:57', NULL, NULL, NULL, NULL, 0, 0),
(36, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'media', 'si se manda como alta?', '2025-12-29 05:49:32', 'cerrado', 2, '2025-12-28 23:07:09', NULL, '2025-12-28 23:07:14', NULL, NULL, '2025-12-29 04:49:32', '2025-12-29 05:07:14', NULL, NULL, NULL, NULL, 0, 0),
(37, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'jala como alta?', '2025-12-29 06:07:33', 'cerrado', 2, '2025-12-28 23:07:52', NULL, '2025-12-28 23:07:54', NULL, NULL, '2025-12-29 05:07:33', '2025-12-29 05:07:54', NULL, NULL, NULL, NULL, 0, 0),
(38, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'jala como media?', '2025-12-29 06:07:45', 'cerrado', 2, '2025-12-28 23:07:56', NULL, '2025-12-28 23:07:58', NULL, NULL, '2025-12-29 05:07:45', '2025-12-29 05:07:58', NULL, NULL, NULL, NULL, 0, 0),
(39, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'no me fije en elproblema xdd', '2025-12-29 06:13:21', 'cerrado', 2, '2025-12-28 23:13:28', NULL, '2025-12-28 23:13:30', NULL, NULL, '2025-12-29 05:13:21', '2025-12-29 05:13:30', NULL, NULL, NULL, NULL, 0, 0),
(40, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'no tengo UnU', '2025-12-29 16:06:06', 'cerrado', 2, '2025-12-29 09:06:22', NULL, '2025-12-29 09:06:32', NULL, NULL, '2025-12-29 15:06:06', '2025-12-29 15:06:32', NULL, NULL, NULL, NULL, 0, 0),
(41, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'PRUEBA PARA ENCUESTA', '2025-12-29 16:14:07', 'cerrado', 2, '2025-12-29 09:14:11', NULL, '2025-12-29 09:14:14', NULL, NULL, '2025-12-29 15:14:07', '2025-12-29 15:14:14', NULL, NULL, NULL, NULL, 0, 0),
(42, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'encuesta2\r\n', '2025-12-29 16:43:34', 'cerrado', 2, '2025-12-29 09:43:44', NULL, '2025-12-29 09:43:49', NULL, NULL, '2025-12-29 15:43:34', '2025-12-29 15:43:49', NULL, NULL, NULL, NULL, 0, 0),
(43, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'prueba ', '2025-12-29 17:00:44', 'cerrado', 2, '2025-12-29 10:00:47', NULL, '2025-12-29 10:00:52', NULL, NULL, '2025-12-29 16:00:44', '2025-12-29 16:00:52', NULL, NULL, NULL, NULL, 0, 0),
(44, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '7', 'alta', 'SI JALA?', '2025-12-29 17:18:52', 'cerrado', 2, '2025-12-29 10:19:32', NULL, '2025-12-29 10:20:35', NULL, NULL, '2025-12-29 16:18:52', '2025-12-29 16:20:35', NULL, NULL, NULL, NULL, 0, 0),
(45, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'no hay pinche intertnet culeros', '2025-12-29 23:13:45', 'cerrado', 2, '2025-12-29 16:14:22', NULL, '2025-12-29 16:15:19', NULL, NULL, '2025-12-29 22:13:45', '2025-12-29 22:15:19', NULL, NULL, NULL, NULL, 0, 0),
(46, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'xd', '2025-12-29 23:18:49', 'cerrado', 2, '2025-12-29 16:19:30', NULL, '2025-12-29 16:19:32', NULL, NULL, '2025-12-29 22:18:49', '2025-12-29 22:19:32', NULL, NULL, NULL, NULL, 0, 0),
(47, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'xd', '2025-12-29 23:19:02', 'cerrado', 2, '2025-12-29 16:19:47', NULL, '2025-12-29 16:19:49', NULL, NULL, '2025-12-29 22:19:02', '2025-12-29 22:19:49', NULL, NULL, NULL, NULL, 0, 0),
(48, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'Red/Internet', 'baja', 'Se reporto el internet', '2025-12-29 20:14:26', 'cerrado', 2, '2025-12-29 20:13:00', '2025-12-29 20:13:00', '2025-12-29 20:14:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 02:14:26', NULL, NULL, NULL, NULL, NULL, 0, 0),
(49, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'Es que no sirve el excel', '2025-12-30 06:02:12', 'cerrado', 2, '2025-12-29 23:02:22', NULL, '2025-12-29 23:31:11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 05:02:12', '2025-12-30 05:31:11', NULL, NULL, NULL, NULL, 0, 0),
(50, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '4', 'alta', 'no son los mismos', '2025-12-30 19:40:26', 'cerrado', 11, '2025-12-30 12:40:34', NULL, '2025-12-30 12:40:39', NULL, NULL, '2025-12-30 18:40:26', '2025-12-30 18:40:39', NULL, NULL, NULL, NULL, 0, 0),
(51, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'NO JALA MI EXCEL', '2025-12-30 20:19:06', 'cerrado', 2, '2025-12-30 13:19:16', NULL, '2025-12-30 13:41:42', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 19:19:06', '2025-12-30 19:41:42', NULL, NULL, NULL, NULL, 0, 0),
(52, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'chale toy bien wey', '2025-12-30 20:43:49', 'cerrado', 2, '2025-12-30 13:43:58', NULL, '2025-12-30 13:44:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 19:43:49', '2025-12-30 19:44:08', NULL, NULL, NULL, NULL, 0, 0),
(53, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'p5', '2025-12-30 22:10:04', 'cerrado', 2, '2025-12-30 15:10:31', NULL, '2025-12-30 15:10:58', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 21:10:04', '2025-12-30 21:10:58', NULL, NULL, NULL, NULL, 0, 0),
(54, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'p6', '2025-12-30 22:16:09', 'cerrado', 2, '2025-12-30 15:16:16', NULL, '2025-12-30 15:16:21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 21:16:09', '2025-12-30 21:16:21', NULL, NULL, NULL, NULL, 0, 0),
(55, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'p7', '2025-12-30 22:16:37', 'cerrado', 2, '2025-12-30 15:16:43', NULL, '2025-12-30 17:21:29', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-30 21:16:37', '2025-12-30 23:21:29', NULL, NULL, NULL, NULL, 0, 0),
(56, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '3', 'alta', 'por favor', '2025-12-30 22:21:29', 'cerrado', 11, '2025-12-30 15:21:43', NULL, '2025-12-31 10:09:45', NULL, NULL, '2025-12-30 21:21:29', '2025-12-31 16:09:45', NULL, NULL, NULL, NULL, 0, 0),
(57, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'ayudaaa pls', '2025-12-31 17:12:09', 'cerrado', 2, '2025-12-31 10:12:16', NULL, '2025-12-31 10:12:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-31 16:12:09', '2025-12-31 16:12:38', NULL, NULL, NULL, NULL, 0, 0),
(58, 2, '30378', 'Brandon Suarez', 'TI', 'ti6@eqf.mx', 'otro', 'alta', 'jelp', '2025-12-31 17:18:14', 'cerrado', 2, '2025-12-31 10:18:21', NULL, '2025-12-31 10:18:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-31 16:18:14', '2025-12-31 16:18:38', NULL, NULL, NULL, NULL, 0, 0),
(59, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'm', '2025-12-31 17:27:57', 'cerrado', 2, '2025-12-31 10:28:02', NULL, '2025-12-31 10:29:52', NULL, NULL, '2025-12-31 16:27:57', '2025-12-31 16:29:52', NULL, NULL, NULL, NULL, 0, 0),
(60, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'ni ideaflko', '2025-12-31 17:30:12', 'cerrado', 2, '2025-12-31 10:30:19', NULL, '2025-12-31 10:33:19', NULL, NULL, '2025-12-31 16:30:12', '2025-12-31 16:33:19', NULL, NULL, NULL, NULL, 0, 0),
(61, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'p7', '2025-12-31 17:34:12', 'cerrado', 2, '2025-12-31 10:34:22', NULL, '2025-12-31 10:34:26', NULL, NULL, '2025-12-31 16:34:12', '2025-12-31 16:34:26', NULL, NULL, NULL, NULL, 0, 0),
(62, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'xd', '2025-12-31 18:46:47', 'cerrado', 2, '2025-12-31 11:46:53', NULL, '2025-12-31 11:46:56', NULL, NULL, '2025-12-31 17:46:47', '2025-12-31 17:46:56', NULL, NULL, NULL, NULL, 0, 0),
(63, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '7', 'alta', 'jeeeeeeeeeeeeelp', '2025-12-31 18:47:37', 'cerrado', 2, '2025-12-31 11:47:42', NULL, '2025-12-31 11:47:43', NULL, NULL, '2025-12-31 17:47:37', '2025-12-31 17:47:43', NULL, NULL, NULL, NULL, 0, 0),
(64, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'ayuda pls', '2026-01-02 05:00:09', 'cerrado', 2, '2026-01-01 22:00:15', NULL, '2026-01-01 22:18:23', NULL, NULL, '2026-01-02 04:00:09', '2026-01-02 04:18:23', NULL, NULL, NULL, NULL, 0, 0),
(65, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'jelpmi', '2026-01-02 05:02:19', 'cerrado', 2, '2026-01-01 22:02:25', NULL, '2026-01-01 22:29:35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-02 04:02:19', '2026-01-02 04:29:35', NULL, NULL, NULL, NULL, 0, 0),
(66, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'xd', '2026-01-02 05:29:41', 'cerrado', 2, '2026-01-01 22:29:53', NULL, '2026-01-01 22:30:13', NULL, NULL, '2026-01-02 04:29:41', '2026-01-02 04:30:13', NULL, NULL, NULL, NULL, 0, 0),
(67, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'xd', '2026-01-02 16:20:42', 'cerrado', 2, '2026-01-02 09:20:59', NULL, '2026-01-02 09:24:25', NULL, NULL, '2026-01-02 15:20:42', '2026-01-02 15:24:25', NULL, NULL, NULL, NULL, 0, 0),
(68, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'de', '2026-01-02 16:45:38', 'cerrado', 2, '2026-01-02 09:45:47', NULL, '2026-01-02 09:47:29', NULL, NULL, '2026-01-02 15:45:38', '2026-01-02 15:47:29', NULL, NULL, NULL, NULL, 0, 0),
(69, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'xd', '2026-01-02 17:18:33', 'cerrado', 2, '2026-01-02 10:18:39', NULL, '2026-01-02 10:18:57', NULL, NULL, '2026-01-02 16:18:33', '2026-01-02 16:18:57', NULL, NULL, NULL, NULL, 0, 0),
(70, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', 'OTRO', 'media', 'PEDIDOS EN LINEA', '2026-01-03 01:07:06', 'cerrado', 11, '2026-01-02 18:07:53', NULL, '2026-01-02 18:12:27', NULL, NULL, '2026-01-03 00:07:06', '2026-01-03 00:12:27', NULL, NULL, NULL, NULL, 0, 0),
(71, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'no me abre mi excel', '2026-01-03 01:15:51', 'cerrado', 2, '2026-01-02 18:16:08', NULL, '2026-01-02 18:19:55', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-03 00:15:51', '2026-01-03 00:19:55', NULL, NULL, NULL, NULL, 0, 0),
(72, 11, '29366', 'Aidee Jimenez', 'TI', 'administracion3@eqf.mx', 'otro', 'alta', 'Holaa', '2026-01-05 03:55:48', 'cerrado', 2, '2026-01-04 20:56:12', NULL, '2026-01-05 17:31:21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '2026-01-05 02:55:48', '2026-01-05 23:31:21', NULL, NULL, NULL, NULL, 0, 0),
(73, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'JELP', '2026-01-05 20:48:24', 'cerrado', 2, '2026-01-05 17:31:17', NULL, '2026-01-06 13:46:10', NULL, NULL, '2026-01-05 19:48:24', '2026-01-06 19:46:10', NULL, NULL, NULL, NULL, 0, 0),
(74, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '1', 'alta', 's', '2026-01-06 18:12:55', 'cerrado', 11, '2026-01-07 08:38:58', NULL, '2026-01-07 08:39:00', NULL, NULL, '2026-01-06 17:12:55', '2026-01-07 14:39:00', NULL, 'TI', 1, '2026-01-06 17:53:13', 0, 0),
(75, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '7', 'alta', 'zs', '2026-01-06 19:59:54', 'cerrado', 11, '2026-01-06 17:40:24', NULL, '2026-01-06 17:43:28', NULL, NULL, '2026-01-06 18:59:54', '2026-01-06 23:43:28', NULL, 'TI', 1, '2026-01-06 17:21:19', 0, 0),
(76, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', 'OTRO', 'media', 's', '2026-01-06 20:03:15', 'cerrado', 11, '2026-01-06 13:25:10', NULL, '2026-01-06 13:45:51', NULL, NULL, '2026-01-06 19:03:15', '2026-01-06 19:45:51', NULL, 'TI', 1, '2026-01-06 13:24:53', 0, 0),
(77, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '3', 'alta', 'pl', '2026-01-07 00:43:46', 'cerrado', 11, '2026-01-06 17:44:03', NULL, '2026-01-06 17:46:59', NULL, NULL, '2026-01-06 23:43:46', '2026-01-06 23:46:59', NULL, NULL, NULL, NULL, 0, 0),
(78, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', 'otro', 'alta', 'XD', '2026-01-07 00:47:32', 'cerrado', 11, '2026-01-06 17:47:38', '2026-01-06 17:46:00', '2026-01-06 17:47:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 23:47:32', '2026-01-06 23:47:40', NULL, NULL, NULL, NULL, 0, 0),
(79, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '7', 'alta', 'SSSSS', '2026-01-07 15:39:20', 'cerrado', 11, '2026-01-07 08:46:52', NULL, '2026-01-07 09:02:10', NULL, NULL, '2026-01-07 14:39:20', '2026-01-07 15:02:10', NULL, 'TI', 1, '2026-01-07 08:46:45', 0, 0),
(80, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '1', 'alta', 'xd', '2026-01-07 16:03:08', 'cerrado', 11, '2026-01-07 09:03:45', NULL, '2026-01-07 11:41:45', NULL, NULL, '2026-01-07 15:03:08', '2026-01-07 17:41:45', NULL, 'TI', 1, '2026-01-07 09:03:40', 0, 0),
(81, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '1', 'alta', 'g', '2026-01-07 18:42:02', 'cerrado', 11, '2026-01-07 11:43:18', NULL, '2026-01-07 12:01:11', NULL, NULL, '2026-01-07 17:42:02', '2026-01-07 18:01:11', NULL, 'TI', 1, '2026-01-07 11:43:10', 0, 0),
(82, 48, '11104', 'Hector Martinez', 'SAP', 'cuautitlan@eqf.mx', '2', 'alta', 'e', '2026-01-07 19:01:21', 'cerrado', 11, '2026-01-07 12:06:55', NULL, '2026-01-07 12:44:11', NULL, NULL, '2026-01-07 18:01:21', '2026-01-07 18:44:11', NULL, 'TI', 1, '2026-01-07 12:06:44', 0, 0),
(83, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'zzzz', '2026-01-07 19:44:24', 'cerrado', 2, '2026-01-07 12:44:32', NULL, '2026-01-07 13:05:12', NULL, NULL, '2026-01-07 18:44:24', '2026-01-07 19:05:12', NULL, NULL, NULL, NULL, 0, 0),
(84, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'por favor', '2026-01-07 20:05:34', 'cerrado', 2, '2026-01-07 13:05:44', NULL, '2026-01-07 13:36:44', NULL, NULL, '2026-01-07 19:05:34', '2026-01-07 19:36:44', NULL, NULL, NULL, NULL, 0, 0),
(85, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '1', 'alta', 'ayuda', '2026-01-07 20:37:05', 'cerrado', 2, '2026-01-07 13:43:00', NULL, '2026-01-07 13:43:02', NULL, NULL, '2026-01-07 19:37:05', '2026-01-07 19:43:02', NULL, NULL, NULL, NULL, 0, 0),
(86, 48, '11104', 'Hector Martinez', 'TI', 'cuautitlan@eqf.mx', '2', 'alta', 'sdasda', '2026-01-07 20:53:42', 'cerrado', 2, '2026-01-07 16:02:27', NULL, '2026-01-07 16:20:43', NULL, NULL, '2026-01-07 19:53:42', '2026-01-07 22:20:43', NULL, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_assignments_log`
--

CREATE TABLE `ticket_assignments_log` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `from_analyst_id` int(11) DEFAULT NULL,
  `to_analyst_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_assignments_log`
--

INSERT INTO `ticket_assignments_log` (`id`, `ticket_id`, `from_analyst_id`, `to_analyst_id`, `admin_id`, `motivo`, `created_at`) VALUES
(1, 82, NULL, 2, 1, NULL, '2026-01-07 12:01:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `peso` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `subido_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_attachments`
--

INSERT INTO `ticket_attachments` (`id`, `ticket_id`, `nombre_archivo`, `ruta_archivo`, `peso`, `tipo`, `subido_en`) VALUES
(1, 2, 'HD.docx', 'uploads/tickets/ticket_2_692a1aff2f598.docx', NULL, NULL, '2025-11-28 21:58:23'),
(2, 2, 'KPIs.docx', 'uploads/tickets/ticket_2_692a1aff31c86.docx', NULL, NULL, '2025-11-28 21:58:23'),
(3, 28, '8dbd0520-b2c1-4b72-8ea8-cc500efbbbe2.jpg', 'uploads/tickets/ticket_28_694303c54318e.jpg', NULL, NULL, '2025-12-17 19:25:57'),
(4, 32, 'icon_helpdesk.png', 'uploads/tickets/ticket_32_6951800ca2922.png', NULL, NULL, '2025-12-28 19:07:56'),
(5, 35, 'icon_helpdesk.png', 'uploads/tickets/ticket_35_6951fe7192ea8.png', NULL, NULL, '2025-12-29 04:07:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_context`
--

CREATE TABLE `ticket_context` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `context_text` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_feedback`
--

CREATE TABLE `ticket_feedback` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `q1_attention` tinyint(4) NOT NULL,
  `q2_resolved` tinyint(4) NOT NULL,
  `q3_time` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `answered_at` datetime DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_feedback`
--

INSERT INTO `ticket_feedback` (`id`, `ticket_id`, `user_id`, `token`, `q1_attention`, `q2_resolved`, `q3_time`, `created_at`, `answered_at`, `comment`) VALUES
(1, 34, 48, 'd33d03142c05ac81ce21725a2354fb64a813b4930142289d687e2cbaf9b9325a', 3, 2, 3, '2025-12-28 14:05:05', '2025-12-28 14:05:25', ''),
(2, 35, 48, '0fd75629d25d34bb7085cdaafad06ae1555f14a3a532f7eadb83d1bdd8989fd7', 1, 2, 3, '2025-12-28 22:48:57', '2025-12-28 22:49:17', ''),
(3, 36, 48, 'da8656e1f0863b1e2528bd578dc0e484b3458d6a704c643201083fd5ea5b91f9', 2, 2, 2, '2025-12-28 23:07:14', '2025-12-28 23:07:22', ''),
(4, 37, 48, 'ef6a86bcc721e7f1f1511689bc9fa377bbd00ae851f67b8a1de7ea4e88e2a0fe', 3, 1, 3, '2025-12-28 23:07:54', '2025-12-28 23:08:06', ''),
(5, 38, 48, 'ef77e7411c11d5d3210c5a9748a6d0f74785a245ca4b47c7ba73e52a8c72a7cd', 2, 1, 1, '2025-12-28 23:07:58', '2025-12-28 23:08:11', ''),
(6, 39, 48, '0c4a6968582cd0f6e01881b7d40a4b1ddbb60bd71eeebdda8cea792c95457141', 3, 1, 3, '2025-12-28 23:13:30', '2025-12-28 23:13:37', ''),
(7, 40, 48, 'db3b08d7efdfc472c7ad77cbcb85921ea537ba13cebda426e754a912f45ae312', 3, 2, 2, '2025-12-29 09:06:32', '2025-12-29 09:07:00', 'joya'),
(8, 41, 48, 'ff0910b90e770ac7c8c6b22869551bde26a024bc01274a1a373572943adde3ab', 3, 1, 3, '2025-12-29 09:14:14', '2025-12-29 09:43:21', ''),
(9, 42, 48, 'b1f1475872ab5ed252e4d54891640a6cac907439f704695940b3ba33b3f96e80', 2, 2, 3, '2025-12-29 09:43:49', '2025-12-29 09:44:58', ''),
(10, 43, 48, 'bbc6e43d4a53eb9b8fe5c2e3b54e0455f9f4a68055839fb7ca75f0bf98a0ea86', 3, 1, 2, '2025-12-29 10:00:52', '2025-12-29 10:18:31', ''),
(11, 44, 48, 'd38274f2ddce83e28771418ff815f087535c29c91895b5852e1cb212f18f4f23', 3, 2, 3, '2025-12-29 10:20:35', '2025-12-29 10:21:47', 'SI JALA'),
(12, 45, 48, '52bf4c52c7d6333d96171f79584ebc84d3d033775cf24431bc0616cde2054a08', 3, 2, 3, '2025-12-29 16:15:19', '2025-12-29 16:15:38', ''),
(13, 46, 48, '5add3e9d455278120a54ed2e4be01f7ea3a3fd57f562df04cc15295b8efc9348', 1, 1, 1, '2025-12-29 16:19:32', '2025-12-29 16:20:18', ''),
(14, 47, 48, '170072461ecba3f4c82675f6daaf6be000a7c1808a53fde26132992b171a0164', 3, 2, 3, '2025-12-29 16:19:49', '2025-12-29 16:20:12', ''),
(15, 48, 48, '2de5abd6e4dacc7f975fff99008cae6aa30341ca58a55d49fedea4e02afc5295', 3, 2, 3, '2025-12-29 20:14:26', '2025-12-29 20:15:54', ''),
(16, 49, 11, '2526efc026a7d318259cae3eb4f2c03aff299f799c8d4eafd2ebd124d9f92a3a', 3, 2, 3, '2025-12-29 23:31:11', '2025-12-30 13:09:55', ''),
(17, 50, 48, '9e80dda4800c4bd85aa06ce02a30831103f74a63357be958cd11cde2b1a95698', 3, 2, 3, '2025-12-30 12:40:39', '2025-12-30 12:42:22', ''),
(18, 51, 11, '77e1512301deb614570531e23e3d5d159bb26e18e45913b7383b19fad6afe9a4', 2, 1, 3, '2025-12-30 13:41:42', '2025-12-30 13:41:53', ''),
(19, 52, 11, '173787fa2ba89bb9019987f318095c7c0e78abae9235edf69e0465dbb9504de0', 2, 1, 1, '2025-12-30 13:44:08', '2025-12-30 15:09:16', ''),
(20, 53, 11, '553edf54b4d4bc97ac84e7823a9698582ff4bf981ce9c1628f064dba4577c353', 3, 2, 3, '2025-12-30 15:10:58', '2025-12-30 15:11:09', ''),
(21, 54, 11, 'f119fa9f80bbdad3dd50a53f7a8fc4dc471551748eb234cf5cf9d4d80304ab1a', 3, 2, 3, '2025-12-30 15:16:21', '2025-12-30 15:16:27', ''),
(22, 55, 11, '0a97b6631c015ef5264ac60fac68a6e0b68599cdbfed4c6edab1746213f3a8bb', 3, 2, 3, '2025-12-30 17:21:29', '2025-12-30 17:21:36', ''),
(23, 56, 48, '73b2b24bac9f93e8dc1e00b873a7fa53c112ff914b4518a786349f4439c4a530', 3, 2, 3, '2025-12-31 10:09:45', '2025-12-31 10:09:52', ''),
(24, 57, 11, 'd9441613cca9fe6f7db6ee177941dbec1b9aeb66a7a0f78120deeabe6ad5f065', 3, 2, 3, '2025-12-31 10:12:38', '2025-12-31 10:12:48', ''),
(25, 58, 2, 'ea208fe7ba9d4b1605467130108ee7dc195736877ef3c81a93541631f4cf0ad1', 3, 2, 3, '2025-12-31 10:18:38', '2025-12-31 10:18:45', ''),
(26, 59, 48, '366b61e0a5ab2cacd0e3b92984e54384b5e75c765e8c7f0f357b2c0348c5aa38', 3, 2, 3, '2025-12-31 10:29:52', '2025-12-31 10:30:00', ''),
(27, 60, 48, '2a7db87ab2a8c0c7aafd086a0fdc3793f8947fb9f8c01a687815a2583b344d0c', 3, 2, 3, '2025-12-31 10:33:19', '2025-12-31 10:33:28', ''),
(28, 61, 48, '2d4d1ae11ea813290348593249176be6ada8c65cbb54c853142101a6a961eab7', 3, 2, 3, '2025-12-31 10:34:26', '2025-12-31 10:34:34', ''),
(29, 62, 48, 'bf4e100b5fc2de07b421ce8b4420ef6500537a8e8c490c647de2083b8976dbce', 2, 1, 2, '2025-12-31 11:46:56', '2025-12-31 11:47:01', ''),
(30, 63, 48, 'd669f7a0fa792499c62a227a41a85f4356315be897963a2754ad01f9f64be59d', 3, 2, 3, '2025-12-31 11:47:43', '2025-12-31 11:47:53', ''),
(31, 64, 48, '0277d158c7d8c66003768ed1bfe733f628fd2b825f308fa1e95ecd6d98313199', 3, 2, 3, '2026-01-01 22:18:23', '2026-01-01 22:24:56', ''),
(32, 65, 11, '689c3ce721e413b1229ad1fab865adc7d74f1fb81d157f8c7113679f6ac0461a', 3, 2, 3, '2026-01-01 22:29:35', '2026-01-02 18:05:09', ''),
(33, 66, 48, 'c7d3816e6eb981644a6b253c117218943a26d7a428acfd199ba5de47f0839078', 3, 2, 3, '2026-01-01 22:30:13', '2026-01-02 09:20:37', ''),
(34, 67, 48, '50227ca203e125d158a36bd931d5a74c3798ce9ff59ba29a4f6173efc02dca89', 3, 2, 3, '2026-01-02 09:24:25', '2026-01-02 09:25:30', ''),
(35, 68, 48, '972a459dae80fd0f5f7a4f560a8816a17a791d5a8f4123eb4a85036fb3169b5d', 3, 2, 3, '2026-01-02 09:47:29', '2026-01-02 10:15:53', ''),
(36, 69, 48, '0a28062b3ed3d58c4c92693b1681260eab6265a833ad284c8884508a3d25d4a4', 3, 2, 3, '2026-01-02 10:18:57', '2026-01-02 10:19:03', ''),
(37, 70, 48, 'e4447d7326516579f8d88c62a12fb6867e7e0e44e3b18e669f6e1488b18b7ae0', 3, 2, 3, '2026-01-02 18:12:27', '2026-01-02 18:13:02', ''),
(38, 71, 11, '1d66f768af9d5046fc1532f396ed24736b455a777aaaa8b505fa9c1f20c3db75', 1, 1, 1, '2026-01-02 18:19:55', '2026-01-02 18:20:18', 'ES GROSERO'),
(39, 72, 11, '8c36d89ca24c3147c8eb01bc036466aafb299e393d8f1bc5625ec81b916b8dcb', 3, 2, 3, '2026-01-05 17:31:21', '2026-01-05 17:31:27', ''),
(40, 76, 48, 'b8f1a5144f39a714fdafddfb6acafcf458e549755336b050f2ffafc111313387', 3, 2, 3, '2026-01-06 13:45:51', '2026-01-06 13:46:21', ''),
(41, 74, 48, 'ddb1369b98fc08fc507edfd6d70a6974a75c5e0087447b6d8d271efb32432206', 3, 2, 2, '2026-01-06 13:46:08', '2026-01-06 13:46:28', ''),
(42, 73, 48, '38f9c0ed78cca5fbaf4680ed723c5f552106753528b207489ce64892421f678e', 2, 1, 2, '2026-01-06 13:46:10', '2026-01-06 13:46:32', ''),
(43, 75, 48, '8f0286b476f667a0643da474f0f511559c59c76080edaa49c000fa5ebe527d06', 2, 1, 2, '2026-01-06 17:43:28', '2026-01-06 17:43:38', ''),
(44, 77, 48, 'e76c5c50111aed6cb5970dc26498ae7f236054b1670824133d777e2f3b0023f7', 3, 2, 3, '2026-01-06 17:46:59', '2026-01-06 17:47:07', ''),
(45, 78, 48, '69dbe19cee302dad053ea7f73f89297e881238ebb8f769d4ef3b765e00671454', 3, 2, 3, '2026-01-06 17:47:40', '2026-01-06 17:47:48', ''),
(46, 79, 48, '35f6bf2aec7add8ed1bbaa472312c38367e63d086e9a16f72bd19133abce076a', 3, 2, 3, '2026-01-07 09:02:10', '2026-01-07 09:02:33', ''),
(47, 80, 48, '144cbb9421819115dc4ca68f824cad4ca086271c93f503920f64f518c7115631', 3, 2, 3, '2026-01-07 11:41:45', '2026-01-07 11:41:57', ''),
(48, 81, 48, 'dd57c314f69133b955bd8080f25c74c164fc4cfc1f4aa7840c3805ecce908b94', 1, 1, 1, '2026-01-07 12:01:11', '2026-01-07 12:01:16', ''),
(49, 82, 48, 'e535d7e9a9d3df4343874c2c1c662e575114ab29766f6975a3279249d71091a5', 2, 1, 2, '2026-01-07 12:44:11', '2026-01-07 12:44:18', ''),
(50, 83, 48, 'a5a2c6f7d0a1035983abec4d8feb4a94b3c1fcc4661f4dc7c18de35c6e21a07e', 3, 2, 3, '2026-01-07 13:05:12', '2026-01-07 13:05:19', ''),
(51, 84, 48, '6c2eb5dd7587b525f9b92f1927ba4375135c454a88d4bae0b191235d367d1599', 3, 2, 3, '2026-01-07 13:36:44', '2026-01-07 13:36:51', ''),
(52, 85, 48, '1507c5f74926ddee811dea566989c8d17885b38f4e674ba8fb10fb9a5056d1f4', 3, 2, 3, '2026-01-07 13:43:02', '2026-01-07 13:43:11', ''),
(53, 86, 48, '26610d64f6d0e02b816a58d0ed960d4629747da58da1bc85e01a65c25d6940e1', 3, 2, 3, '2026-01-07 16:20:43', '2026-01-07 16:20:52', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('usuario','analista','admin','sa') NOT NULL,
  `mensaje` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_id`, `sender_role`, `mensaje`, `is_internal`, `created_at`) VALUES
(16, 17, 3, 'analista', '[Archivo adjunto]', 0, '2025-12-01 19:27:39'),
(17, 17, 3, 'analista', 'Paola ya regreso de la escuela', 0, '2025-12-01 19:36:00'),
(18, 17, 46, 'usuario', 'a poco?', 0, '2025-12-01 19:36:11'),
(19, 17, 3, 'analista', 'porque no hizo cierre ?', 0, '2025-12-01 19:36:26'),
(20, 17, 46, 'usuario', 'por tonto', 0, '2025-12-01 19:36:30'),
(21, 17, 3, 'analista', '[Archivo adjunto]', 0, '2025-12-01 19:36:46'),
(22, 18, 46, 'usuario', '[Archivo adjunto]', 0, '2025-12-01 22:17:33'),
(23, 17, 46, 'usuario', 'xd', 0, '2025-12-02 17:59:53'),
(24, 17, 46, 'usuario', 'caragiooo', 0, '2025-12-02 18:00:07'),
(25, 17, 46, 'usuario', '[Archivo adjunto]', 0, '2025-12-02 18:00:20'),
(26, 17, 46, 'usuario', '[Archivo adjunto]', 0, '2025-12-02 18:00:36'),
(27, 17, 46, 'usuario', 'Wendy hola', 0, '2025-12-02 18:29:57'),
(28, 17, 46, 'usuario', '[Archivo adjunto]', 0, '2025-12-02 18:30:08'),
(29, 17, 46, 'usuario', 'Angie feliz cumple', 0, '2025-12-02 18:40:58'),
(30, 17, 46, 'usuario', 'La chaim si vinoooooooo', 0, '2025-12-02 19:05:51'),
(31, 17, 46, 'usuario', 'hola que medicamentos tienes', 0, '2025-12-02 20:37:25'),
(32, 20, 3, 'analista', 'Hola', 0, '2025-12-03 19:52:01'),
(33, 20, 46, 'usuario', 'Que crees?, que me aparece esto en mi compu', 0, '2025-12-03 19:52:12'),
(34, 20, 3, 'analista', 'no me diga', 0, '2025-12-03 19:52:17'),
(36, 20, 46, 'usuario', 'xd', 0, '2025-12-03 19:54:49'),
(37, 20, 3, 'analista', 'va', 0, '2025-12-03 19:54:56'),
(38, 21, 3, 'analista', 'xd', 0, '2025-12-03 21:02:10'),
(39, 21, 46, 'usuario', 'que paso?', 0, '2025-12-03 21:02:17'),
(40, 20, 3, 'analista', 'sisiis', 0, '2025-12-03 21:46:56'),
(41, 20, 3, 'analista', 'xd', 0, '2025-12-03 21:47:30'),
(42, 20, 3, 'analista', '[Archivo adjunto]', 0, '2025-12-03 21:47:38'),
(43, 21, 46, 'usuario', 'ya jalo', 0, '2025-12-03 23:15:14'),
(44, 21, 46, 'usuario', 'ssiii', 0, '2025-12-03 23:15:20'),
(45, 21, 46, 'usuario', 'hmm', 0, '2025-12-03 23:15:29'),
(46, 21, 46, 'usuario', 'xd', 0, '2025-12-03 23:15:36'),
(47, 21, 3, 'analista', 'jala', 0, '2025-12-03 23:19:00'),
(48, 21, 3, 'analista', 'no jala', 0, '2025-12-03 23:19:06'),
(49, 21, 3, 'analista', 'xd', 0, '2025-12-03 23:23:42'),
(50, 21, 3, 'analista', 'xd', 0, '2025-12-03 23:23:54'),
(51, 21, 3, 'analista', 'xd', 0, '2025-12-03 23:29:53'),
(52, 21, 3, 'analista', 'si', 0, '2025-12-03 23:29:56'),
(53, 21, 46, 'usuario', 'xs', 0, '2025-12-03 23:39:18'),
(54, 21, 46, 'usuario', 'gggg', 0, '2025-12-03 23:39:24'),
(55, 21, 46, 'usuario', 'xd', 0, '2025-12-03 23:40:00'),
(56, 21, 3, 'analista', 'hmm', 0, '2025-12-03 23:43:04'),
(57, 21, 3, 'analista', 'xddd', 0, '2025-12-03 23:43:11'),
(58, 21, 46, 'usuario', 'funcionaraaa?', 0, '2025-12-03 23:58:58'),
(59, 21, 3, 'analista', 'chance si?', 0, '2025-12-03 23:59:35'),
(60, 21, 3, 'analista', 'okay, ameeen', 0, '2025-12-03 23:59:45'),
(61, 21, 3, 'analista', '[Archivo adjunto]', 0, '2025-12-03 23:59:48'),
(62, 21, 46, 'usuario', 'sii yaaa pinches funciono', 0, '2025-12-03 23:59:58'),
(63, 21, 3, 'analista', 'xd', 0, '2025-12-04 00:01:12'),
(64, 21, 3, 'analista', 'vavava', 0, '2025-12-04 00:12:58'),
(65, 20, 46, 'usuario', 'este?', 0, '2025-12-04 00:13:14'),
(66, 20, 3, 'analista', 'vava feka', 0, '2025-12-04 00:13:21'),
(67, 21, 46, 'usuario', 'aun funcionas?', 0, '2025-12-08 19:50:49'),
(68, 21, 3, 'analista', 'creo que si xd', 0, '2025-12-08 19:50:58'),
(69, 19, 46, 'usuario', 'hola, tengo una replica del dia 08/12', 0, '2025-12-08 21:10:40'),
(70, 19, 11, 'analista', 'claro la apoyo', 0, '2025-12-08 21:10:49'),
(71, 19, 11, 'analista', 'mi foto de perfil jaja', 0, '2025-12-08 21:10:59'),
(72, 19, 46, 'usuario', 'esta geniiial jajaj', 0, '2025-12-08 21:11:11'),
(73, 19, 46, 'usuario', 'la de nosotros', 0, '2025-12-08 21:11:23'),
(74, 19, 46, 'usuario', 'xd', 0, '2025-12-08 21:27:34'),
(75, 19, 11, 'analista', 'xd', 0, '2025-12-08 21:53:43'),
(76, 19, 46, 'usuario', 'funciono?', 0, '2025-12-08 21:53:57'),
(77, 26, 46, 'usuario', 'gracias', 0, '2025-12-09 21:22:14'),
(78, 26, 3, 'analista', 'la apoyo', 0, '2025-12-09 21:22:19'),
(79, 28, 2, 'analista', 'carajo', 0, '2025-12-17 19:27:14'),
(80, 30, 2, 'analista', 'xd', 0, '2025-12-22 18:12:02'),
(81, 30, 48, 'usuario', 'creo que si funciona', 0, '2025-12-22 18:12:11'),
(82, 30, 2, 'analista', 'que te digo', 0, '2025-12-22 18:12:17'),
(83, 30, 11, 'analista', 'xd', 0, '2025-12-23 15:16:09'),
(84, 31, 2, 'analista', 'a ver si jala', 0, '2025-12-23 23:11:47'),
(85, 31, 48, 'usuario', '[Archivo adjunto]', 0, '2025-12-23 23:11:55'),
(86, 31, 2, 'analista', 'si jala', 0, '2025-12-23 23:12:09'),
(87, 31, 48, 'usuario', 'intercambio de dm', 0, '2025-12-23 23:12:16'),
(88, 31, 2, 'analista', 'si eso si', 0, '2025-12-23 23:12:20'),
(89, 31, 2, 'analista', 'se resolvio perfectamente solo eran actualizaciones pendientes en el equipoy un reinicio', 0, '2025-12-23 23:12:56'),
(90, 31, 48, 'usuario', 'pendejo yo', 0, '2025-12-23 23:16:34'),
(91, 31, 2, 'analista', 'chance si, pendejo tu', 0, '2025-12-23 23:16:48'),
(92, 31, 2, 'analista', 'jalo?', 1, '2025-12-23 23:16:57'),
(93, 31, 48, 'usuario', 'si jalo JAJA', 0, '2025-12-23 23:17:52'),
(94, 31, 2, 'analista', 'prueba normal de chat', 0, '2025-12-23 23:18:54'),
(95, 31, 48, 'usuario', 'si pues si', 0, '2025-12-23 23:18:58'),
(96, 31, 2, 'analista', 'dafne 123', 0, '2025-12-23 23:19:18'),
(97, 31, 48, 'usuario', 'yoyooy', 0, '2025-12-23 23:19:23'),
(98, 31, 2, 'analista', 'xd', 1, '2025-12-23 23:20:08'),
(99, 31, 2, 'analista', 'si hasta luego', 0, '2025-12-23 23:20:26'),
(100, 31, 2, 'analista', 'se resolvio perfectamente solo eran actualizaciones pendientes en el equipoy un reinicio', 1, '2025-12-23 23:20:31'),
(101, 32, 2, 'analista', 'Hola', 0, '2025-12-28 19:08:28'),
(102, 32, 2, 'analista', 'Que problema hay?', 0, '2025-12-28 19:08:42'),
(103, 32, 48, 'usuario', 'No puedo facturar', 0, '2025-12-28 19:08:48'),
(104, 32, 2, 'analista', 'un babosoteeee', 1, '2025-12-28 19:08:56'),
(105, 32, 2, 'analista', 'Claro, voy me pasa el ID de favor', 0, '2025-12-28 19:09:07'),
(106, 32, 48, 'usuario', '1556987233', 0, '2025-12-28 19:09:22'),
(107, 33, 48, 'usuario', 'lo checamos', 0, '2025-12-28 19:15:08'),
(108, 33, 2, 'analista', 'lo checamos', 0, '2025-12-28 19:15:13'),
(109, 33, 2, 'analista', 'quedamos atentos', 0, '2025-12-28 19:15:16'),
(110, 35, 2, 'analista', 'hola', 0, '2025-12-29 04:22:19'),
(111, 35, 48, 'usuario', 'HOLA', 0, '2025-12-29 04:22:43'),
(112, 35, 48, 'usuario', 'OYE', 0, '2025-12-29 04:22:57'),
(113, 35, 2, 'analista', 'MANDE', 0, '2025-12-29 04:24:22'),
(114, 45, 2, 'analista', 'xd', 0, '2025-12-29 22:14:44'),
(115, 45, 48, 'usuario', 'hola', 0, '2025-12-29 22:14:48'),
(116, 45, 2, 'analista', 'se resolvio reportando', 1, '2025-12-29 22:14:59'),
(117, 49, 2, 'analista', 'hola mi amor', 0, '2025-12-30 05:29:58'),
(118, 55, 2, 'analista', 'hola', 0, '2025-12-30 21:16:51'),
(119, 56, 11, 'analista', 'hola', 0, '2025-12-30 21:21:47'),
(120, 56, 48, 'usuario', 'aqui si funcionas', 0, '2025-12-30 21:21:55'),
(121, 56, 11, 'analista', 'obvio que si papi', 0, '2025-12-30 21:22:06'),
(122, 56, 48, 'usuario', 'gracias', 0, '2025-12-30 21:22:13'),
(123, 56, 11, 'analista', 'de nada', 0, '2025-12-30 21:22:25'),
(124, 56, 11, 'analista', 'xd', 0, '2025-12-30 22:19:28'),
(125, 55, 11, 'analista', 'ya jala?', 0, '2025-12-30 23:17:49'),
(126, 55, 2, 'analista', 'Al parecer si', 0, '2025-12-30 23:17:58'),
(127, 55, 2, 'analista', 'ESTA NOTA LA PUEDES VER?', 1, '2025-12-30 23:19:17'),
(128, 55, 11, 'analista', 'SI', 1, '2025-12-30 23:19:27'),
(129, 58, 2, 'analista', 'xd', 0, '2025-12-31 16:18:27'),
(130, 58, 2, 'analista', 'xd', 0, '2025-12-31 16:18:31'),
(131, 65, 11, 'analista', 'te odioooo', 0, '2026-01-02 04:09:18'),
(132, 65, 2, 'analista', 'ya no me quieres?', 0, '2026-01-02 04:09:32'),
(133, 65, 11, 'analista', 'noo, hueles a kk', 0, '2026-01-02 04:09:41'),
(134, 70, 48, 'usuario', 'HOLA', 0, '2026-01-03 00:08:24'),
(135, 70, 11, 'analista', 'HOLA', 0, '2026-01-03 00:08:29'),
(136, 70, 48, 'usuario', '[Archivo adjunto]', 0, '2026-01-03 00:09:24'),
(137, 71, 11, 'analista', 'HOLA NIÑO', 0, '2026-01-03 00:16:29'),
(138, 71, 2, 'analista', 'HOLA NIÑA', 0, '2026-01-03 00:16:38'),
(139, 71, 11, 'analista', 'ME CAES MAL', 0, '2026-01-03 00:17:12'),
(140, 71, 2, 'analista', 'LO SOSPECHABA):', 0, '2026-01-03 00:17:25'),
(141, 71, 11, 'analista', 'NO ES CIERTO', 0, '2026-01-03 00:17:52'),
(142, 71, 2, 'analista', 'DAME UN BESO', 0, '2026-01-03 00:17:59'),
(143, 71, 11, 'analista', 'NO PORQUE TE ENAMORAS', 0, '2026-01-03 00:19:31'),
(144, 72, 11, 'analista', 'Hola mi amor', 0, '2026-01-05 02:56:50'),
(145, 72, 2, 'analista', 'Hola mi amor', 0, '2026-01-05 02:56:56'),
(146, 72, 2, 'analista', 'Ya no me odias?', 0, '2026-01-05 02:57:02'),
(147, 72, 11, 'analista', 'Ya no', 0, '2026-01-05 02:57:06'),
(148, 72, 2, 'analista', 'gracias', 0, '2026-01-05 02:57:21'),
(149, 72, 11, 'analista', 'te amo', 0, '2026-01-05 02:57:25'),
(150, 72, 2, 'analista', '<3', 0, '2026-01-05 02:57:30'),
(151, 72, 2, 'analista', '😭😭😭', 0, '2026-01-05 02:57:37'),
(152, 72, 11, 'analista', 'sigue funcionando?', 0, '2026-01-05 19:09:23'),
(153, 72, 2, 'analista', 'si', 0, '2026-01-05 19:10:06'),
(154, 72, 2, 'analista', 'creo', 0, '2026-01-05 19:10:09'),
(155, 72, 11, 'analista', 'va', 0, '2026-01-05 19:10:28'),
(156, 73, 2, 'analista', 'HOLA', 0, '2026-01-05 19:48:50'),
(157, 73, 48, 'usuario', 'HOLA', 0, '2026-01-05 19:48:54'),
(158, 72, 2, 'analista', 'AUN', 0, '2026-01-05 23:30:06'),
(159, 72, 2, 'analista', 'S', 0, '2026-01-05 23:30:09'),
(160, 72, 2, 'analista', 'SS', 0, '2026-01-05 23:30:11'),
(161, 72, 11, 'analista', 'si', 0, '2026-01-05 23:30:31'),
(162, 72, 2, 'analista', 'va', 0, '2026-01-05 23:30:37'),
(163, 73, 2, 'analista', 'dxxx', 0, '2026-01-05 23:34:02'),
(164, 76, 2, 'analista', 'zs', 0, '2026-01-06 19:22:41'),
(165, 76, 48, 'usuario', 'si jala?', 0, '2026-01-06 19:22:47'),
(166, 76, 2, 'analista', 'holaaaaa mi amor', 0, '2026-01-06 19:24:39'),
(167, 75, 48, 'usuario', 'hola', 0, '2026-01-06 23:14:31'),
(168, 75, 48, 'usuario', 'prueba de canalizacion de ticket', 0, '2026-01-06 23:14:37'),
(169, 75, 2, 'analista', 'Prueba de transferencia de datos', 0, '2026-01-06 23:19:18'),
(170, 75, 2, 'analista', '[Archivo adjunto]', 0, '2026-01-06 23:19:23'),
(171, 75, 48, 'usuario', '[Archivo adjunto]', 0, '2026-01-06 23:19:27'),
(172, 75, 2, 'analista', 'ya se reiniciaron catalogos', 1, '2026-01-06 23:19:42'),
(173, 75, 11, 'analista', 'hola', 0, '2026-01-06 23:41:20'),
(174, 79, 48, 'usuario', 'hola', 0, '2026-01-07 14:40:10'),
(175, 79, 48, 'usuario', 'no puedo, me marcan en 0', 0, '2026-01-07 14:40:16'),
(176, 79, 2, 'analista', 'ah okay, eso es tema de SAP, sobre esta misma conversacion, se va a canalizar para alla', 0, '2026-01-07 14:40:39'),
(177, 79, 48, 'usuario', 'perfecto, gracias', 0, '2026-01-07 14:40:45'),
(178, 79, 2, 'analista', 'buen dia', 0, '2026-01-07 14:40:55'),
(179, 80, 48, 'usuario', 'prueba de canalizacion', 0, '2026-01-07 15:03:18'),
(180, 80, 11, 'analista', 'Hola', 0, '2026-01-07 17:33:34'),
(181, 80, 11, 'analista', 'canalicemoz de regreso', 0, '2026-01-07 17:33:42'),
(182, 80, 48, 'usuario', 'oka', 0, '2026-01-07 17:33:47'),
(183, 80, 48, 'usuario', 'hmm se ve izquierdo verdad', 0, '2026-01-07 17:34:02'),
(184, 80, 11, 'analista', 'aca normal verdad en usuario?', 0, '2026-01-07 17:34:32'),
(185, 81, 2, 'analista', 'conversemos un poquito', 0, '2026-01-07 17:42:23'),
(186, 81, 48, 'usuario', 'claor', 0, '2026-01-07 17:42:28'),
(187, 81, 2, 'analista', 'prueba 2 intercambio', 0, '2026-01-07 17:42:37'),
(188, 81, 48, 'usuario', 'okay vamos a SAP', 0, '2026-01-07 17:42:46'),
(189, 81, 48, 'usuario', '[Archivo adjunto]', 0, '2026-01-07 17:42:50'),
(190, 81, 2, 'analista', 'hola', 1, '2026-01-07 17:42:58'),
(191, 81, 11, 'analista', 'aparte platicamos', 0, '2026-01-07 17:44:59'),
(192, 81, 48, 'usuario', 'el chat queda izquierdo', 0, '2026-01-07 17:45:08'),
(193, 81, 48, 'usuario', 'si', 0, '2026-01-07 18:00:52'),
(194, 81, 11, 'analista', 'chance si', 0, '2026-01-07 18:00:59'),
(195, 82, 2, 'analista', 'hola', 0, '2026-01-07 18:02:35'),
(196, 82, 48, 'usuario', 'hola', 0, '2026-01-07 18:02:41'),
(197, 82, 2, 'analista', 'intentemos', 0, '2026-01-07 18:06:23'),
(198, 82, 48, 'usuario', 'va', 0, '2026-01-07 18:06:26'),
(199, 82, 11, 'analista', 'en efecto', 0, '2026-01-07 18:07:11'),
(200, 82, 48, 'usuario', 'ya no se transfirio', 0, '2026-01-07 18:07:20'),
(201, 82, 11, 'analista', 'ya', 0, '2026-01-07 18:39:03'),
(202, 82, 48, 'usuario', 'no', 0, '2026-01-07 18:39:09'),
(203, 82, 11, 'analista', 'depalnoddddddddd', 0, '2026-01-07 18:39:29'),
(204, 82, 11, 'analista', 'ya quedo creo', 0, '2026-01-07 18:43:23'),
(205, 83, 48, 'usuario', 'hola inge', 0, '2026-01-07 18:44:44'),
(206, 83, 2, 'analista', 'hola, me apoya con el ID del TV', 0, '2026-01-07 18:44:56'),
(207, 83, 48, 'usuario', 'funciona?', 0, '2026-01-07 19:03:48'),
(208, 83, 2, 'analista', 'al parecer si', 0, '2026-01-07 19:03:53'),
(209, 84, 2, 'analista', 'cf', 0, '2026-01-07 19:09:19'),
(210, 84, 2, 'analista', 'gracias, quedo?', 0, '2026-01-07 19:22:06'),
(211, 84, 48, 'usuario', 'si si quedo', 0, '2026-01-07 19:22:11'),
(212, 84, 2, 'analista', 'dios?', 0, '2026-01-07 19:33:33'),
(213, 84, 48, 'usuario', 'carajooo', 0, '2026-01-07 19:33:37'),
(214, 84, 2, 'analista', '[Archivo adjunto]', 0, '2026-01-07 19:33:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_message_files`
--

CREATE TABLE `ticket_message_files` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_message_files`
--

INSERT INTO `ticket_message_files` (`id`, `message_id`, `ticket_id`, `file_name`, `file_path`, `file_type`, `created_at`) VALUES
(1, 16, 17, 'pp_sa.jpg', 'uploads/ticket_messages/t17_m16_692dec2b2cbf9.jpg', 'image/jpeg', '2025-12-01 19:27:39'),
(2, 21, 17, 'pp_sa.jpg', 'uploads/ticket_messages/t17_m21_692dee4e34179.jpg', 'image/jpeg', '2025-12-01 19:36:46'),
(3, 22, 18, 'icon2.png', 'uploads/ticket_messages/t18_m22_692e13fdc2697.png', 'image/png', '2025-12-01 22:17:33'),
(4, 25, 17, 'Fondos de pantalla 2_1-2.jpg', 'uploads/ticket_messages/t17_m25_692f29343706e.jpg', 'image/jpeg', '2025-12-02 18:00:20'),
(5, 26, 17, 'CV_BrandonSuarez.pdf', 'uploads/ticket_messages/t17_m26_692f2944e8282.pdf', 'application/pdf', '2025-12-02 18:00:36'),
(6, 28, 17, 'fondo.png', 'uploads/ticket_messages/t17_m28_692f3030ee80d.png', 'image/png', '2025-12-02 18:30:08'),
(7, 42, 20, 'Fondos de pantalla 2_1-2.jpg', 'uploads/ticket_messages/t20_m42_6930affa189b0.jpg', 'image/jpeg', '2025-12-03 21:47:38'),
(8, 61, 21, 'Fondos de pantalla 2_1-2.jpg', 'uploads/ticket_messages/t21_m61_6930cef402cfc.jpg', 'image/jpeg', '2025-12-03 23:59:48'),
(9, 71, 19, 'pp_admin.jpg', 'uploads/ticket_messages/t19_m71_69373ee3424c3.jpg', 'image/jpeg', '2025-12-08 21:10:59'),
(10, 73, 19, 'pp_sucursal.jpg', 'uploads/ticket_messages/t19_m73_69373efb4e2d5.jpg', 'image/jpeg', '2025-12-08 21:11:23'),
(11, 85, 31, 'ticket_27 (1).pdf', 'uploads/ticket_messages/t31_m85_694b21bb5292c.pdf', 'application/pdf', '2025-12-23 23:11:55'),
(12, 136, 70, 'icon_helpdesk.png', 'uploads/ticket_messages/t70_m136_69585e348d1cf.png', 'image/png', '2026-01-03 00:09:24'),
(13, 170, 75, 'icon_helpdesk.png', 'uploads/ticket_messages/t75_m170_695d987b9c5d8.png', 'image/png', '2026-01-06 23:19:23'),
(14, 171, 75, 'icon_helpdesk.png', 'uploads/ticket_messages/t75_m171_695d987fea93c.png', 'image/png', '2026-01-06 23:19:27'),
(15, 189, 81, 'ESTADO DE CUENTA.pdf', 'uploads/ticket_messages/t81_m189_695e9b1a5a5ec.pdf', 'application/pdf', '2026-01-07 17:42:50'),
(16, 214, 84, 'Anexo Levantamientos de requerimietnos.docx', 'uploads/ticket_messages/t84_m214_695eb52298519.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-01-07 19:33:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_notifications`
--

CREATE TABLE `ticket_notifications` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_notifications`
--

INSERT INTO `ticket_notifications` (`id`, `ticket_id`, `user_id`, `mensaje`, `created_at`, `leido`) VALUES
(1, 21, 46, 'Tu ticket #21 será atendido por Paola Martinez.', '2025-12-03 21:01:57', 1),
(2, 19, 46, 'Tu ticket #19 será atendido por Aidee Jimenez.', '2025-12-08 21:10:10', 1),
(3, 22, 46, 'Tu ticket #22 será atendido por Aidee Jimenez.', '2025-12-08 22:58:30', 1),
(4, 23, 46, 'Tu ticket #23 será atendido por Aidee Jimenez.', '2025-12-08 23:15:49', 1),
(5, 25, 46, 'Tu ticket #25 será atendido por Paola Martinez.', '2025-12-09 00:14:04', 1),
(6, 26, 46, 'Tu ticket #26 será atendido por Paola Martinez.', '2025-12-09 21:22:03', 1),
(7, 27, 46, 'Tu ticket #27 será atendido por Brandon Suarez.', '2025-12-09 23:39:17', 1),
(8, 28, 48, 'Tu ticket #28 será atendido por Brandon Suarez.', '2025-12-17 19:26:34', 1),
(9, 29, 48, 'Tu ticket #29 será atendido por Aidee Jimenez.', '2025-12-18 16:49:14', 1),
(10, 30, 48, 'Tu ticket #30 será atendido por Aidee Jimenez.', '2025-12-23 15:15:28', 1),
(11, 31, 48, 'Tu ticket #31 será atendido por Brandon Suarez.', '2025-12-23 23:11:33', 1),
(12, 32, 48, 'Tu ticket #32 será atendido por Brandon Suarez.', '2025-12-28 19:08:10', 1),
(13, 33, 48, 'Tu ticket #33 será atendido por Brandon Suarez.', '2025-12-28 19:14:56', 1),
(14, 34, 48, 'Tu ticket #34 será atendido por Brandon Suarez.', '2025-12-28 20:05:00', 1),
(15, 35, 48, 'Tu ticket #35 será atendido por Brandon Suarez.', '2025-12-29 04:18:10', 1),
(16, 36, 48, 'Tu ticket #36 será atendido por Brandon Suarez.', '2025-12-29 05:07:09', 1),
(17, 37, 48, 'Tu ticket #37 será atendido por Brandon Suarez.', '2025-12-29 05:07:52', 1),
(18, 38, 48, 'Tu ticket #38 será atendido por Brandon Suarez.', '2025-12-29 05:07:56', 1),
(19, 39, 48, 'Tu ticket #39 será atendido por Brandon Suarez.', '2025-12-29 05:13:28', 1),
(20, 40, 48, 'Tu ticket #40 será atendido por Brandon Suarez.', '2025-12-29 15:06:22', 1),
(21, 41, 48, 'Tu ticket #41 será atendido por Brandon Suarez.', '2025-12-29 15:14:11', 1),
(22, 42, 48, 'Tu ticket #42 será atendido por Brandon Suarez.', '2025-12-29 15:43:44', 1),
(23, 43, 48, 'Tu ticket #43 será atendido por Brandon Suarez.', '2025-12-29 16:00:47', 1),
(24, 44, 48, 'Tu ticket #44 será atendido por Brandon Suarez.', '2025-12-29 16:19:32', 1),
(25, 45, 48, 'Tu ticket #45 será atendido por Brandon Suarez.', '2025-12-29 22:14:22', 1),
(26, 46, 48, 'Tu ticket #46 será atendido por Brandon Suarez.', '2025-12-29 22:19:30', 1),
(27, 47, 48, 'Tu ticket #47 será atendido por Brandon Suarez.', '2025-12-29 22:19:47', 1),
(28, 49, 11, 'Tu ticket #49 será atendido por Brandon Suarez.', '2025-12-30 05:02:22', 1),
(29, 50, 48, 'Tu ticket #50 será atendido por Aidee Jimenez.', '2025-12-30 18:40:34', 1),
(30, 51, 11, 'Tu ticket #51 será atendido por Brandon Suarez.', '2025-12-30 19:19:16', 1),
(31, 52, 11, 'Tu ticket #52 será atendido por Brandon Suarez.', '2025-12-30 19:43:58', 0),
(32, 53, 11, 'Tu ticket #53 será atendido por Brandon Suarez.', '2025-12-30 21:10:31', 0),
(33, 54, 11, 'Tu ticket #54 será atendido por Brandon Suarez.', '2025-12-30 21:16:16', 0),
(34, 55, 11, 'Tu ticket #55 será atendido por Brandon Suarez.', '2025-12-30 21:16:43', 0),
(35, 56, 48, 'Tu ticket #56 será atendido por Aidee Jimenez.', '2025-12-30 21:21:43', 1),
(36, 57, 11, 'Tu ticket #57 será atendido por Brandon Suarez.', '2025-12-31 16:12:16', 0),
(37, 58, 2, 'Tu ticket #58 será atendido por Brandon Suarez.', '2025-12-31 16:18:21', 0),
(38, 59, 48, 'Tu ticket #59 será atendido por Brandon Suarez.', '2025-12-31 16:28:02', 1),
(39, 60, 48, 'Tu ticket #60 será atendido por Brandon Suarez.', '2025-12-31 16:30:19', 1),
(40, 61, 48, 'Tu ticket #61 será atendido por Brandon Suarez.', '2025-12-31 16:34:22', 1),
(41, 62, 48, 'Tu ticket #62 será atendido por Brandon Suarez.', '2025-12-31 17:46:53', 1),
(42, 63, 48, 'Tu ticket #63 será atendido por Brandon Suarez.', '2025-12-31 17:47:42', 1),
(43, 64, 48, 'Tu ticket #64 será atendido por Brandon Suarez.', '2026-01-02 04:00:15', 1),
(44, 65, 11, 'Tu ticket #65 será atendido por Brandon Suarez.', '2026-01-02 04:02:25', 0),
(45, 66, 48, 'Tu ticket #66 será atendido por Brandon Suarez.', '2026-01-02 04:29:53', 1),
(46, 67, 48, 'Tu ticket #67 será atendido por Brandon Suarez.', '2026-01-02 15:20:59', 1),
(47, 68, 48, 'Tu ticket #68 será atendido por Brandon Suarez.', '2026-01-02 15:45:47', 1),
(48, 69, 48, 'Tu ticket #69 será atendido por Brandon Suarez.', '2026-01-02 16:18:39', 1),
(49, 70, 48, 'Tu ticket #70 será atendido por Aidee Jimenez.', '2026-01-03 00:07:53', 1),
(50, 71, 11, 'Tu ticket #71 será atendido por Brandon Suarez.', '2026-01-03 00:16:08', 0),
(51, 72, 11, 'Tu ticket #72 será atendido por Brandon Suarez.', '2026-01-05 02:56:12', 0),
(52, 73, 48, 'Tu ticket #73 será atendido por Brandon Suarez.', '2026-01-05 19:48:34', 1),
(53, 73, 48, 'Tu ticket #73 será atendido por Brandon Suarez.', '2026-01-05 23:31:17', 1),
(54, 74, 48, 'Tu ticket #74 será atendido por Brandon Suarez.', '2026-01-06 17:13:10', 1),
(55, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 18:59:58', 1),
(56, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 19:00:13', 1),
(57, 76, 48, 'Tu ticket #76 será atendido por Brandon Suarez.', '2026-01-06 19:03:21', 1),
(58, 76, 48, 'Tu ticket #76 será atendido por Aidee Jimenez.', '2026-01-06 19:25:10', 1),
(59, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 19:46:11', 1),
(60, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 19:57:46', 1),
(61, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 21:38:00', 1),
(62, 75, 48, 'Tu ticket #75 será atendido por Brandon Suarez.', '2026-01-06 22:58:44', 1),
(63, 75, 48, 'Tu ticket #75 será atendido por Aidee Jimenez.', '2026-01-06 23:22:00', 1),
(64, 75, 48, 'Tu ticket #75 será atendido por Aidee Jimenez.', '2026-01-06 23:40:24', 1),
(65, 77, 48, 'Tu ticket #77 será atendido por Aidee Jimenez.', '2026-01-06 23:44:03', 1),
(66, 78, 48, 'Tu ticket #78 será atendido por Aidee Jimenez.', '2026-01-06 23:47:38', 1),
(67, 74, 48, 'Tu ticket #74 será atendido por Aidee Jimenez.', '2026-01-07 14:38:46', 1),
(68, 74, 48, 'Tu ticket #74 será atendido por Aidee Jimenez.', '2026-01-07 14:38:58', 1),
(69, 79, 48, 'Tu ticket #79 será atendido por Brandon Suarez.', '2026-01-07 14:39:26', 1),
(70, 79, 48, 'Tu ticket #79 será atendido por Aidee Jimenez.', '2026-01-07 14:46:52', 1),
(71, 80, 48, 'Tu ticket #80 será atendido por Aidee Jimenez.', '2026-01-07 15:03:45', 1),
(72, 81, 48, 'Tu ticket #81 será atendido por Brandon Suarez.', '2026-01-07 17:42:09', 1),
(73, 81, 48, 'Tu ticket #81 será atendido por Aidee Jimenez.', '2026-01-07 17:43:18', 1),
(74, 82, 48, 'Tu ticket #82 será atendido por Aidee Jimenez.', '2026-01-07 18:06:55', 1),
(75, 83, 48, 'Tu ticket #83 será atendido por Brandon Suarez.', '2026-01-07 18:44:32', 1),
(76, 84, 48, 'Tu ticket #84 será atendido por Brandon Suarez.', '2026-01-07 19:05:44', 1),
(77, 85, 48, 'Tu ticket #85 será atendido por Brandon Suarez.', '2026-01-07 19:43:00', 1),
(78, 86, 48, 'Tu ticket #86 será atendido por Brandon Suarez.', '2026-01-07 19:56:54', 1),
(79, 86, 48, 'Tu ticket #86 será atendido por Brandon Suarez.', '2026-01-07 21:48:09', 1),
(80, 86, 48, 'Tu ticket #86 será atendido por Brandon Suarez.', '2026-01-07 21:48:35', 1),
(81, 86, 48, 'Tu ticket #86 será atendido por Brandon Suarez.', '2026-01-07 22:02:27', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_reads`
--

CREATE TABLE `ticket_reads` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_read_message_id` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_reads`
--

INSERT INTO `ticket_reads` (`id`, `ticket_id`, `user_id`, `last_read_message_id`, `updated_at`) VALUES
(1, 35, 2, 113, '2025-12-28 22:25:12'),
(2, 35, 48, 113, '2025-12-28 22:24:59'),
(7, 45, 2, 0, '2025-12-29 16:14:41'),
(8, 45, 48, 114, '2025-12-29 16:14:45'),
(9, 49, 2, 0, '2025-12-29 23:02:58'),
(10, 49, 11, 117, '2025-12-29 23:30:20'),
(14, 51, 11, 0, '2025-12-30 13:19:41'),
(15, 51, 2, 0, '2025-12-30 13:19:48'),
(18, 55, 2, 118, '2025-12-30 16:19:21'),
(19, 55, 11, 118, '2025-12-30 15:16:53'),
(20, 56, 11, 0, '2025-12-30 15:21:45'),
(21, 56, 48, 124, '2025-12-31 10:09:31'),
(26, 58, 2, 129, '2025-12-31 10:18:28'),
(28, 65, 11, 0, '2026-01-01 22:09:11'),
(29, 65, 2, 133, '2026-01-01 22:19:32'),
(30, 64, 48, 0, '2026-01-01 22:18:17'),
(33, 66, 48, 0, '2026-01-01 22:30:08'),
(34, 70, 48, 0, '2026-01-02 18:08:19'),
(35, 70, 11, 134, '2026-01-02 18:08:25'),
(36, 71, 2, 0, '2026-01-02 18:16:13'),
(37, 71, 11, 0, '2026-01-02 18:16:15'),
(38, 72, 2, 155, '2026-01-05 17:30:01'),
(39, 72, 11, 162, '2026-01-05 17:30:39'),
(43, 73, 2, 163, '2026-01-06 10:58:03'),
(44, 73, 48, 156, '2026-01-05 13:48:51'),
(51, 76, 2, 165, '2026-01-06 13:24:34'),
(52, 76, 48, 164, '2026-01-06 13:22:43'),
(54, 76, 11, 166, '2026-01-06 13:25:11'),
(55, 75, 48, 171, '2026-01-06 17:23:32'),
(57, 75, 2, 172, '2026-01-06 17:21:27'),
(59, 75, 11, 172, '2026-01-06 17:21:48'),
(67, 77, 11, 0, '2026-01-06 17:45:07'),
(68, 74, 11, 0, '2026-01-07 08:38:47'),
(69, 79, 48, 0, '2026-01-07 08:40:07'),
(70, 79, 2, 175, '2026-01-07 08:40:22'),
(71, 79, 11, 178, '2026-01-07 08:46:55'),
(72, 80, 48, 181, '2026-01-07 11:33:44'),
(73, 80, 11, 179, '2026-01-07 09:03:46'),
(76, 81, 2, 0, '2026-01-07 11:42:17'),
(77, 81, 48, 192, '2026-01-07 11:47:41'),
(78, 81, 11, 192, '2026-01-07 11:47:29'),
(84, 82, 2, 0, '2026-01-07 12:01:48'),
(85, 82, 48, 199, '2026-01-07 12:07:13'),
(86, 82, 11, 203, '2026-01-07 12:43:17'),
(93, 83, 48, 206, '2026-01-07 13:03:42'),
(94, 83, 2, 206, '2026-01-07 13:03:33'),
(97, 84, 2, 211, '2026-01-07 13:33:27'),
(100, 84, 48, 211, '2026-01-07 13:33:26'),
(110, 86, 2, 0, '2026-01-07 16:02:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_transfers`
--

CREATE TABLE `ticket_transfers` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `from_area` varchar(50) NOT NULL,
  `to_area` varchar(50) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_transfers`
--

INSERT INTO `ticket_transfers` (`id`, `ticket_id`, `from_area`, `to_area`, `admin_id`, `motivo`, `created_at`) VALUES
(1, 76, 'TI', 'SAP', 1, 'ayuda es primero alla', '2026-01-06 13:24:53'),
(2, 75, 'TI', 'SAP', 1, 'tiene replicas pendientes', '2026-01-06 17:21:19'),
(3, 74, 'TI', 'SAP', 1, NULL, '2026-01-06 17:53:13'),
(4, 79, 'TI', 'SAP', 1, NULL, '2026-01-07 08:46:45'),
(5, 80, 'TI', 'SAP', 1, NULL, '2026-01-07 09:03:40'),
(6, 81, 'TI', 'SAP', 1, NULL, '2026-01-07 11:43:09'),
(7, 82, 'TI', 'SAP', 1, 'ayuda con su cierre', '2026-01-07 12:06:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_transfer_files`
--

CREATE TABLE `ticket_transfer_files` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_transfer_messages`
--

CREATE TABLE `ticket_transfer_messages` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_name` varchar(120) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket_transfer_messages`
--

INSERT INTO `ticket_transfer_messages` (`id`, `transfer_id`, `ticket_id`, `sender_role`, `sender_name`, `message`, `created_at`) VALUES
(1, 1, 76, 'analista', 'Brandon Suarez', 'zs', '2026-01-06 13:22:41'),
(2, 1, 76, 'usuario', 'Hector Martinez', 'si jala?', '2026-01-06 13:22:47'),
(3, 1, 76, 'analista', 'Brandon Suarez', 'holaaaaa mi amor', '2026-01-06 13:24:39'),
(4, 2, 75, 'usuario', 'Hector Martinez', 'hola', '2026-01-06 17:14:31'),
(5, 2, 75, 'usuario', 'Hector Martinez', 'prueba de canalizacion de ticket', '2026-01-06 17:14:37'),
(6, 2, 75, 'analista', 'Brandon Suarez', 'Prueba de transferencia de datos', '2026-01-06 17:19:18'),
(7, 2, 75, 'analista', 'Brandon Suarez', '[Archivo adjunto]', '2026-01-06 17:19:23'),
(8, 2, 75, 'usuario', 'Hector Martinez', '[Archivo adjunto]', '2026-01-06 17:19:27'),
(9, 2, 75, 'analista', 'Brandon Suarez', 'ya se reiniciaron catalogos', '2026-01-06 17:19:42'),
(10, 4, 79, 'usuario', 'Hector Martinez', 'hola', '2026-01-07 08:40:10'),
(11, 4, 79, 'usuario', 'Hector Martinez', 'no puedo, me marcan en 0', '2026-01-07 08:40:16'),
(12, 4, 79, 'analista', 'Brandon Suarez', 'ah okay, eso es tema de SAP, sobre esta misma conversacion, se va a canalizar para alla', '2026-01-07 08:40:39'),
(13, 4, 79, 'usuario', 'Hector Martinez', 'perfecto, gracias', '2026-01-07 08:40:45'),
(14, 4, 79, 'analista', 'Brandon Suarez', 'buen dia', '2026-01-07 08:40:55'),
(15, 5, 80, 'usuario', 'Hector Martinez', 'prueba de canalizacion', '2026-01-07 09:03:18'),
(16, 6, 81, 'analista', 'Brandon Suarez', 'conversemos un poquito', '2026-01-07 11:42:23'),
(17, 6, 81, 'usuario', 'Hector Martinez', 'claor', '2026-01-07 11:42:28'),
(18, 6, 81, 'analista', 'Brandon Suarez', 'prueba 2 intercambio', '2026-01-07 11:42:37'),
(19, 6, 81, 'usuario', 'Hector Martinez', 'okay vamos a SAP', '2026-01-07 11:42:46'),
(20, 6, 81, 'usuario', 'Hector Martinez', '[Archivo adjunto]', '2026-01-07 11:42:50'),
(21, 6, 81, 'analista', 'Brandon Suarez', 'hola', '2026-01-07 11:42:58'),
(22, 7, 82, 'analista', 'Brandon Suarez', 'hola', '2026-01-07 12:02:35'),
(23, 7, 82, 'usuario', 'Hector Martinez', 'hola', '2026-01-07 12:02:41'),
(24, 7, 82, 'analista', 'Brandon Suarez', 'intentemos', '2026-01-07 12:06:23'),
(25, 7, 82, 'usuario', 'Hector Martinez', 'va', '2026-01-07 12:06:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `number_sap` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `rol` int(11) NOT NULL,
  `area` varchar(50) NOT NULL,
  `register` timestamp NOT NULL DEFAULT current_timestamp(),
  `celular` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `number_sap`, `name`, `last_name`, `email`, `password`, `must_change_password`, `rol`, `area`, `register`, `celular`) VALUES
(1, 30660, 'Israel', 'Rico', 'gerente-ti@eqf.mx', '$2y$10$PX/13X/IIxwaCVOo9AIzlePU3rDju3XqmifumRL7.I5YwSlU89ErO', 0, 2, 'TI', '2025-11-25 16:21:23', '5543375604'),
(2, 30378, 'Brandon', 'Suarez', 'ti6@eqf.mx', '$2y$10$eq/C6Gdd/5DNdZpIE/1bouo0sO6bazf8ONG7dPGJ6mOkeYPScaCxu', 0, 3, 'TI', '2025-11-25 16:21:37', ''),
(3, 30376, 'Paola', 'Martinez', 'ti2@eqf.mx', '$2y$10$RrAuMw.kVE4PJu1BevGnWuFzwFMI0GtvMyvAb0oqG93cbS3WtXDU.', 1, 3, 'TI', '2025-11-25 16:21:56', '5580749966'),
(4, 26551, 'Dafne', 'Lailson', 'ti5@eqf.mx', '$2y$10$cg71.K/575ixfz/VUL4S3u6OG3.tn9t6RKXpOpQ4j2JkuPYNXaIU2', 0, 3, 'TI', '2025-11-25 16:22:09', '5579192672'),
(5, 22983, 'David', 'Nava', 'ti4@eqf.mx', '$2y$10$4A8GlRij1SYisD93JCfhH.j09bFGXub4kybFF4JxjXvDIB8ggT0da', 1, 3, 'TI', '2025-11-25 16:24:17', '56411819904'),
(6, 27889, 'Belen', 'Pineda', 'ti3@eqf.mx', '$2y$10$jXn.COL.WPT2hmdKxAkMn.204ZYW7EoXlUWQnim07KpfVbTKzsQNu', 1, 3, 'TI', '2025-11-25 16:25:53', '5544662216'),
(7, 28822, 'Aldo', 'Mota', 'ti@eqf.mx', '$2y$10$YmMsYfIGt1RWorQ/qfobCeMxJl9i65dw4JjNdlQ76NxQ1NxCmLY6W', 1, 3, 'TI', '2025-11-25 16:26:30', '5579606359'),
(8, 21643, 'Darla', 'Sanchez', 'administracion@eqf.mx', '$2y$10$1K4Et.oXyg1pIpxh/K19c.FAco1gazMydzJsjU.p9tHgeoLNs9jyO', 0, 2, 'SAP', '2025-11-25 16:29:13', '5528992482'),
(9, 13783, 'Jesús', 'Luna', 'administracion1@eqf.mx', '$2y$10$HaIvlqum0QbmWd4mMXjMku.N10CScVXPcUI1RcxodbKo3RdlJyUf6', 1, 3, 'SAP', '2025-11-25 16:30:21', '5580712109'),
(10, 28577, 'David', 'Zenteno', 'administracion2@eqf.mx', '$2y$10$BOHmzZjsL22KOQGefvFRzexi3KSlYRuTuRHEghKEZuMpoeAGtOVxe', 1, 3, 'SAP', '2025-11-25 16:31:10', '5580789737'),
(11, 29366, 'Aidee', 'Jimenez', 'administracion3@eqf.mx', '$2y$10$ZS5A.N3oZGGZjVd1jWa9f.YuZMdwqYWFRqpmHfH2CY9ShlxVA7eM6', 0, 3, 'SAP', '2025-11-25 16:31:56', '5580749950'),
(12, 26541, 'Miriam', 'Cardenas', 'gerente.mercadotecnia@eqf.mx', '$2y$10$SXQhyrBDmvgbcV5k/PuLXeNJbzIFKG46nojbmos0q8UfhD2yw4c9K', 1, 2, 'MKT', '2025-11-25 16:33:55', '0'),
(13, 21338, 'Marisol', 'Rodriguez', 'mkt@eqf.mx', '$2y$10$1O/Yu.Rz0mTMwru.lFYj3OfYU7xcU2Juze/VFq70nTSP7WZZke.Fa', 1, 3, 'MKT', '2025-11-25 16:35:54', '0'),
(14, 30073, 'Nahomi', 'Demetrio', 'diseno@eqf.mx', '$2y$10$YtHKtztHaKCp5di0yMyzzuY/7yQT6C1vlBmo3qP23OhlmEnW9pawy', 1, 3, 'MKT', '2025-11-25 16:38:02', '0'),
(15, 26285, 'Oscar', 'Colin', 'diseno1@eqf.mx', '$2y$10$Iwn4ifHQqnzD3c4EBwxtPe.bHv9Shl4um.U0WeEXlc5IThA8jwN.e', 1, 3, 'MKT', '2025-11-25 16:38:47', '0'),
(16, 25701, 'Vianey', 'Victoria', 'mkt4@eqf.mx', '$2y$10$Ujj.7hncVxeN3S64Y1IgoOGDOb0rlU/HOZ64Ekr6F4Huv7t5/3u2q', 1, 3, 'MKT', '2025-11-25 16:41:03', '0'),
(17, 13821, 'Rosa', 'Cartagena', 'mkt5@eqf.mx', '$2y$10$.RsA7umzbIq1PJiDP2Ml3egKQpLnOl1gqEwCHOJ/i8TItQwlnUx5G', 1, 3, 'MKT', '2025-11-25 16:42:00', '0'),
(18, 11111, 'Arturo', 'Huizar', 'diseno2@eqf.mx', '$2y$10$Pt3kZ2KU16s0bTdpw2nVEuBjMGU9HuYG/QbFHCRNVAwlWn40HtRQy', 1, 3, 'MKT', '2025-11-25 16:43:00', '0'),
(19, 11111, 'Sergio', 'Gonzalez', 'mkt3@eqf.mx', '$2y$10$BQUprwSLzvJVl0XYWFtOEevjPZku0HUIyFeIVAPCPwpuw/0bIQ5J2', 1, 3, 'MKT', '2025-11-25 16:43:20', '0'),
(23, 10121, 'Olegario', 'Alonso', 'dir.general@eqf.mx', '$2y$10$viATif0d2FXu/Lm16Db2ieJS7Wb5Zylp2l1DH9DcaDlA4D6AWb9OK', 1, 4, 'Corporativo', '2025-11-26 15:49:22', '0'),
(24, 25891, 'Erika', 'Montes de Oca', 'asistente.dir@eqf.mx', '$2y$10$.QCIwC/hbdPMRx14jPDa9uFWTvqvf0316FvZMwIOw4Mjyjjk8BVhC', 1, 4, 'Corporativo', '2025-11-26 15:51:37', '0'),
(25, 11111, 'Alfonso', 'Garcia', 'dir.comercial@eqf.mx', '$2y$10$U3toClzTIJMfWWPVLu.o7uab1WGprpLSTCSh467i6J4dCw0CmO3au', 1, 4, 'Corporativo', '2025-11-26 15:52:37', '0'),
(26, 27893, 'Anabell', 'Mendoza', 'asistente.dircomercial@eqf.mx', '$2y$10$UW.Rab7bv7XyRrkzSoz71Ono1NpNjD65ZLJig4Z86s3j0eHOPmhuC', 1, 4, 'Corporativo', '2025-11-26 15:54:21', '0'),
(27, 13740, 'Erick', 'Ham', 'gerente.logistica@eqf.mx', '$2y$10$cFmyufJG4hCiJWTPSqB7lOrvGxrgb7tRLpaLQanDD/Zw1grwWQ5la', 1, 4, 'Corporativo', '2025-11-26 15:55:03', '0'),
(28, 24645, 'Manuel', 'Velazquez', 'coordinador.logistica@eqf.mx', '$2y$10$lNPBg.SxN9gj6XF5Wt1nDuCe2hZuozeHo1xNziAsIDYjB.tzziOy2', 1, 4, 'Corporativo', '2025-11-26 15:55:41', '0'),
(29, 24269, 'Claudia', 'Chavez', 'insumos@eqf.mx', '$2y$10$hI6JDfZq854QxkwVaemTeetJiRDohNsxq6R0gPBX7awbm9oa9afqy', 1, 4, 'Corporativo', '2025-11-26 15:56:32', '0'),
(30, 11111, 'Omar', 'Vazquez', 'auxinsumos@eqf.mx', '$2y$10$D7/SjbjcAenJophB0qD7ueYRgTCgesYSnDpBrnjNkOyF0oFcsK76O', 1, 4, 'Corporativo', '2025-11-26 16:04:45', '0'),
(31, 10682, 'Guadalupe', 'Sosa', 'gerete.juridico@eqf.mx', '$2y$10$BqA6pt0WFRZNswVy4m8PBO2yqKdaP3L1XRoR0.if2TfToelupRrU2', 1, 4, 'Corporativo', '2025-11-26 16:05:47', '0'),
(32, 25614, 'Cesar', 'Espitia', 'juridico1@eqf.mx', '$2y$10$zKzFnBiNOEX7KT2Rt7eRduYmWff6ffAZua3WH5LqA/QIjqI29qohy', 1, 4, 'Corporativo', '2025-11-26 16:06:28', '0'),
(33, 30533, 'Andresito', 'Lindo', 'juridico2@eqf.mx', '$2y$10$/y7EIVXyLh1osZ96EGvNDOT09YvQQP9cWZ/cRpA5kv4Ytg1X4mr3.', 0, 4, 'Corporativo', '2025-11-26 16:07:59', '0'),
(34, 22268, 'Saradry', 'Perez', 'capacitacion@eqf.mx', '$2y$10$iWvqk6TV6.OhgwO.egeBM.AQ7Li5MY6TNxAp/LfqcrjjnJBvPIXoe', 1, 4, 'Corporativo', '2025-11-26 16:14:13', '0'),
(35, 11111, 'Veronica', 'Monroy', 'gerente.rrhh@eqf.mx', '$2y$10$NePXHSIYRffuVITBtHG95.l11ZbkZtmL05f5Q.QHlL33QHoYcMVf6', 1, 4, 'Corporativo', '2025-11-26 16:18:24', '0'),
(36, 24722, 'Mariana', 'Montoya', 'capacitacion3@eqf.mx', '$2y$10$Yhv1flBKWfif5LQfkrDUzONZfoonQFfusskhy8jPRXPWZQtZxJ.FO', 1, 4, 'Corporativo', '2025-11-26 16:19:04', '0'),
(37, 29946, 'Viviana', 'Morales', 'capacitacion2@eqf.mx', '$2y$10$aVq7SJRWL3CIVbN8NH02seFu/mMInmRypicFwCc/3xwufYXSfUyKO', 1, 4, 'Corporativo', '2025-11-26 16:19:42', '0'),
(38, 24030, 'Zayra', 'Licea', 'rrhh@eqf.mx', '$2y$10$jTsxAW2qNCMCBqTSDy6Q2u0R.NI1smjIxNbOtuEVJKSuheZNkFaji', 1, 4, 'Corporativo', '2025-11-26 16:20:24', '0'),
(39, 22144, 'Leonardo', 'Dominguez', 'rrhh1@eqf.mx', '$2y$10$2Wx4NydBP9.YZ/pLpU8mOOJrhXf3aL5ib8f4TVdlEcBQEgu68i9lW', 1, 4, 'Corporativo', '2025-11-26 16:21:00', '0'),
(40, 14839, 'Dalila', 'Cornejo', 'rrhh2@eqf.mx', '$2y$10$lPp8OuL4E1Eh8gTSRA/5rOgc8dyOB.9u5GlbvmE1uB/xVEYjZbLAS', 1, 4, 'Corporativo', '2025-11-26 16:32:22', '0'),
(41, 28062, 'Luis', 'Sanchez', 'rrhh5@eqf.mx', '$2y$10$AHW6wrVzWMyUSIrD6uR69eFL924kEgaGwsRE0.AOsc8RimjBI2xje', 1, 4, 'Corporativo', '2025-11-26 16:33:03', '0'),
(42, 11111, 'Xiomami', 'Vazquez', 'rrhh4@eqf.mx', '$2y$10$k0StTSWWsv.jZ0dGirzMs.SfSt8ojetVtKP.maIDsNKkB/fEQfrlW', 1, 4, 'Corporativo', '2025-11-26 16:36:17', '0'),
(43, 27888, 'Yoshelin', 'Mejia', 'aux.rh@eqf.mx', '$2y$10$k214PiP1wv9Kj4UUruTmc.dzRw4n5L4iN/EPjvrPOm5ZEvu8iSc1W', 1, 4, 'Corporativo', '2025-11-26 16:36:58', '0'),
(44, 11111, 'Adrian', 'Sanchez', 'mensajero@eqf.mx', '$2y$10$j/EmjLr2oyAS545601Q8TuJkHbgcffmsXidIkEhkpTYXO0CBntdpK', 1, 4, 'Corporativo', '2025-11-26 16:42:14', '0'),
(45, 27587, 'Martin', 'Fernandez', 'controldegestion@eqf.mx', '$2y$10$AR01uSdcgbGv.l0eSYn93u1O77DTsrBb3uVleFu3ISgY7FmQe4uia', 1, 4, 'Corporativo', '2025-11-26 16:44:07', '0'),
(46, 26282, 'Maria', 'Sanchez', 'naucalpan@eqf.mx', '$2y$10$hk5mOauFUzfrDYHXd0wmtO3lOz9kGTeN4Cjvf8gEA0cOcpYbe9Axa', 1, 4, 'Sucursal', '2025-11-26 16:58:31', '0'),
(48, 11104, 'Hector', 'Martinez', 'cuautitlan@eqf.mx', '$2y$10$7cWfpQoPA06cuat6CpNpWuBxZ3xa1Ut9STr5Yx9ZuGeMX2i1uRbvu', 0, 4, 'Sucursal', '2025-11-26 23:31:31', '0'),
(50, 10248, 'Lucero', 'José', 'gerente.contabilidad@eqf.mx', '$2y$10$SpNsFyvAYFQbtHPpKl4Tz.A61z56Eaw2RqrCwBxUPOXrxtspp72sS', 1, 4, 'Corporativo', '2025-11-28 23:25:38', '0'),
(51, 11111, 'S', 'A', 'soporte@eqf.mx', '$2y$10$1iDcpxnVHjNiSuoQE4dYMewmk7j1u6X9v6gpfNEFvo0MJveyM8GYa', 0, 1, 'Corporativo', '2025-12-09 23:29:42', '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `analyst_schedules`
--
ALTER TABLE `analyst_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `analyst_status_overrides`
--
ALTER TABLE `analyst_status_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD KEY `idx_until` (`until_at`);

--
-- Indices de la tabla `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_active` (`is_active`,`target_area`,`starts_at`,`ends_at`),
  ADD KEY `idx_ann_created_area` (`created_by_area`),
  ADD KEY `idx_ann_created_user` (`created_by_user_id`);

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `idx_actor` (`actor_user_id`);

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `idx_actor` (`actor_user_id`);

--
-- Indices de la tabla `catalog_absence_reasons`
--
ALTER TABLE `catalog_absence_reasons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `catalog_areas`
--
ALTER TABLE `catalog_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `catalog_priorities`
--
ALTER TABLE `catalog_priorities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `catalog_problems`
--
ALTER TABLE `catalog_problems`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_area_label` (`area_code`,`label`);

--
-- Indices de la tabla `catalog_sat_patterns`
--
ALTER TABLE `catalog_sat_patterns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `catalog_shifts`
--
ALTER TABLE `catalog_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `catalog_status`
--
ALTER TABLE `catalog_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visibility` (`visibility`),
  ADD KEY `created_at` (`created_at`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_ide` (`user_ide`),
  ADD KEY `is_read` (`is_read`);

--
-- Indices de la tabla `password_recovery_requests`
--
ALTER TABLE `password_recovery_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_email` (`requester_email`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indices de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_endpoint` (`user_id`,`endpoint`(255));

--
-- Indices de la tabla `staff_notifications`
--
ALTER TABLE `staff_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_leido` (`user_id`,`leido`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assigned` (`assigned_to_user_id`,`status`),
  ADD KEY `idx_created` (`created_by_admin_id`,`status`),
  ADD KEY `idx_due` (`due_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `task_events`
--
ALTER TABLE `task_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_actor` (`actor_user_id`);

--
-- Indices de la tabla `task_files`
--
ALTER TABLE `task_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task` (`task_id`,`file_type`,`is_deleted`),
  ADD KEY `idx_task_assignee` (`task_assignee_id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ticket_assignments_log`
--
ALTER TABLE `ticket_assignments_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_to` (`to_analyst_id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Indices de la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ticket_adj` (`ticket_id`);

--
-- Indices de la tabla `ticket_context`
--
ALTER TABLE `ticket_context`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indices de la tabla `ticket_feedback`
--
ALTER TABLE `ticket_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_id` (`ticket_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ticket_id_2` (`ticket_id`);

--
-- Indices de la tabla `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_user` (`sender_id`),
  ADD KEY `idx_ticket_messages_ticket_internal` (`ticket_id`,`is_internal`,`id`);

--
-- Indices de la tabla `ticket_message_files`
--
ALTER TABLE `ticket_message_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_file_message` (`message_id`),
  ADD KEY `fk_msg_file_ticket` (`ticket_id`);

--
-- Indices de la tabla `ticket_notifications`
--
ALTER TABLE `ticket_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_ticket` (`ticket_id`),
  ADD KEY `fk_notif_user` (`user_id`);

--
-- Indices de la tabla `ticket_reads`
--
ALTER TABLE `ticket_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ticket_user` (`ticket_id`,`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `ticket_transfers`
--
ALTER TABLE `ticket_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `from_area` (`from_area`),
  ADD KEY `to_area` (`to_area`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indices de la tabla `ticket_transfer_files`
--
ALTER TABLE `ticket_transfer_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indices de la tabla `ticket_transfer_messages`
--
ALTER TABLE `ticket_transfer_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `analyst_schedules`
--
ALTER TABLE `analyst_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `analyst_status_overrides`
--
ALTER TABLE `analyst_status_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=612;

--
-- AUTO_INCREMENT de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalog_absence_reasons`
--
ALTER TABLE `catalog_absence_reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `catalog_areas`
--
ALTER TABLE `catalog_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `catalog_priorities`
--
ALTER TABLE `catalog_priorities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `catalog_problems`
--
ALTER TABLE `catalog_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `catalog_sat_patterns`
--
ALTER TABLE `catalog_sat_patterns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `catalog_shifts`
--
ALTER TABLE `catalog_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `catalog_status`
--
ALTER TABLE `catalog_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `password_recovery_requests`
--
ALTER TABLE `password_recovery_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `staff_notifications`
--
ALTER TABLE `staff_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `task_events`
--
ALTER TABLE `task_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `task_files`
--
ALTER TABLE `task_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de la tabla `ticket_assignments_log`
--
ALTER TABLE `ticket_assignments_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ticket_context`
--
ALTER TABLE `ticket_context`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_feedback`
--
ALTER TABLE `ticket_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT de la tabla `ticket_message_files`
--
ALTER TABLE `ticket_message_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `ticket_notifications`
--
ALTER TABLE `ticket_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT de la tabla `ticket_reads`
--
ALTER TABLE `ticket_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT de la tabla `ticket_transfers`
--
ALTER TABLE `ticket_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `ticket_transfer_files`
--
ALTER TABLE `ticket_transfer_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_transfer_messages`
--
ALTER TABLE `ticket_transfer_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `task_events`
--
ALTER TABLE `task_events`
  ADD CONSTRAINT `fk_task_events_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `task_files`
--
ALTER TABLE `task_files`
  ADD CONSTRAINT `fk_task_files_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `fk_ticket_adj` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `fk_msg_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `ticket_message_files`
--
ALTER TABLE `ticket_message_files`
  ADD CONSTRAINT `fk_msg_file_message` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`),
  ADD CONSTRAINT `fk_msg_file_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`);

--
-- Filtros para la tabla `ticket_notifications`
--
ALTER TABLE `ticket_notifications`
  ADD CONSTRAINT `fk_notif_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `ticket_transfer_files`
--
ALTER TABLE `ticket_transfer_files`
  ADD CONSTRAINT `fk_ttf_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `ticket_transfers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_transfer_messages`
--
ALTER TABLE `ticket_transfer_messages`
  ADD CONSTRAINT `fk_ttm_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `ticket_transfers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
