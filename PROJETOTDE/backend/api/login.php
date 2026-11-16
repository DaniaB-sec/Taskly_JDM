<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido."]);
    exit;
}

$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";

if (empty($email) || empty($senha)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nome, senha FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode(["sucesso" => false, "mensagem" => "Email ou senha inválidos."]);
    $stmt->close();
    $conn->close();
    exit;
}

$usuario = $resultado->fetch_assoc();

if (!password_verify($senha, $usuario["senha"])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Email ou senha inválidos."]);
    $stmt->close();
    $conn->close();
    exit;
}

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["usuario_nome"] = $usuario["nome"];

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Login realizado com sucesso!",
    "nome" => $usuario["nome"]
]);

$stmt->close();
$conn->close();
