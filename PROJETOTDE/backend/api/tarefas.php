<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";

$acao = $_GET["acao"] ?? "";

if ($acao === "logout") {
    session_unset();
    session_destroy();
    echo json_encode(["sucesso" => true]);
    exit;
}

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Usuário não logado."]);
    exit;
}

$id_do_usuario = $_SESSION["usuario_id"];

if ($acao === "listar_categorias") {
    $stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE usuario_id = ? ORDER BY nome ASC");
    $stmt->bind_param("i", $id_do_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $categorias = [];
    while ($linha = $resultado->fetch_assoc()) {
        $categorias[] = $linha;
    }
    echo json_encode(["sucesso" => true, "categorias" => $categorias]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "adicionar_categoria") {
    $nome_categoria = trim($_POST["nome"] ?? "");
    if (empty($nome_categoria)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Digite o nome da categoria."]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO categorias (usuario_id, nome) VALUES (?, ?)");
    $stmt->bind_param("is", $id_do_usuario, $nome_categoria);
    $stmt->execute();
    echo json_encode(["sucesso" => true, "mensagem" => "Categoria adicionada!"]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "editar_categoria") {
    $id_categoria = (int)($_POST["id"] ?? 0);
    $nome_categoria = trim($_POST["nome"] ?? "");
    if (empty($nome_categoria) || $id_categoria <= 0) {
        echo json_encode(["sucesso" => false, "mensagem" => "Dados inválidos para editar categoria."]);
        exit;
    }
    $stmt = $conn->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("sii", $nome_categoria, $id_categoria, $id_do_usuario);
    $stmt->execute();
    echo json_encode(["sucesso" => true, "mensagem" => "Categoria atualizada!"]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "excluir_categoria") {
    $id_categoria = (int)($_POST["id"] ?? 0);
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_categoria, $id_do_usuario);
    $stmt->execute();
    echo json_encode(["sucesso" => true, "mensagem" => "Categoria excluída!"]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "listar") {
    $filtro_cat = isset($_GET["categoria_id"]) && $_GET["categoria_id"] !== "" ? (int)$_GET["categoria_id"] : null;

    if ($filtro_cat !== null) {
        $stmt = $conn->prepare("
            SELECT t.id, t.texto, t.concluida, t.categoria_id, c.nome AS categoria_nome
            FROM tarefas t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.usuario_id = ? AND t.categoria_id = ?
            ORDER BY t.id DESC
        ");
        $stmt->bind_param("ii", $id_do_usuario, $filtro_cat);
    } else {
        $stmt = $conn->prepare("
            SELECT t.id, t.texto, t.concluida, t.categoria_id, c.nome AS categoria_nome
            FROM tarefas t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.usuario_id = ?
            ORDER BY t.id DESC
        ");
        $stmt->bind_param("i", $id_do_usuario);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $lista = [];
    while ($linha = $resultado->fetch_assoc()) {
        $linha["concluida"] = (bool)$linha["concluida"]; 
        $lista[] = $linha;
    }
    
    echo json_encode(["sucesso" => true, "tarefas" => $lista]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "adicionar") {
    $texto_da_tarefa = trim($_POST["texto"] ?? "");
    $categoria_id = !empty($_POST["categoria_id"]) ? (int)$_POST["categoria_id"] : null;
    
    if (empty($texto_da_tarefa)) {
        echo json_encode(["sucesso" => false, "mensagem" => "Digite uma tarefa."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tarefas (usuario_id, categoria_id, texto) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id_do_usuario, $categoria_id, $texto_da_tarefa);
    
    if ($stmt->execute()) {
        echo json_encode(["sucesso" => true, "mensagem" => "Tarefa adicionada!"]);
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao salvar."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "editar") {
    $id_da_tarefa = (int)($_POST["id"] ?? 0);
    $novo_texto = trim($_POST["texto"] ?? "");
    $categoria_id = !empty($_POST["categoria_id"]) ? (int)$_POST["categoria_id"] : null;

    if (empty($novo_texto) || $id_da_tarefa <= 0) {
        echo json_encode(["sucesso" => false, "mensagem" => "Digite o novo texto da tarefa."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE tarefas SET texto = ?, categoria_id = ? WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("siii", $novo_texto, $categoria_id, $id_da_tarefa, $id_do_usuario);
    $stmt->execute();

    echo json_encode(["sucesso" => true, "mensagem" => "Tarefa atualizada!"]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "concluir") {
    $id_da_tarefa = (int)($_POST["id"] ?? 0);
    
    $stmt = $conn->prepare("UPDATE tarefas SET concluida = NOT concluida WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_da_tarefa, $id_do_usuario);
    $stmt->execute();
    
    echo json_encode(["sucesso" => true]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($acao === "excluir") {
    $id_da_tarefa = (int)($_POST["id"] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM tarefas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_da_tarefa, $id_do_usuario);
    $stmt->execute();
    
    echo json_encode(["sucesso" => true]);
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(["sucesso" => false, "mensagem" => "Ação inválida."]);
$conn->close();
