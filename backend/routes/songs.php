<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once "../config/database.php";

try {

    // 🔎 Se existir busca por nome (?search=)
    if (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";

        $stmt = $pdo->prepare("
            SELECT 
                songs.id,
                songs.name,
                artists.name AS artist,
                albums.name AS album,
                songs.release_year
            FROM songs
            INNER JOIN artists ON songs.artist_id = artists.id
            LEFT JOIN albums ON songs.album_id = albums.id
            WHERE songs.name LIKE :search
            ORDER BY songs.name ASC
        ");

        $stmt->bindParam(":search", $search);
        $stmt->execute();
    } 
    // 🎵 Se não tiver busca → lista tudo
    else {

        $stmt = $pdo->query("
            SELECT 
                songs.id,
                songs.name,
                artists.name AS artist,
                albums.name AS album,
                songs.release_year
            FROM songs
            INNER JOIN artists ON songs.artist_id = artists.id
            LEFT JOIN albums ON songs.album_id = albums.id
            ORDER BY songs.name ASC
        ");
    }

    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $songs
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
