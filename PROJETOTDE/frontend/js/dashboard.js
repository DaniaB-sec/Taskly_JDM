const url_tarefas = "../backend/api/tarefas.php";
const url_cadastro = "../backend/api/cadastro.php";

const inputNovaCategoria = document.getElementById("inputNovaCategoria");
const btnAdicionarCategoria = document.getElementById("btnAdicionarCategoria");
const listaCategorias = document.getElementById("listaCategorias");

const formPerfil = document.getElementById("formPerfil");
const perfilNome = document.getElementById("perfilNome");
const perfilEmail = document.getElementById("perfilEmail");
const perfilTelefone = document.getElementById("perfilTelefone");
const btnExcluirConta = document.getElementById("btnExcluirConta");

function carregarCategorias() {
    fetch(`${url_tarefas}?acao=listar_categorias`)
    .then(r => r.json())
    .then(dados => {
        if (!dados.sucesso) {
            window.location.href = "login.html";
            return;
        }
        listaCategorias.innerHTML = "";
        dados.categorias.forEach(cat => {
            const li = document.createElement("li");
            li.className = "item-tarefa";

            const span = document.createElement("span");
            span.textContent = cat.nome;

            const divBotoes = document.createElement("div");
            divBotoes.className = "botoes-tarefa";

            const btnEditar = document.createElement("button");
            btnEditar.innerHTML = '<i class="bi bi-pencil" style="color: var(--cor-primaria);"></i>';
            btnEditar.addEventListener("click", () => {
                const novoNome = prompt("Novo nome da categoria:", cat.nome);
                if (!novoNome || novoNome.trim() === "") return;
                const fd = new FormData();
                fd.append("id", cat.id);
                fd.append("nome", novoNome.trim());
                fetch(`${url_tarefas}?acao=editar_categoria`, { method: "POST", body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) carregarCategorias();
                    else alert(res.mensagem);
                });
            });

            const btnExcluir = document.createElement("button");
            btnExcluir.innerHTML = '<i class="bi bi-trash" style="color: red;"></i>';
            btnExcluir.addEventListener("click", () => {
                const fd = new FormData();
                fd.append("id", cat.id);
                fetch(`${url_tarefas}?acao=excluir_categoria`, { method: "POST", body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) carregarCategorias();
                });
            });

            divBotoes.appendChild(btnEditar);
            divBotoes.appendChild(btnExcluir);
            li.appendChild(span);
            li.appendChild(divBotoes);
            listaCategorias.appendChild(li);
        });
    });
}

btnAdicionarCategoria.addEventListener("click", () => {
    const nome = inputNovaCategoria.value.trim();
    if (!nome) {
        alert("Digite o nome da categoria!");
        return;
    }
    const fd = new FormData();
    fd.append("nome", nome);
    fetch(`${url_tarefas}?acao=adicionar_categoria`, { method: "POST", body: fd })
    .then(r => r.json())
    .then(dados => {
        if (dados.sucesso) {
            inputNovaCategoria.value = "";
            carregarCategorias();
        } else {
            alert(dados.mensagem);
        }
    });
});

function carregarPerfil() {
    fetch(`${url_cadastro}?acao=perfil`)
    .then(r => r.json())
    .then(dados => {
        if (dados.sucesso && dados.usuario) {
            perfilNome.value = dados.usuario.nome;
            perfilEmail.value = dados.usuario.email;
            perfilTelefone.value = dados.usuario.telefone;
        }
    });
}

formPerfil.addEventListener("submit", (e) => {
    e.preventDefault();
    const fd = new FormData(formPerfil);
    fetch(`${url_cadastro}?acao=atualizar`, { method: "POST", body: fd })
    .then(r => r.json())
    .then(dados => {
        alert(dados.mensagem);
        if (dados.sucesso) {
            localStorage.setItem("usuario", dados.nome);
        }
    });
});

btnExcluirConta.addEventListener("click", () => {
    if (!confirm("Tem certeza que deseja excluir sua conta e todas as suas tarefas?")) return;
    fetch(`${url_cadastro}?acao=excluir_conta`, { method: "POST" })
    .then(r => r.json())
    .then(dados => {
        alert(dados.mensagem);
        if (dados.sucesso) {
            localStorage.removeItem("usuario");
            window.location.href = "index.html";
        }
    });
});

carregarCategorias();
carregarPerfil();
