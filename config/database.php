<?php
// Connexion PDO à la base de données OmnesEvent.
// À modifier si votre hébergeur utilise d'autres identifiants.

$host = 'localhost';
$dbname = 'omnesevent';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erreur de connexion à la base de données.');
}
