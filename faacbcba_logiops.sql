-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 30-12-2025 a las 15:03:39
-- Versión del servidor: 8.0.44-35
-- Versión de PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `faacbcba_logiops`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `client_id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tax_id` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `contact_name` varchar(120) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone` varchar(60) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `billing_address` text COLLATE utf8mb4_spanish_ci,
  `shipping_address` text COLLATE utf8mb4_spanish_ci,
  `notes` text COLLATE utf8mb4_spanish_ci,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_days` smallint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`client_id`, `name`, `tax_id`, `contact_name`, `email`, `phone`, `billing_address`, `shipping_address`, `notes`, `credit_limit`, `credit_days`, `created_at`) VALUES
(2, 'PPC Broadband', '', 'Maximiliano Perez', 'Sergio.Perez-Sandi@belden.com', '+52 81 1813 2447', 'PO Box 23000 Hickory, NC 28603, USA', 'N G Forwarding Inc	// 412 Enterprise St, Laredo, TX 78045 USA				', '', 50000.00, 30, '2025-11-24 16:36:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(60) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('issued','paid','overdue','cancelled') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_items`
--

CREATE TABLE `invoice_items` (
  `invoice_item_id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `providers`
--

CREATE TABLE `providers` (
  `provider_id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo` varchar(80) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `contact_name` varchar(120) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone` varchar(60) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_spanish_ci,
  `credit_days` tinyint NOT NULL DEFAULT '0',
  `credit_limit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `providers`
--

INSERT INTO `providers` (`provider_id`, `name`, `tipo`, `contact_name`, `email`, `phone`, `notes`, `credit_days`, `credit_limit`, `created_at`) VALUES
(2, 'av2', '', '', '', '', '', 0, 50.00, '2025-11-24 16:58:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipments`
--

CREATE TABLE `shipments` (
  `shipment_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `provider_id` int UNSIGNED DEFAULT NULL,
  `reference` varchar(120) COLLATE utf8mb4_spanish_ci NOT NULL,
  `status` enum('draft','booked','in_transit','delivered','cancelled') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'draft',
  `pickup_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `origin` text COLLATE utf8mb4_spanish_ci,
  `destination` text COLLATE utf8mb4_spanish_ci,
  `notes` text COLLATE utf8mb4_spanish_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_spanish_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_spanish_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Administrador', 'admin@example.com', '$2y$12$abflURQ6UF71v.92FEeIq.U.K.hOgvO4aMvaGix5X.cO0wlxIAl5K', 'admin', 1, '2025-11-23 10:35:03'),
(2, 'Administrador', 'admin@lites.com.mx', '$2y$10$csWZbhaXAOoGiOKCGbt/OOQN9TKjkzNm/51n8AL96FFHSa7D6l/aW', 'admin', 1, '2025-11-23 10:36:15');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `idx_clients_name` (`name`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `idx_invoices_client` (`client_id`),
  ADD KEY `idx_invoices_status` (`status`),
  ADD KEY `idx_invoices_issue_date` (`issue_date`);

--
-- Indices de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`invoice_item_id`),
  ADD KEY `idx_items_invoice` (`invoice_id`);

--
-- Indices de la tabla `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD KEY `idx_providers_name` (`name`);

--
-- Indices de la tabla `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`shipment_id`),
  ADD KEY `fk_shipments_provider` (`provider_id`),
  ADD KEY `idx_shipments_status` (`status`),
  ADD KEY `idx_shipments_client` (`client_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `invoice_item_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `providers`
--
ALTER TABLE `providers`
  MODIFY `provider_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `shipments`
--
ALTER TABLE `shipments`
  MODIFY `shipment_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shipments_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
