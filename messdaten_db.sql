-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 27. Mai 2025 um 20:46
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `messdaten_db`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messdaten`
--

CREATE TABLE `messdaten` (
  `id` int(11) NOT NULL,
  `sensor_name` varchar(100) NOT NULL,
  `messwert` decimal(10,4) NOT NULL,
  `einheit` varchar(20) NOT NULL,
  `zeitstempel` timestamp NOT NULL DEFAULT current_timestamp(),
  `standort` varchar(100) DEFAULT NULL,
  `beschreibung` text DEFAULT NULL,
  `erstellt_von` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `messdaten`
--

INSERT INTO `messdaten` (`id`, `sensor_name`, `messwert`, `einheit`, `zeitstempel`, `standort`, `beschreibung`, `erstellt_von`) VALUES
(4, 'Druck', 1013.2500, 'hPa', '2025-05-26 14:56:46', 'Außenbereich', 'Atmosphärischer Luftdruck', 'System'),
(5, 'CO2-Gehalt', 420.0000, 'ppm', '2025-05-26 14:56:46', 'Büro', 'Kohlendioxidkonzentration', 'System'),
(6, 'Temperatur', 24.1000, '°C', '2025-05-26 14:56:46', 'Büro', 'Klimaanlage Sollwert', 'System'),
(7, 'Lichtintensität', 450.0000, 'Lux', '2025-05-26 14:56:46', 'Labor 1', 'Arbeitsplatzbeleuchtung', 'System'),
(8, 'pH-Wert', 7.2000, 'pH', '2025-05-26 14:56:46', 'Wasserlabor', 'Trinkwasserqualität', 'System'),
(9, 'Temperatur', 19.5000, '°C', '2025-05-26 14:56:46', 'Kühlraum', 'Lagertemperatur', 'System'),
(10, 'Luftfeuchtigkeit', 45.8000, '%', '2025-05-26 14:56:46', 'Büro', 'Klimaanlage', 'System'),
(11, 'Schallpegel', 42.5000, 'dB', '2025-05-26 14:56:46', 'Büro', 'Hintergrundgeräusch', 'System'),
(12, 'Temperatur', 25.3000, '°C', '2025-05-26 14:56:46', 'Serverraum', 'IT-Infrastruktur', 'System'),
(13, 'Spannung', 230.2000, 'V', '2025-05-26 14:56:46', 'Technikraum', 'Netzspannung L1', 'System'),
(14, 'Strom', 15.7000, 'A', '2025-05-26 14:56:46', 'Technikraum', 'Hauptverbraucher', 'System'),
(15, 'Leistung', 3.6000, 'kW', '2025-05-26 14:56:46', 'Technikraum', 'Gesamtverbrauch', 'System'),
(16, 'Temperatur', 18.2000, '°C', '2025-05-26 14:56:46', 'Keller', 'Grundtemperatur', 'System'),
(17, 'Luftfeuchtigkeit', 78.5000, '%', '2025-05-26 14:56:46', 'Keller', 'Feuchtigkeit kritisch', 'System'),
(18, 'Windgeschwindigkeit', 12.3000, 'km/h', '2025-05-26 14:56:46', 'Wetterstation', 'Außenmessung', 'System'),
(19, 'Windrichtung', 245.0000, 'Grad', '2025-05-26 14:56:46', 'Wetterstation', 'SW-Wind', 'System'),
(20, 'Niederschlag', 2.5000, 'mm', '2025-05-26 14:56:46', 'Wetterstation', 'Regenmenge 1h', 'System'),
(21, 'Temperatur', 22.8000, '°C', '2025-05-26 14:56:46', 'Werkstatt', 'Arbeitsplatztemperatur', 'System'),
(22, 'Vibration', 0.1500, 'mm/s', '2025-05-26 14:56:46', 'Maschine A', 'Lagerzustand', 'System'),
(23, 'Drehzahl', 1450.0000, 'U/min', '2025-05-26 14:56:46', 'Maschine A', 'Motorgeschwindigkeit', 'System'),
(24, 'Temperatur', 85.2000, '°C', '2025-05-26 14:56:46', 'Maschine A', 'Lagertemperatur', 'System'),
(25, 'Öldruck', 4.2000, 'bar', '2025-05-26 14:56:46', 'Maschine A', 'Hydrauliksystem', 'System'),
(26, 'Durchfluss', 125.6000, 'l/min', '2025-05-26 14:56:46', 'Kühlsystem', 'Kühlwasserdurchfluss', 'System'),
(27, 'Temperatur', 35.8000, '°C', '2025-05-26 14:56:46', 'Kühlsystem', 'Vorlauftemperatur', 'System'),
(28, 'Temperatur', 28.4000, '°C', '2025-05-26 14:56:46', 'Kühlsystem', 'Rücklauftemperatur', 'System'),
(29, 'Sauerstoffgehalt', 20.8000, '%', '2025-05-26 14:56:46', 'Labor 3', 'Raumluftqualität', 'System'),
(30, 'Methangehalt', 1.2000, 'ppm', '2025-05-26 14:56:46', 'Labor 3', 'Gaswarnsystem', 'System'),
(31, 'Temperatur', 20.5000, '°C', '2025-05-26 14:56:46', 'Archiv', 'Dokumentenlagerung', 'System'),
(32, 'Luftfeuchtigkeit', 50.1000, '%', '2025-05-26 14:56:46', 'Archiv', 'Konservierungsbedingungen', 'System'),
(33, 'Helligkeit', 89.0000, 'Lux', '2025-05-26 14:56:46', 'Parkplatz', 'Außenbeleuchtung', 'System'),
(34, 'Temperatur', -2.3000, '°C', '2025-05-26 14:56:46', 'Tiefkühlraum', 'Lebensmittellagerung', 'System'),
(35, 'Bodenfeuchtigkeit', 32.5000, '%', '2025-05-26 14:56:46', 'Gewächshaus', 'Bewässerungssystem', 'System'),
(36, 'Temperatur', 26.7000, '°C', '2025-05-26 14:56:46', 'Gewächshaus', 'Wachstumsbedingungen', 'System'),
(37, 'Luftfeuchtigkeit', 85.3000, '%', '2025-05-26 14:56:46', 'Gewächshaus', 'Optimal für Pflanzen', 'System'),
(38, 'UV-Index', 3.2000, 'Index', '2025-05-26 14:56:46', 'Gewächshaus', 'Pflanzenschutz', 'System'),
(39, 'Wasserstand', 1.8500, 'm', '2025-05-26 14:56:46', 'Tank A', 'Füllstand Regenwasser', 'System'),
(40, 'Temperatur', 15.8000, '°C', '2025-05-26 14:56:46', 'Tank A', 'Wassertemperatur', 'System'),
(41, 'Chlorgehalt', 0.8000, 'mg/l', '2025-05-26 14:56:46', 'Schwimmbad', 'Wasseraufbereitung', 'System'),
(42, 'pH-Wert', 7.4000, 'pH', '2025-05-26 14:56:46', 'Schwimmbad', 'Wasserqualität', 'System'),
(43, 'Temperatur', 28.0000, '°C', '2025-05-26 14:56:46', 'Schwimmbad', 'Wassertemperatur', 'System'),
(44, 'Ozongehalt', 0.0500, 'mg/m³', '2025-05-26 14:56:46', 'Labor 4', 'Luftreinigung', 'System'),
(45, 'Partikelanzahl', 1250.0000, 'Partikel/m³', '2025-05-26 14:56:46', 'Reinraum', 'Luftqualität Klasse 1000', 'System'),
(46, 'Temperatur', 21.0000, '°C', '2025-05-26 14:56:46', 'Reinraum', 'Kontrollierte Bedingungen', 'System'),
(47, 'Luftfeuchtigkeit', 40.5000, '%', '2025-05-26 14:56:46', 'Reinraum', 'Spezifikation eingehalten', 'System'),
(48, 'Stromverbrauch', 856.2000, 'kWh', '2025-05-26 14:56:46', 'Gebäude', 'Tagesverbrauch', 'System'),
(49, 'Gasverbrauch', 142.8000, 'm³', '2025-05-26 14:56:46', 'Gebäude', 'Heizungsverbrauch', 'System'),
(50, 'Wasserverbrauch', 2850.0000, 'Liter', '2025-05-26 14:56:46', 'Gebäude', 'Tagesverbrauch', 'System'),
(51, 'Temperatur', 23.2000, '°C', '2025-05-26 14:56:46', 'Konferenzraum', 'Raumklima', 'System'),
(52, 'CO2-Gehalt', 580.0000, 'ppm', '2025-05-26 14:56:46', 'Konferenzraum', 'Belüftung erforderlich', 'System'),
(53, 'Schallpegel', 38.2000, 'dB', '2025-05-26 14:56:46', 'Konferenzraum', 'Ruhepegel', 'System'),
(54, 'Temperatur', 22.1000, '°C', '2025-05-26 14:56:46', 'Bibliothek', 'Lesebereich', 'System'),
(55, 'Luftfeuchtigkeit', 48.7000, '%', '2025-05-26 14:56:46', 'Bibliothek', 'Buchkonservierung', 'System');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(2, 'Christoph', '74f161114685c523a9519495a1a7e72907f18a251503744f2cd6a57ae8b3f805', '2025-05-26 14:57:27');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `messdaten`
--
ALTER TABLE `messdaten`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sensor` (`sensor_name`),
  ADD KEY `idx_zeitstempel` (`zeitstempel`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `messdaten`
--
ALTER TABLE `messdaten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
