const nomeDoUsuarioLogado = localStorage.getItem("usuario");

if (!nomeDoUsuarioLogado) {
    window.location.href = "login.html";
} else {
    document.getElementById("nomeUsuario").textContent = nomeDoUsuarioLogado;
}

const inputNovaTarefa = document.getElementById("inputNovaTarefa");
const selectCategoriaTarefa = document.getElementById("selectCategoriaTarefa");
const filtroCategoria = document.getElementById("filtroCategoria");
const botaoAdicionarTarefa = document.getElementById("btnAdicionarTarefa");
const ulListaTarefas = document.getElementById("listaTarefas");
const spanContadorTarefas = document.getElementById("contadorTarefas");

const url_da_api = "../backend/api/tarefas.php";
let listaCategoriasCache = [];

const botaoSair = document.getElementById("btnLogout");
botaoSair.addEventListener("click", () => {
    fetch(`${url_da_api}?acao=logout`)
    .finally(() => {
        localStorage.removeItem("usuario");
        window.location.href = "index.html";
    });
});

function carregarCategorias() {
    fetch(`${url_da_api}?acao=listar_categorias`)
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            listaCategoriasCache = dados.categorias;
            selectCategoriaTarefa.innerHTML = '<option value="">Sem categoria</option>';
            filtroCategoria.innerHTML = '<option value="">Todas as categorias</option>';

            dados.categorias.forEach(cat => {
                const opt1 = document.createElement("option");
                opt1.value = cat.id;
                opt1.textContent = cat.nome;
                selectCategoriaTarefa.appendChild(opt1);

                const opt2 = document.createElement("option");
                opt2.value = cat.id;
                opt2.textContent = cat.nome;
                filtroCategoria.appendChild(opt2);
            });
        }
    });
}

filtroCategoria.addEventListener("change", () => {
    buscarTarefasNoBanco();
});

function buscarTarefasNoBanco() {
    const catFiltro = filtroCategoria.value;
    fetch(`${url_da_api}?acao=listar&categoria_id=${encodeURIComponent(catFiltro)}`)
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            desenharTarefasNaTela(dados.tarefas);
        } else {
            alert(dados.mensagem);
            window.location.href = "login.html"; 
        }
    })
    .catch(error => console.error("Erro ao buscar:", error));
}

function desenharTarefasNaTela(listaDeTarefas) {
    ulListaTarefas.innerHTML = "";
    spanContadorTarefas.textContent = `${listaDeTarefas.length} tarefas`;

    listaDeTarefas.forEach((tarefa) => {
        const li = document.createElement("li");
        li.className = "item-tarefa";
        
        if (tarefa.concluida) {
            li.classList.add("concluida");
        }

        const divConteudo = document.createElement("div");
        const spanTexto = document.createElement("span");
        spanTexto.textContent = tarefa.texto;
        divConteudo.appendChild(spanTexto);

        if (tarefa.categoria_nome) {
            const badge = document.createElement("small");
            badge.className = "badge-categoria";
            badge.textContent = tarefa.categoria_nome;
            divConteudo.appendChild(badge);
        }

        const divBotoes = document.createElement("div");
        divBotoes.className = "botoes-tarefa";

        const btnConcluir = document.createElement("button");
        btnConcluir.title = "Concluir / Reabrir";
        btnConcluir.innerHTML = tarefa.concluida ? '<i class="bi bi-x-circle"></i>' : '<i class="bi bi-check-circle" style="color: green;"></i>';
        
        btnConcluir.addEventListener("click", () => {
            const dados = new FormData();
            dados.append("id", tarefa.id);
            
            fetch(`${url_da_api}?acao=concluir`, { method: "POST", body: dados })
            .then(response => response.json())
            .then(dados => { if (dados.sucesso) buscarTarefasNoBanco(); });
        });

        const btnEditar = document.createElement("button");
        btnEditar.title = "Editar tarefa";
        btnEditar.innerHTML = '<i class="bi bi-pencil" style="color: var(--cor-primaria);"></i>';

        btnEditar.addEventListener("click", () => {
            const novoTexto = prompt("Editar descrição da tarefa:", tarefa.texto);
            if (novoTexto === null || novoTexto.trim() === "") return;

            const dados = new FormData();
            dados.append("id", tarefa.id);
            dados.append("texto", novoTexto.trim());
            dados.append("categoria_id", tarefa.categoria_id || "");

            fetch(`${url_da_api}?acao=editar`, { method: "POST", body: dados })
            .then(response => response.json())
            .then(dados => {
                if (dados.sucesso) {
                    buscarTarefasNoBanco();
                } else {
                    alert(dados.mensagem);
                }
            });
        });

        const btnExcluir = document.createElement("button");
        btnExcluir.title = "Excluir tarefa";
        btnExcluir.innerHTML = '<i class="bi bi-trash" style="color: red;"></i>';
        
        btnExcluir.addEventListener("click", () => {
            const dados = new FormData();
            dados.append("id", tarefa.id);
            
            fetch(`${url_da_api}?acao=excluir`, { method: "POST", body: dados })
            .then(response => response.json())
            .then(dados => { if (dados.sucesso) buscarTarefasNoBanco(); });
        });

        divBotoes.appendChild(btnConcluir);
        divBotoes.appendChild(btnEditar);
        divBotoes.appendChild(btnExcluir);
        li.appendChild(divConteudo);
        li.appendChild(divBotoes);
        ulListaTarefas.appendChild(li);
    });
}

botaoAdicionarTarefa.addEventListener("click", () => {
    const textoDigitado = inputNovaTarefa.value.trim();

    if (textoDigitado === "") {
        alert("Por favor, digite uma tarefa!");
        return;
    }

    const dados = new FormData();
    dados.append("texto", textoDigitado);
    dados.append("categoria_id", selectCategoriaTarefa.value);

    fetch(`${url_da_api}?acao=adicionar`, {
        method: "POST",
        body: dados
    })
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            inputNovaTarefa.value = "";
            buscarTarefasNoBanco();
        } else {
            alert(dados.mensagem);
        }
    });
});

inputNovaTarefa.addEventListener("keypress", (evento) => {
    if (evento.key === "Enter") botaoAdicionarTarefa.click();
});

carregarCategorias();
buscarTarefasNoBanco();
