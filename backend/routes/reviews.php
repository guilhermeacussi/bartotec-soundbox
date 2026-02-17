<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$method = $_SERVER['REQUEST_METHOD'];

/*
================================================
FUNÇÃO: VALIDAR RATING (0.5 até 5.0)
================================================
*/
function validarRating($rating) {
    $allowed = [0.5,1,1.5,2,2.5,3,3.5,4,4.5,5];
    return in_array($rating, $allowed);
}

/*
================================================
GET → LISTAR AVALIAÇÕES + MÉDIA
================================================
*/
if ($method === 'GET') {

    $song_id = $_GET['song_id'] ?? null;

    if (!$song_id) {
        echo json_encode(["success"=>false,"error"=>"song_id obrigatório"]);
        exit;
    }

    // Buscar reviews
    $stmt = $conn->prepare("
        SELECT reviews.rating, reviews.created_at, users.username
        FROM reviews
        JOIN users ON reviews.user_id = users.id
        WHERE song_id = ?
        ORDER BY reviews.created_at DESC
    ");

    $stmt->bind_param("i", $song_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    // Calcular média
    $stmtAvg = $conn->prepare("
        SELECT AVG(rating) as average, COUNT(*) as total
        FROM reviews
        WHERE song_id = ?
    ");
    $stmtAvg->bind_param("i", $song_id);
    $stmtAvg->execute();
    $avgResult = $stmtAvg->get_result()->fetch_assoc();

    $average = $avgResult['average'] ? round($avgResult['average'] * 2) / 2 : 0;

    echo json_encode([
        "success" => true,
        "average_rating" => $average,
        "total_reviews" => $avgResult['total'],
        "reviews" => $reviews
    ]);
    exit;
}

/*
================================================
POST → CRIAR AVALIAÇÃO
================================================
*/
if ($method === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    $user_id = $data['user_id'] ?? null;
    $musicbrainz_id = $data['musicbrainz_id'] ?? null;
    $title = $data['title'] ?? null;
    $artist = $data['artist'] ?? null;
    $release_date = $data['release_date'] ?? null;
    $rating = $data['rating'] ?? null;

    if (!$user_id || !$musicbrainz_id || !$title || !$artist || !$rating) {
        http_response_code(400);
        echo json_encode(["success"=>false,"error"=>"Dados incompletos"]);
        exit;
    }

    if (!validarRating($rating)) {
        http_response_code(400);
        echo json_encode(["success"=>false,"error"=>"Rating inválido"]);
        exit;
    }

    // Verificar se música existe
    $stmt = $conn->prepare("SELECT id FROM songs WHERE musicbrainz_id = ?");
    $stmt->bind_param("s", $musicbrainz_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $song_id = $result->fetch_assoc()['id'];
    } else {

        $stmtInsert = $conn->prepare("
            INSERT INTO songs (musicbrainz_id, title, artist, release_date)
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->bind_param("ssss", $musicbrainz_id, $title, $artist, $release_date);
        $stmtInsert->execute();
        $song_id = $stmtInsert->insert_id;
    }

    // Impedir duplicado
    $check = $conn->prepare("
        SELECT id FROM reviews WHERE user_id = ? AND song_id = ?
    ");
    $check->bind_param("ii", $user_id, $song_id);
    $check->execute();
    $exists = $check->get_result();

    if ($exists->num_rows > 0) {
        echo json_encode([
            "success"=>false,
            "error"=>"Usuário já avaliou essa música. Use PUT para editar."
        ]);
        exit;
    }

    // Inserir review
    $stmtReview = $conn->prepare("
        INSERT INTO reviews (user_id, song_id, rating)
        VALUES (?, ?, ?)
    ");

    $stmtReview->bind_param("iid", $user_id, $song_id, $rating);
    $stmtReview->execute();

    echo json_encode([
        "success"=>true,
        "message"=>"Avaliação salva com sucesso!"
    ]);
    exit;
}

/*
================================================
PUT → EDITAR AVALIAÇÃO
================================================
*/
if ($method === 'PUT') {

    $data = json_decode(file_get_contents("php://input"), true);

    $user_id = $data['user_id'] ?? null;
    $song_id = $data['song_id'] ?? null;
    $rating = $data['rating'] ?? null;

    if (!$user_id || !$song_id || !$rating) {
        http_response_code(400);
        echo json_encode(["success"=>false,"error"=>"Dados incompletos"]);
        exit;
    }

    if (!validarRating($rating)) {
        http_response_code(400);
        echo json_encode(["success"=>false,"error"=>"Rating inválido"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE reviews
        SET rating = ?
        WHERE user_id = ? AND song_id = ?
    ");

    $stmt->bind_param("dii", $rating, $user_id, $song_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "success"=>false,
            "error"=>"Avaliação não encontrada."
        ]);
        exit;
    }

    echo json_encode([
        "success"=>true,
        "message"=>"Avaliação atualizada!"
    ]);
    exit;
}
