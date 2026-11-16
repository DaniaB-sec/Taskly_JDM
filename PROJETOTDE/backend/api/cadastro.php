<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";

$acao = $_GET["acao"] ?? "cadastrar";

if ($acao === "perfil") {
    if (!isset($_SESSION["usuario_id"])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário não logado."]);
        exit;
    }
    $id_do_usuario = $_SESSION["usuario_id"];
    $stmt = $conn->prepare("SELECT id, nome, email, telefone FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_do_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    echo json_encode(["sucesso" => true, "usuario" => $usuario]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "atualizar") {
    if (!isset($_SESSION["usuario_id"])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário não logado."]);
        exit;
    }
    $id_do_usuario = $_SESSION["usuario_id"];
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");

    if (empty($nome) || empty($email) || empty($telefone)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET nome = ?, email = ?, telefone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nome, $email, $telefone, $id_do_usuario);
    $stmt->execute();
    $_SESSION["usuario_nome"] = $nome;
    echo json_encode(["sucesso" => true, "mensagem" => "Dados atualizados com sucesso!", "nome" => $nome]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "excluir_conta") {
    if (!isset($_SESSION["usuario_id"])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Usuário não logado."]);
        exit;
    }
    $id_do_usuario = $_SESSION["usuario_id"];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_do_usuario);
    $stmt->execute();
    session_unset();
    session_destroy();
    echo json_encode(["sucesso" => true, "mensagem" => "Conta excluída com sucesso!"]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido."]);
    exit;
}

$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";
$telefone = trim($_POST["telefone"] ?? "");

if (empty($nome) || empty($email) || empty($senha) || empty($telefone)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["sucesso" => false, "mensagem" => "Email inválido."]);
    exit;
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (nome, email, senha, telefone) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $nome, $email, $senhaHash, $telefone);

try {
    $stmt->execute();
    $novo_usuario_id = $conn->insert_id;

    $stmtCat = $conn->prepare("INSERT INTO categorias (usuario_id, nome) VALUES (?, 'Estudos'), (?, 'Trabalho'), (?, 'Pessoal')");
    $stmtCat->bind_param("iii", $novo_usuario_id, $novo_usuario_id, $novo_usuario_id);
    $stmtCat->execute();
    $stmtCat->close();

    echo json_encode(["sucesso" => true, "mensagem" => "Cadastro realizado com sucesso!"]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        echo json_encode(["sucesso" => false, "mensagem" => "Este email já está cadastrado."]);
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao cadastrar: " . $e->getMessage()]);
    }
}

$stmt->close();
$conn->close();
