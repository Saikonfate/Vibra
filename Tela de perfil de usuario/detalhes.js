document.addEventListener('DOMContentLoaded', () => {
    // --- Lógica da Sidebar ---
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay"); // Overlay principal da sidebar
    const sidebarToggleBtn = document.querySelector(".user-btn");

    window.toggleSidebar = function() { // Função global para o onclick do HTML
        if (sidebar && overlay && sidebarToggleBtn) {
            const isVisible = sidebar.classList.toggle("visible");
            sidebarToggleBtn.setAttribute('aria-expanded', isVisible.toString());
            overlay.style.display = isVisible ? "block" : "none";
        }
    };

    function closeSidebar() {
        if (sidebar && overlay && sidebarToggleBtn && sidebar.classList.contains("visible")) {
            sidebar.classList.remove("visible");
            sidebarToggleBtn.setAttribute('aria-expanded', 'false');
            overlay.style.display = "none";
        }
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    // Fecha a sidebar se clicar fora dela (mantido do seu original)
    window.addEventListener("click", function (event) {
        if (sidebar && sidebarToggleBtn && sidebar.classList.contains("visible") &&
            !sidebar.contains(event.target) &&
            !sidebarToggleBtn.contains(event.target) &&
            !event.target.closest('.modal-perfil')) { // Não fecha se clicar dentro do modal
            closeSidebar();
        }
    });

    // --- Lógica do Modal de Edição de Perfil ---
    const modalEditarPerfil = document.getElementById('modalEditarPerfil');
    const formEditarPerfil = document.getElementById('formEditarPerfil');
    const novaDescricaoTextarea = document.getElementById('nova_descricao');
    const novaFotoInput = document.getElementById('nova_foto_perfil');
    const previewFotoPerfilImg = document.getElementById('previewFotoPerfil');
    const editarPerfilFeedbackDiv = document.getElementById('editarPerfilFeedback');

    // Elementos na página para exibir os dados atualizados
    const fotoPerfilAtualImg = document.getElementById('fotoPerfilAtual');
    const descricaoPerfilAtualP = document.getElementById('descricaoPerfilAtual');

    window.abrirModalPerfil = function() {
        if (modalEditarPerfil && novaDescricaoTextarea && previewFotoPerfilImg && descricaoPerfilAtualP && fotoPerfilAtualImg) {
            // Preenche o textarea com a descrição atual (removendo <br> se houver)
            let descAtual = descricaoPerfilAtualP.innerHTML.replace(/<br\s*[\/]?>/gi, "\n").trim();
            if (descAtual === 'Para adicionar uma descrição ou alterar seus dados, vá para "Minha Conta & Cadastros."' || descAtual === 'Localização não informada') { // Limpa placeholders
                 novaDescricaoTextarea.value = '';
            } else {
                 novaDescricaoTextarea.value = descAtual;
            }
            
            // Mostra a foto atual no preview
            previewFotoPerfilImg.src = fotoPerfilAtualImg.src;
            previewFotoPerfilImg.style.display = fotoPerfilAtualImg.src.includes('placeholder_usuario.png') ? 'none' : 'block';
            
            novaFotoInput.value = ''; // Limpa seleção de arquivo anterior
            if (editarPerfilFeedbackDiv) editarPerfilFeedbackDiv.textContent = '';
            modalEditarPerfil.style.display = 'flex';
        } else {
            console.error('Elementos do modal não encontrados.');
        }
    };

    window.fecharModalPerfil = function() {
        if (modalEditarPerfil) {
            modalEditarPerfil.style.display = 'none';
        }
    };

    if (novaFotoInput && previewFotoPerfilImg) {
        novaFotoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewFotoPerfilImg.src = e.target.result;
                    previewFotoPerfilImg.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                // Se nenhum arquivo for selecionado, pode reverter para a foto atual ou um placeholder
                // previewFotoPerfilImg.src = fotoPerfilAtualImg.src; 
                // previewFotoPerfilImg.style.display = fotoPerfilAtualImg.src.includes('placeholder_usuario.png') ? 'none' : 'block';
            }
        });
    }

    if (formEditarPerfil) {
        formEditarPerfil.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (editarPerfilFeedbackDiv) {
                editarPerfilFeedbackDiv.textContent = 'Salvando...';
                editarPerfilFeedbackDiv.className = 'feedback-message-modal info';
            }

            const formData = new FormData(this);
            // Não precisa adicionar id_usuario, pois será pego da sessão no PHP

            try {
                const response = await fetch('atualizar_detalhes_perfil.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (editarPerfilFeedbackDiv) {
                        editarPerfilFeedbackDiv.textContent = result.message;
                        editarPerfilFeedbackDiv.className = 'feedback-message-modal success';
                    }
                    // Atualiza a descrição na página
                    if (result.novaDescricao !== undefined && descricaoPerfilAtualP) {
                        descricaoPerfilAtualP.innerHTML = result.novaDescricao.replace(/\n/g, '<br>');
                    }
                    // Atualiza a foto na página
                    if (result.novaFotoUrl !== undefined && fotoPerfilAtualImg && previewFotoPerfilImg) {
                        const newPhotoPath = result.novaFotoUrl; // Já vem com '../' do PHP
                        fotoPerfilAtualImg.src = newPhotoPath;
                        previewFotoPerfilImg.src = newPhotoPath; 
                    }
                    
                    // Opcional: fechar modal após um pequeno delay
                    setTimeout(() => {
                        if (result.success) fecharModalPerfil();
                    }, 1500);

                } else {
                    if (editarPerfilFeedbackDiv) {
                        editarPerfilFeedbackDiv.textContent = result.message || 'Erro ao atualizar.';
                        editarPerfilFeedbackDiv.className = 'feedback-message-modal error';
                    }
                }
            } catch (error) {
                console.error('Erro no fetch:', error);
                if (editarPerfilFeedbackDiv) {
                    editarPerfilFeedbackDiv.textContent = 'Erro de comunicação com o servidor.';
                    editarPerfilFeedbackDiv.className = 'feedback-message-modal error';
                }
            }
        });
    }

    // Fechar o modal se clicar fora do conteúdo dele
    if (modalEditarPerfil) {
        modalEditarPerfil.addEventListener('click', function(event) {
            if (event.target === modalEditarPerfil) { // Clicou no fundo do modal
                fecharModalPerfil();
            }
        });
    }
});