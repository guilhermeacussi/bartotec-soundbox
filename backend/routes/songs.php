<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$artist = $_GET['artist'] ?? "";
$song   = $_GET['song'] ?? "";

if (!$artist || !$song) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Parâmetros 'artist' e 'song' são obrigatórios."
    ]);
    exit;
}

$artistEncoded = urlencode($artist);
$songEncoded   = urlencode($song);

$url = "https://musicbrainz.org/ws/2/recording/?query=recording:{$songEncoded}%20AND%20artist:{$artistEncoded}&fmt=json";

$response = file_get_contents($url);

if ($response === FALSE) {
    echo json_encode([
        "success" => false,
        "error" => "Erro ao conectar com MusicBrainz."
    ]);
    exit;
}

$data = json_decode($response, true);

if (empty($data['recordings'])) {
    echo json_encode([
        "success" => false,
        "message" => "Música não encontrada."
    ]);
    exit;
}

$record = $data['recordings'][0];

echo json_encode([
    "success" => true,
    "song" => $record['title'] ?? null,
    "artist" => $record['artist-credit'][0]['name'] ?? null,
    "release_date" => $record['first-release-date'] ?? null
]);
