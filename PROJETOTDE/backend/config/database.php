<?php

ini_set("display_errors", "0");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responderErro($mensagem) {
    if (!headers_sent()) {
        header("Content-Type: application/json");
    }
    echo json_encode(["sucesso" => false, "mensagem" => $mensagem]);
    exit;
}

set_exception_handler(function ($e) {
    responderErro("Erro no servidor: " . $e->getMessage());
});

$host = "127.0.0.1";
$usuario = "root";
$senha = "";
$banco = "taskly";
$porta = 3307;

try {
    $conn = new mysqli($host, $usuario, $senha, "", $porta);
} catch (mysqli_sql_exception $e) {
    $porta = 3306;
    $conn = new mysqli($host, $usuario, $senha, "", $porta);
}

$conn->set_charset("utf8mb4");

$conn->query("CREATE DATABASE IF NOT EXISTS `$banco`");
$conn->select_db($banco);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT DEFAULT NULL,
    texto VARCHAR(255) NOT NULL,
    concluida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
)");

$colCheck = $conn->query("SHOW COLUMNS FROM tarefas LIKE 'categoria_id'");
if ($colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE tarefas ADD COLUMN categoria_id INT DEFAULT NULL");
    $conn->query("ALTER TABLE tarefas ADD CONSTRAINT fk_tarefas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL");
}
