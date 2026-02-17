<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../config/database.php";

if (!isset($pdo)) {
    echo json_encode([
        "success" => false,
        "error" => "Conexão PDO não encontrada"
    ]);
    exit;
}

$type = $_GET['type'] ?? null;

if (!$type) {
    echo json_encode([
        "success" => false,
        "error" => "Tipo de ranking não informado"
    ]);
    exit;
}

/*
==================================================
1️⃣ TOP 10 MAIS BEM AVALIADAS
==================================================
*/
if ($type === "best-rated") {

    $sql = "
        SELECT 
            s.id,
            s.title,
            s.artist,
            ROUND(AVG(r.rating),1) AS average_rating,
            COUNT(r.id) AS total_reviews
        FROM songs s
        JOIN reviews r ON s.id = r.song_id
        GROUP BY s.id
        ORDER BY average_rating DESC, total_reviews DESC
        LIMIT 10
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "type" => "Top 10 mais bem avaliadas",
        "data" => $data
    ]);
    exit;
}

/*
==================================================
2️⃣ TOP 10 MÚSICAS MAIS OUVIDAS
==================================================
*/
if ($type === "most-played-songs") {

    $sql = "
        SELECT id, title, artist, play_count
        FROM songs
        ORDER BY play_count DESC
        LIMIT 10
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "type" => "Top 10 músicas mais ouvidas",
        "data" => $data
    ]);
    exit;
}

/*
==================================================
3️⃣ TOP 10 ÁLBUNS MAIS OUVIDOS
==================================================
*/
if ($type === "most-played-albums") {

    $sql = "
        SELECT album, SUM(play_count) AS total_plays
        FROM songs
        WHERE album IS NOT NULL
        GROUP BY album
        ORDER BY total_plays DESC
        LIMIT 10
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "type" => "Top 10 álbuns mais ouvidos",
        "data" => $data
    ]);
    exit;
}

/*
==================================================
4️⃣ TOP 10 CANTORES MAIS OUVIDOS
==================================================
*/
if ($type === "most-played-artists") {

    $sql = "
        SELECT artist, SUM(play_count) AS total_plays
        FROM songs
        GROUP BY artist
        ORDER BY total_plays DESC
        LIMIT 10
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "type" => "Top 10 cantores mais ouvidos",
        "data" => $data
    ]);
    exit;
}

echo json_encode([
    "success" => false,
    "error" => "Tipo de ranking inválido"
]);
