const formulario = document.getElementById("formLogin");

formulario.addEventListener("submit", function (event) {
    event.preventDefault();

    const dados = new FormData(formulario);

    fetch("../backend/api/login.php", {
        method: "POST",
        body: dados
    })
    .then(response => response.text())
    .then(texto => {
        try {
            return JSON.parse(texto);
        } catch (e) {
            const limpo = texto.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
            console.error("Resposta do servidor:", texto);
            throw new Error("O PHP retornou erro: " + limpo.slice(0, 400));
        }
    })
    .then(data => {
        alert(data.mensagem);

        if (data.sucesso) {
            localStorage.setItem("usuario", data.nome);
            window.location.href = "home.html";
        }
    })
    .catch(error => {
        console.error("Erro:", error);
        alert(error.message || "Erro ao conectar com o servidor.");
    });
});
