<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $song_id = $_GET['song_id'] ?? null;

    if (!$song_id) {
        echo json_encode(["success" => false, "error" => "song_id obrigatório"]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT reviews.rating, reviews.created_at, users.name
        FROM reviews
        JOIN users ON reviews.user_id = users.id
        WHERE song_id = ?
    ");

    $stmt->bind_param("i", $song_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $reviews = [];

    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    echo json_encode([
        "success" => true,
        "reviews" => $reviews
    ]);
    exit;
}


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once "../config/database.php";

// Pega JSON do body
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$musicbrainz_id = $data['musicbrainz_id'] ?? null;
$title = $data['title'] ?? null;
$artist = $data['artist'] ?? null;
$release_date = $data['release_date'] ?? null;
$rating = $data['rating'] ?? null;

// Validação básica
if (!$user_id || !$musicbrainz_id || !$title || !$artist || !$rating) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Dados incompletos"]);
    exit;
}

// ===========================
// 1️⃣ Verifica se música existe
// ===========================

$stmt = $conn->prepare("SELECT id FROM songs WHERE musicbrainz_id = ?");
$stmt->bind_param("s", $musicbrainz_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $song = $result->fetch_assoc();
    $song_id = $song['id'];
} else {

    // 2️⃣ Insere música nova
    $stmtInsert = $conn->prepare("
        INSERT INTO songs (musicbrainz_id, title, artist, release_date)
        VALUES (?, ?, ?, ?)
    ");
    $stmtInsert->bind_param("ssss", $musicbrainz_id, $title, $artist, $release_date);
    $stmtInsert->execute();

    $song_id = $stmtInsert->insert_id;
}

// ===========================
// 3️⃣ Salva avaliação
// ===========================

$stmtReview = $conn->prepare("
    INSERT INTO reviews (user_id, song_id, rating)
    VALUES (?, ?, ?)
");

$stmtReview->bind_param("iii", $user_id, $song_id, $rating);
$stmtReview->execute();

echo json_encode([
    "success" => true,
    "message" => "Avaliação salva com sucesso!"
]);
