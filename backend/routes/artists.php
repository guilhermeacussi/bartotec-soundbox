<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$artist = $_GET['name'] ?? null;

if (!$artist) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Parâmetro 'name' é obrigatório"
    ]);
    exit;
}

$artistEncoded = urlencode($artist);

// ===========================
// 1️⃣ VALIDAR NO MUSICBRAINZ
// ===========================

$mbUrl = "https://musicbrainz.org/ws/2/artist/?query=artist:$artistEncoded&fmt=json";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $mbUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => "SoundBoxApp/1.0 (guilherme@email.com)"
]);

$mbResponse = curl_exec($ch);
curl_close($ch);

$mbData = json_decode($mbResponse, true);

if (empty($mbData['artists'])) {
    echo json_encode([
        "success" => false,
        "error" => "Artista musical não encontrado."
    ]);
    exit;
}

// Pega primeiro artista válido
$artistName = $mbData['artists'][0]['name'];

// ===========================
// BUSCAR BIO NA WIKIPEDIA (cURL)
// ===========================

function buscarWiki($nome) {

    $url = "https://pt.wikipedia.org/api/rest_v1/page/summary/" . urlencode($nome);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "SoundBoxApp/1.0 (guilherme@email.com)"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

$wikiData = buscarWiki($artistName);

if (!$wikiData) {
    $wikiData = buscarWiki($artistName); // pode adaptar para EN se quiser
}

if (!$wikiData) {
    echo json_encode([
        "success" => true,
        "artist" => $artistName,
        "biography" => null,
        "message" => "Artista encontrado, mas sem biografia disponível."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "artist" => $artistName,
    "biography" => $wikiData['extract'] ?? null,
    "image" => $wikiData['thumbnail']['source'] ?? null,
    "wikipedia_url" => $wikiData['content_urls']['desktop']['page'] ?? null
]);
