# 📊 Messdatenerfassungssystem mit Tasmota-Import

Ein leichtgewichtiges PHP-Projekt zur Erfassung, Anzeige und Verwaltung von Umweltdaten – inklusive Benutzerverwaltung, CSV/JSON-Export und Import von Tasmota-Daten (z. B. BME280).

##  Funktionen

- CSV-Import und Export
- Tasmota-JSON-Datenimport (BME280-kompatibel)
- Daten filtern, bearbeiten, löschen
- Benutzerregistrierung und Login
- Speichern in MySQL-Datenbank
- Datenbank-Setup automatisch bei Aufruf von `index.php`
- Responsive Oberfläche

##  Installation

1. Projekt in den `htdocs`-Ordner legen (z. B. `C:\xampp\htdocs\messdaten`)
2. Stelle sicher, dass **Apache** und **MySQL** in XAMPP laufen
3. Rufe im Browser `http://localhost/messdaten/index.php` auf
4. Die Datenbank und Tabellen werden automatisch erstellt

##  Datenimport aus Tasmota

1. Tasmota-Gerät konfigurieren und Sensor (z. B. BME280) anschließen
2. ![image](https://github.com/user-attachments/assets/f31a083e-460b-4929-bda2-1ef3b3fb573c)
3. Rufe die Weboberfläche deines Geräts auf
4. Öffne die **Konsole** und gebe `Status 8` ein
5. Kopiere die JSON-Ausgabe und füge sie im Webtool ein
6. Nach Klick auf „Importieren“ werden die Werte übernommen

##  Datenexport

- Export als CSV oder JSON möglich
- Exportfilter für Sensorname, Zeitraum oder Standort
- Dateiname enthält automatisch Datum und Uhrzeit

##  Benutzerverwaltung

- Nutzer können sich registrieren, einloggen und Messdaten zuordnen
- Bearbeiten und Löschen nur für eigene Daten möglich

##  Technologien

- **PHP 8+**
- **MySQL / MariaDB**
- **HTML/CSS**
- **Tasmota JSON**
- Optional: Arduino (ESP32 mit BME280)

## ⚠ Hinweise

- Die Daten werden **nicht live aktualisiert**
- Importierte Tasmota-Werte erscheinen nach dem Speichern
- Nur Daten seit dem Start der Anwendung werden dauerhaft gespeichert

---

© 2025 – Alexander Schleh / Ausbildung / Projektarbeit
