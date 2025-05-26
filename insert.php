<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sensor = $_POST['sensor'] ?? '';
    $value = $_POST['value'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $location = $_POST['location'] ?? '';
    $desc = $_POST['description'] ?? '';

    if ($sensor && $value && $unit) {
        $stmt = $pdo->prepare("INSERT INTO messdaten (sensor_name, messwert, einheit, standort, beschreibung, erstellt_von) VALUES (?, ?, ?, ?, ?, 'Tasmota')");
        $stmt->execute([$sensor, $value, $unit, $location, $desc]);
        echo "OK";
    } else {
        http_response_code(400);
        echo "Fehlende Parameter";
    }
}
