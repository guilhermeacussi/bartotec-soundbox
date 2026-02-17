<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

// Pega dados enviados pelo frontend
$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data["username"] ?? "");
$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// 🔎 Validação básica
if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Todos os campos são obrigatórios."
    ]);
    exit;
}

// 🔎 Verificar se email já existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->bindParam(":email", $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "error" => "Email já cadastrado."
    ]);
    exit;
}

// 🔎 Verificar se username já existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
$stmt->bindParam(":username", $username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "error" => "Nome de usuário já está em uso."
    ]);
    exit;
}

// 🔐 Criptografar senha
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 📝 Inserir usuário
$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password_hash)
    VALUES (:username, :email, :password_hash)
");

$stmt->bindParam(":username", $username);
$stmt->bindParam(":email", $email);
$stmt->bindParam(":password_hash", $password_hash);

$stmt->execute();

// 🎉 Retorno de sucesso
echo json_encode([
    "success" => true,
    "message" => "Usuário cadastrado com sucesso."
]);
