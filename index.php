<?php
/**
 * Messdatenerfassung System (vollständig kommentiert nach Coding Guidelines)
 *
 * Funktionen:
 * - Benutzerverwaltung (Registrierung, Login, Löschung)
 * - Import und Export von Sensordaten (CSV, JSON)
 * - Tasmota-Datenimport (ESP32)
 * - Datenanzeige und -filterung
 * - UI über einfache Tabs
 *
 * Hinweis: Diese Datei enthält sowohl Backend-Logik als auch HTML.
 */

session_start();

/**
 * Klasse Database
 * Stellt die Datenbankverbindung her und initialisiert Tabellen.
 */
class Database {
    private $host = 'localhost';
    private $dbname = 'messdaten_db';
    private $username = 'root';
    private $password = '';
    private $pdo;


    /**
     * Konstruktor – Baut Verbindung zur Datenbank auf.
     */
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Gibt die aktuelle PDO-Verbindung zurück.
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Erstellt die notwendigen Tabellen, falls nicht vorhanden.
     */
    public function setupDatabase() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS messdaten (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sensor_name VARCHAR(100) NOT NULL,
                messwert DECIMAL(10,4) NOT NULL,
                einheit VARCHAR(20) NOT NULL,
                zeitstempel TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                standort VARCHAR(100),
                beschreibung TEXT,
                erstellt_von VARCHAR(50),
                INDEX idx_sensor (sensor_name),
                INDEX idx_zeitstempel (zeitstempel)
            )"
        ];

        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
    }
}

/**
 * Klasse MessdatenManager
 * Verwaltet das Einfügen, Abrufen und Exportieren von Sensordaten.
 */
class MessdatenManager {
    private $db;

    /**
     * Konstruktor
     *
     * @param Database $database
     */
    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /**
     * Importiert Messdaten aus einer CSV-Datei.
     *
     * @param string $csvFile Pfad zur CSV-Datei
     * @return array Anzahl importierter Zeilen und Fehlerliste
     */
    public function importFromCSV($csvFile) {
        $imported = 0;
        $errors = [];

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            // Header überspringen
            $header = fgetcsv($handle, 1000, ";");

            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (count($data) >= 4) {
                    try {
                        $stmt = $this->db->prepare(
                            "INSERT INTO messdaten (sensor_name, messwert, einheit, standort, beschreibung, erstellt_von) 
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->execute([
                            $data[0], // sensor_name
                            floatval($data[1]), // messwert
                            $data[2], // einheit
                            $data[3] ?? '', // standort
                            $data[4] ?? '', // beschreibung
                            $_SESSION['username'] ?? 'System'
                        ]);
                        $imported++;
                    } catch (Exception $e) {
                        $errors[] = "Zeile " . ($imported + 1) . ": " . $e->getMessage();
                    }
                }
            }
            fclose($handle);
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Holt alle Messdaten mit optionalen Filtern.
     *
     * @param array $filters Filterparameter
     * @return array Liste der Messdaten
     */
    public function getMessdaten($filters = []) {
        $sql = "SELECT * FROM messdaten WHERE 1=1";
        $params = [];

        if (!empty($filters['sensor_name'])) {
            $sql .= " AND sensor_name LIKE ?";
            $params[] = '%' . $filters['sensor_name'] . '%';
        }

        if (!empty($filters['datum_von'])) {
            $sql .= " AND DATE(zeitstempel) >= ?";
            $params[] = $filters['datum_von'];
        }

        if (!empty($filters['datum_bis'])) {
            $sql .= " AND DATE(zeitstempel) <= ?";
            $params[] = $filters['datum_bis'];
        }

        if (!empty($filters['standort'])) {
            $sql .= " AND standort LIKE ?";
            $params[] = '%' . $filters['standort'] . '%';
        }

        $sql .= " ORDER BY zeitstempel DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exportiert Daten als CSV mit optionalem Filter.
     * Sendet die Datei direkt als Download.
     */
    public function exportToCSV($filters = []) {
        $data = $this->getMessdaten($filters);

        $filename = 'messdaten_export_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Header schreiben
        fputcsv($output, [
            'ID', 'Sensor Name', 'Messwert', 'Einheit',
            'Zeitstempel', 'Standort', 'Beschreibung', 'Erstellt von'
        ], ';');

        // Daten schreiben
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit();
    }

    /**
     * Exportiert Daten als JSON mit optionalem Filter.
     * Sendet die Datei direkt als Download.
     */
    public function exportToJSON($filters = []) {
        $data = $this->getMessdaten($filters);

        $filename = 'messdaten_export_' . date('Y-m-d_H-i-s') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Löscht einen bestimmten Datensatz nach ID.
     *
     * @param int $id Datensatz-ID
     * @return bool Erfolgreich gelöscht?
     */
    public function deleteMessdaten($id) {
        $stmt = $this->db->prepare("DELETE FROM messdaten WHERE id = ?");
        return $stmt->execute([$id]);
    }
}



/**
 * Klasse UserManager
 * Verwaltung von Benutzern inkl. Authentifizierung.
 */
class UserManager {
    private $db;

    /**
     * Konstruktor
     *
     * @param Database $database
     */
    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /**
     * Registriert einen neuen Benutzer.
     *
     * @param string $username
     * @param string $password
     * @return bool Erfolg
     */
    public function registerUser($username, $password) {
        try {
            $passwordHash = hash('sha256', $password);
            $stmt = $this->db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            return $stmt->execute([$username, $passwordHash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Benutzer anmelden
    public function loginUser($username, $password) {
        $passwordHash = hash('sha256', $password);
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND password_hash = ?");
        $stmt->execute([$username, $passwordHash]);

        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }

    // Benutzer löschen
    public function deleteUser($username, $password) {
        $passwordHash = hash('sha256', $password);
        $stmt = $this->db->prepare("DELETE FROM users WHERE username = ? AND password_hash = ?");
        return $stmt->execute([$username, $passwordHash]);
    }

    // Alle Benutzer abrufen
    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, username, created_at FROM users ORDER BY username");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Initialisierung
$database = new Database();
$database->setupDatabase();
$messdatenManager = new MessdatenManager($database);
$userManager = new UserManager($database);

// Request Handling
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === Abruf von Tasmota-ESP-Daten ===
    if (isset($_POST['fetch_tasmota']) && isset($_SESSION['logged_in'])) {
        $ip = '10.124.127.189';
        $url = "http://$ip/cm?cmnd=STATUS%208";

        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data["StatusSNS"]["BME280"])) {
                $temp = $data["StatusSNS"]["BME280"]["Temperature"];
                $hum  = $data["StatusSNS"]["BME280"]["Humidity"];
                $press = $data["StatusSNS"]["BME280"]["Pressure"];
                $zeit = date('Y-m-d H:i:s');
                $von = $_SESSION['username'] ?? 'System';

                $stmt = $database->getConnection()->prepare("
                    INSERT INTO messdaten (sensor_name, messwert, einheit, standort, beschreibung, erstellt_von, zeitstempel)
                    VALUES 
                    ('Temperatur', ?, '°C', 'Tasmota', 'Importiert von ESP32', ?, ?),
                    ('Luftfeuchtigkeit', ?, '%', 'Tasmota', 'Importiert von ESP32', ?, ?),
                    ('Luftdruck', ?, 'hPa', 'Tasmota', 'Importiert von ESP32', ?, ?)
                ");
                $stmt->execute([$temp, $von, $zeit, $hum, $von, $zeit, $press, $von, $zeit]);

                $message = "✅ Tasmota-Daten importiert.";
                $messageType = 'success';
            } else {
                $message = "❌ Keine BME280-Daten gefunden.";
                $messageType = 'error';
            }
        } else {
            $message = "❌ Verbindung zum ESP32 fehlgeschlagen.";
            $messageType = 'error';
        }
    }

    // … alle anderen POST-Aktionen (CSV, Login etc.)

    // Messdaten bearbeiten
    if (isset($_POST['edit_messdaten']) && isset($_SESSION['logged_in'])) {
        $id = $_POST['messdaten_id'];
        $sensor = $_POST['sensor_name'];
        $wert = $_POST['messwert'];
        $einheit = $_POST['einheit'];
        $standort = $_POST['standort'];
        $beschreibung = $_POST['beschreibung'];

        $stmt = $database->getConnection()->prepare("
            UPDATE messdaten
            SET sensor_name = ?, messwert = ?, einheit = ?, standort = ?, beschreibung = ?
            WHERE id = ?
        ");
        $stmt->execute([$sensor, $wert, $einheit, $standort, $beschreibung, $id]);

        $message = "Messdaten erfolgreich aktualisiert.";
        $messageType = 'success';
    }

    // CSV Upload
    if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $result = $messdatenManager->importFromCSV($_FILES['csv_file']['tmp_name']);
            $message = "CSV Import: {$result['imported']} Datensätze importiert.";
            if (!empty($result['errors'])) {
                $message .= " Fehler: " . implode(', ', $result['errors']);
                $messageType = 'warning';
            } else {
                $messageType = 'success';
            }
        }
    }

    // Benutzer registrieren
    if (isset($_POST['register_user'])) {
        $username = trim($_POST['reg_username']);
        $password = $_POST['reg_password'];

        if ($userManager->registerUser($username, $password)) {
            $message = "Benutzer '$username' erfolgreich registriert.";
            $messageType = 'success';
        } else {
            $message = "Fehler beim Registrieren. Benutzername bereits vorhanden?";
            $messageType = 'error';
        }
    }

    // Benutzer anmelden
    if (isset($_POST['login_user'])) {
        $username = trim($_POST['login_username']);
        $password = $_POST['login_password'];

        if ($userManager->loginUser($username, $password)) {
            $message = "Erfolgreich angemeldet als '$username'.";
            $messageType = 'success';
        } else {
            $message = "Anmeldung fehlgeschlagen. Benutzername oder Passwort falsch.";
            $messageType = 'error';
        }
    }

    // Benutzer abmelden
    if (isset($_POST['logout'])) {
        session_destroy();
        session_start();
        $message = "Erfolgreich abgemeldet.";
        $messageType = 'info';
    }

    // Benutzer löschen
    if (isset($_POST['delete_user'])) {
        $username = trim($_POST['del_username']);
        $password = $_POST['del_password'];

        if ($userManager->deleteUser($username, $password)) {
            $message = "Benutzer '$username' erfolgreich gelöscht.";
            $messageType = 'success';
        } else {
            $message = "Fehler beim Löschen. Benutzername oder Passwort falsch.";
            $messageType = 'error';
        }
    }

    // Messdaten löschen
    if (isset($_POST['delete_messdaten']) && isset($_SESSION['logged_in'])) {
        $id = $_POST['messdaten_id'];
        if ($messdatenManager->deleteMessdaten($id)) {
            $message = "Messdaten erfolgreich gelöscht.";
            $messageType = 'success';
        } else {
            $message = "Fehler beim Löschen der Messdaten.";
            $messageType = 'error';
        }
    }
}

// Export Handling
if (isset($_GET['export'])) {
    $filters = [
        'sensor_name' => $_GET['filter_sensor'] ?? '',
        'datum_von' => $_GET['filter_datum_von'] ?? '',
        'datum_bis' => $_GET['filter_datum_bis'] ?? '',
        'standort' => $_GET['filter_standort'] ?? ''
    ];

    if ($_GET['export'] === 'csv') {
        $messdatenManager->exportToCSV($filters);
    } elseif ($_GET['export'] === 'json') {
        $messdatenManager->exportToJSON($filters);
    }
}

// Filter für Anzeige
$filters = [
    'sensor_name' => $_GET['filter_sensor'] ?? '',
    'datum_von' => $_GET['filter_datum_von'] ?? '',
    'datum_bis' => $_GET['filter_datum_bis'] ?? '',
    'standort' => $_GET['filter_standort'] ?? ''
];

$messdaten = $messdatenManager->getMessdaten($filters);
$users = $userManager->getAllUsers();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messdatenerfassung System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .tab.active {
            background: #3498db;
            color: white;
        }

        .tab:hover {
            background: #2980b9;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn.danger {
            background: #e74c3c;
        }

        .btn.danger:hover {
            background: #c0392b;
        }

        .btn.success {
            background: #27ae60;
        }

        .btn.success:hover {
            background: #229954;
        }

        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #34495e;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .user-status {
            background: #e8f5e8;
            color: #2d5a2d;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 15px;
            display: inline-block;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Messdatenerfassung System</h1>
        <p>Professionelle Verwaltung und Analyse von Sensordaten</p>
    </div>

    <div class="content">
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="user-status">
                ✅ Angemeldet als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistiken -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($messdaten); ?></div>
                <div class="stat-label">Gesamt Messdaten</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Registrierte Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($messdaten, 'sensor_name'))); ?></div>
                <div class="stat-label">Verschiedene Sensoren</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('messdaten')">📊 Messdaten</button>
            <button class="tab" onclick="showTab('import')">📤 Import</button>
            <button class="tab" onclick="showTab('benutzer')">👥 Benutzerverwaltung</button>
        </div>

        <!-- Messdaten Tab -->
        <div id="messdaten" class="tab-content active">
            <h2>Messdaten Übersicht</h2>

            <!-- Filter -->
            <form method="GET" class="filter-form">
                <h3>🔍 Filter</h3>
                <div class="filter-row">
                    <div class="form-group">
                        <label>Sensor Name:</label>
                        <input type="text" name="filter_sensor" value="<?php echo htmlspecialchars($filters['sensor_name']); ?>" placeholder="z.B. Temperatur">
                    </div>
                    <div class="form-group">
                        <label>Datum von:</label>
                        <input type="date" name="filter_datum_von" value="<?php echo htmlspecialchars($filters['datum_von']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Datum bis:</label>
                        <input type="date" name="filter_datum_bis" value="<?php echo htmlspecialchars($filters['datum_bis']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Standort:</label>
                        <input type="text" name="filter_standort" value="<?php echo htmlspecialchars($filters['standort']); ?>" placeholder="z.B. Labor">
                    </div>
                </div>
                <button type="submit" class="btn">Filter anwenden</button>
                <a href="?" class="btn" style="background: #95a5a6;">Filter zurücksetzen</a>
            </form>

            <!-- Export Buttons -->
            <div style="margin-bottom: 20px;">
                <h3>📥 Export</h3>
                <a href="?export=csv&<?php echo http_build_query($filters); ?>" class="btn success">CSV Export</a>
                <a href="?export=json&<?php echo http_build_query($filters); ?>" class="btn success">JSON Export</a>
            </div>

            <form method="POST" style="margin-bottom: 20px;">
                <button type="submit" name="fetch_tasmota" class="btn success">📡 Tasmota-Daten abrufen</button>
            </form>


            <!-- Tabelle -->
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sensor</th>
                        <th>Messwert</th>
                        <th>Einheit</th>
                        <th>Zeitstempel</th>
                        <th>Standort</th>
                        <th>Beschreibung</th>
                        <th>Erstellt von</th>
                        <?php if (isset($_SESSION['logged_in'])): ?>
                            <th>Aktionen</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($messdaten)): ?>
                        <tr>
                            <td colspan="<?php echo isset($_SESSION['logged_in']) ? '9' : '8'; ?>" style="text-align: center; padding: 40px; color: #666;">
                                Keine Messdaten gefunden. Laden Sie eine CSV-Datei hoch oder passen Sie die Filter an.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messdaten as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['sensor_name']) ?></td>
                                <td><?= number_format($row['messwert'], 2) ?></td>
                                <td><?= htmlspecialchars($row['einheit']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($row['zeitstempel'])) ?></td>
                                <td><?= htmlspecialchars($row['standort']) ?></td>
                                <td><?= htmlspecialchars($row['beschreibung']) ?></td>
                                <td><?= htmlspecialchars($row['erstellt_von']) ?></td>

                                <?php if (isset($_SESSION['logged_in'])): ?>
                                    <td>
                                        <?php if (isset($_SESSION['logged_in'])): ?>

                                            <!-- Bearbeiten-Button -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="messdaten_id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="sensor_name" value="<?= htmlspecialchars($row['sensor_name']); ?>">
                                                <input type="hidden" name="messwert" value="<?= $row['messwert']; ?>">
                                                <input type="hidden" name="einheit" value="<?= htmlspecialchars($row['einheit']); ?>">
                                                <input type="hidden" name="standort" value="<?= htmlspecialchars($row['standort']); ?>">
                                                <input type="hidden" name="beschreibung" value="<?= htmlspecialchars($row['beschreibung']); ?>">
                                                <button type="submit" name="edit_trigger" class="btn" style="padding:5px 10px; font-size:12px;">Bearbeiten</button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Löschen-Button (immer sichtbar bei Login) -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Messdaten wirklich löschen?');">
                                            <input type="hidden" name="messdaten_id" value="<?= $row['id']; ?>">
                                            <button type="submit" name="delete_messdaten" class="btn danger" style="padding:5px 10px; font-size:12px;">Löschen</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if (isset($_POST['edit_trigger'])): ?>
                    <div class="filter-form" style="margin-top: 40px;">
                        <h3>✏️ Messwert bearbeiten</h3>
                        <form method="POST">
                            <input type="hidden" name="messdaten_id" value="<?= htmlspecialchars($_POST['messdaten_id']) ?>">

                            <div class="form-group">
                                <label>Sensor Name:</label>
                                <input type="text" name="sensor_name" value="<?= htmlspecialchars($_POST['sensor_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Messwert:</label>
                                <input type="number" step="0.01" name="messwert" value="<?= htmlspecialchars($_POST['messwert']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Einheit:</label>
                                <input type="text" name="einheit" value="<?= htmlspecialchars($_POST['einheit']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Standort:</label>
                                <input type="text" name="standort" value="<?= htmlspecialchars($_POST['standort']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung:</label>
                                <input type="text" name="beschreibung" value="<?= htmlspecialchars($_POST['beschreibung']) ?>">
                            </div>

                            <button type="submit" name="edit_messdaten" class="btn success">Änderungen speichern</button>
                            <a href="index.php" class="btn danger">Abbrechen</a>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Import Tab -->
        <div id="import" class="tab-content">
            <h2>📤 CSV Import</h2>
            <p style="margin-bottom: 20px; color: #666;">
                CSV-Format: Sensor Name; Messwert; Einheit; Standort; Beschreibung<br>
                Beispiel: Temperatur;23.5;°C;Labor 1;Raumtemperatur
            </p>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>CSV-Datei auswählen:</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" name="upload_csv" class="btn">CSV hochladen</button>
            </form>
        </div>

        <!-- Benutzer Tab -->
        <div id="benutzer" class="tab-content">
            <h2>👥 Benutzerverwaltung</h2>

            <!-- Login/Logout -->
            <?php if (!isset($_SESSION['logged_in'])): ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h3>🔐 Anmelden</h3>
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; align-items: end;">
                            <div class="form-group">
                                <label>Benutzername:</label>
                                <input type="text" name="login_username" required>
                            </div>
                            <div class="form-group">
                                <label>Passwort:</label>
                                <input type="password" name="login_password" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="login_user" class="btn">Anmelden</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h3>✅ Angemeldet als: <?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="logout" class="btn danger">Abmelden</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Benutzer registrieren -->
            <h3>➕ Benutzer registrieren</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Benutzername:</label>
                    <input type="text" name="reg_username" required>
                </div>
                <div class="form-group">
                    <label>Passwort:</label>
                    <input type="password" name="reg_password" required>
                </div>
                <button type="submit" name="register_user" class="btn success">Registrieren</button>
            </form>

            <!-- Benutzer löschen -->
            <h3 style="margin-top: 30px;">❌ Benutzer löschen</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Benutzername:</label>
                    <input type="text" name="del_username" required>
                </div>
                <div class="form-group">
                    <label>Passwort:</label>
                    <input type="password" name="del_password" required>
                </div>
                <button type="submit" name="delete_user" class="btn danger">Löschen</button>
            </form>

            <!-- Alle Benutzer anzeigen -->
            <h3 style="margin-top: 30px;">📋 Registrierte Benutzer</h3>
            <ul style="list-style: none; padding-left: 0;">
                <?php foreach ($users as $user): ?>
                    <li style="margin-bottom: 5px;">
                        👤 <?php echo htmlspecialchars($user['username']); ?> – <small><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div> <!-- Ende Benutzerverwaltung -->

    </div> <!-- Ende content -->
</div> <!-- Ende container -->

<!-- Tab-Wechsel Script -->
<script>
    function showTab(tabId) {
        // Inhalte ausblenden
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        // Buttons zurücksetzen
        document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
        // Aktivieren
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>

</body>
</html>


