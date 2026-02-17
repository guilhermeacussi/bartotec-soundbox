<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ============================
// VALIDAR PARÂMETROS
// ============================

$artist = $_GET['artist'] ?? null;
$song   = $_GET['song'] ?? null;

if (!$artist || !$song) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Parâmetros obrigatórios: artist e song"
    ]);
    exit;
}

// ============================
// MONTAR URL MUSICBRAINZ
// ============================

$artistEncoded = urlencode($artist);
$songEncoded   = urlencode($song);

$url = "https://musicbrainz.org/ws/2/recording/?query=recording:$songEncoded%20AND%20artist:$artistEncoded&fmt=json";

// ============================
// REQUISIÇÃO COM cURL
// ============================

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => "SoundBoxApp/1.0 (guilherme@email.com)",
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro cURL: " . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        "success" => false,
        "error" => "Erro na API MusicBrainz. Código HTTP: $httpCode"
    ]);
    exit;
}

// ============================
// PROCESSAR RESPOSTA
// ============================

$data = json_decode($response, true);

if (empty($data['recordings'])) {
    echo json_encode([
        "success" => false,
        "message" => "Música não encontrada."
    ]);
    exit;
}

$record = $data['recordings'][0];

$result = [
    "success" => true,
    "data" => [
        "title" => $record['title'] ?? null,
        "artist" => $record['artist-credit'][0]['name'] ?? null,
        "release_date" => $record['first-release-date'] ?? null,
        "musicbrainz_id" => $record['id'] ?? null
    ]
];

echo json_encode($result);
