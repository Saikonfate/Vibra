document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM completamente carregado e parseado.');

    // --- Controle da Sidebar ---
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const sidebarToggleBtn = document.querySelector(".user-btn"); 

    if (!sidebar) {
        console.error("Elemento da sidebar (#sidebar) NÃO encontrado!");
    }
    if (!overlay) {
        console.error("Elemento do overlay (#overlay) NÃO encontrado!");
    }
    if (!sidebarToggleBtn) {
        console.error("Botão de toggle da sidebar (.user-btn) NÃO encontrado!");
    }

    window.toggleSidebar = function() {
        console.log('toggleSidebar foi chamada.');
        if (sidebar) {
            const isVisible = sidebar.classList.toggle("visible");
            if (sidebarToggleBtn) {
                sidebarToggleBtn.setAttribute('aria-expanded', isVisible.toString());
            }
            if (overlay) {
                overlay.style.display = isVisible ? "block" : "none";
            }
        }
    };
     if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar); 
    }

    function closeSidebar() {
        if (sidebar && sidebar.classList.contains("visible")) {
            sidebar.classList.remove("visible");
            if (sidebarToggleBtn) {
                sidebarToggleBtn.setAttribute('aria-expanded', 'false');
            }
            if (overlay) {
                overlay.style.display = "none";
            }
        }
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    window.addEventListener("click", function (event) {
        if (sidebar && sidebar.classList.contains("visible") &&
            sidebarToggleBtn &&
            !sidebar.contains(event.target) &&
            !sidebarToggleBtn.contains(event.target) &&
            !event.target.closest('.modal')) { 
            closeSidebar();
        }
    });

    // --- EDIÇÃO DE CAMPOS (PERFIL USUÁRIO) ---
    const editButtons = document.querySelectorAll('#conta .edit-btn');
    const iconPencil = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
        </svg>`;
    const iconCheck = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>`;

    editButtons.forEach(button => {
        const formGroup = button.closest('.form-group');
        if (!formGroup) return;
        const input = formGroup.querySelector('.form-input');
        if (!input) return;

        let originalValue = input.value;

        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const feedbackElement = formGroup.querySelector('.form-feedback');
            if (feedbackElement) {
                feedbackElement.textContent = '';
                feedbackElement.className = 'form-feedback';
            }

            if (!input.classList.contains('editable')) {
                originalValue = input.value;
                input.classList.add('editable');
                input.removeAttribute('readonly');
                input.focus();

                const supportedTypesForSelection = ['text', 'search', 'url', 'tel', 'password'];
                if (supportedTypesForSelection.includes(input.type)) {
                    input.setSelectionRange(input.value.length, input.value.length);
                }

                this.innerHTML = iconCheck;
                this.setAttribute('aria-label', `Salvar ${input.dataset.field}`);
            } else {
                const newValue = input.value.trim();
                const fieldName = input.dataset.field;

                if (fieldName === 'nome' && newValue === '') {
                    if (feedbackElement) {
                        feedbackElement.textContent = 'O nome não pode estar vazio.';
                        feedbackElement.classList.add('error');
                    }
                    input.focus();
                    return;
                }
                if (fieldName === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(newValue)) {
                        if (feedbackElement) {
                            feedbackElement.textContent = 'Por favor, insira um email válido.';
                            feedbackElement.classList.add('error');
                        }
                        input.focus();
                        return;
                    }
                }

                if (newValue === originalValue) {
                    input.classList.remove('editable');
                    input.setAttribute('readonly', true);
                    this.innerHTML = iconPencil;
                    this.setAttribute('aria-label', `Editar ${input.dataset.field}`);
                    if (feedbackElement) feedbackElement.textContent = '';
                    return;
                }

                if (feedbackElement) {
                    feedbackElement.textContent = 'Salvando...';
                    feedbackElement.classList.remove('error', 'success');
                }
                button.disabled = true;

                try {
                    const response = await fetch('atualizar_usuario.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ [fieldName]: newValue })
                    });

                    const result = await response.json();
                    button.disabled = false;

                    if (result.success) {
                        input.classList.remove('editable');
                        input.setAttribute('readonly', true);
                        input.value = result.newValue;
                        originalValue = result.newValue;
                        this.innerHTML = iconPencil;
                        this.setAttribute('aria-label', `Editar ${input.dataset.field}`);

                        if (feedbackElement) {
                            feedbackElement.textContent = result.message;
                            feedbackElement.classList.add('success');
                        }
                        if (fieldName === 'nome') {
                            const sidebarUsername = document.getElementById('sidebar-username');
                            if (sidebarUsername) {
                                sidebarUsername.textContent = result.newValue;
                            }
                        }
                    } else {
                        if (feedbackElement) {
                            feedbackElement.textContent = result.message || 'Erro ao salvar. Tente novamente.';
                            feedbackElement.classList.add('error');
                        }
                        input.focus();
                    }
                } catch (error) {
                    button.disabled = false;
                    console.error('Erro na requisição AJAX:', error);
                    if (feedbackElement) {
                        feedbackElement.textContent = 'Erro de comunicação. Verifique sua conexão.';
                        feedbackElement.classList.add('error');
                    }
                    input.focus();
                }
            }
        });
    });

    // --- Controle do Modal de Alteração de Senha ---
    const changePasswordModal = document.getElementById('changePasswordModal');
    const openChangePasswordModalBtn = document.getElementById('openChangePasswordModal');
    const closeChangePasswordModalBtn = document.getElementById('closeChangePasswordModal');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const passwordChangeFeedback = document.getElementById('passwordChangeFeedback');

    function openPasswordModal() {
        if (changePasswordModal) {
            changePasswordModal.style.display = 'flex'; 
            if (passwordChangeFeedback) {
                passwordChangeFeedback.textContent = '';
                passwordChangeFeedback.className = 'form-feedback'; 
            }
            if(changePasswordForm) changePasswordForm.reset();
        }
    }

    function closePasswordModal() {
        if (changePasswordModal) {
            changePasswordModal.style.display = 'none';
        }
    }

    if (openChangePasswordModalBtn) {
        openChangePasswordModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openPasswordModal();
        });
    }

    if (closeChangePasswordModalBtn) {
        closeChangePasswordModalBtn.addEventListener('click', closePasswordModal);
    }

    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function(event) {
            if (event.target === changePasswordModal) { 
                closePasswordModal();
            }
        });
    }
    

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (passwordChangeFeedback) {
                 passwordChangeFeedback.textContent = '';
                 passwordChangeFeedback.className = 'form-feedback'; // Reseta classes
            }

            const currentPasswordInput = document.getElementById('current_password');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            if (!currentPasswordInput || !newPasswordInput || !confirmPasswordInput) {
                if(passwordChangeFeedback) {
                    passwordChangeFeedback.textContent = 'Erro: Campos do formulário não encontrados.';
                    passwordChangeFeedback.classList.add('error');
                }
                return;
            }

            const currentPassword = currentPasswordInput.value;
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword.length < 8) {
                if(passwordChangeFeedback) {
                    passwordChangeFeedback.textContent = 'A nova senha deve ter pelo menos 8 caracteres.';
                    passwordChangeFeedback.classList.add('error');
                }
                return;
            }

            if (newPassword !== confirmPassword) {
                if(passwordChangeFeedback) {
                    passwordChangeFeedback.textContent = 'As novas senhas não coincidem.';
                    passwordChangeFeedback.classList.add('error');
                }
                return;
            }

            const formData = new FormData();
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmPassword);

            if (passwordChangeFeedback) {
                passwordChangeFeedback.textContent = 'Alterando...';
                passwordChangeFeedback.classList.remove('error', 'success');
            }

            try {
                const response = await fetch('alterar_senha.php', { 
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (passwordChangeFeedback) {
                    passwordChangeFeedback.textContent = result.message;
                    if (result.success) {
                        passwordChangeFeedback.classList.remove('error'); 
                        passwordChangeFeedback.classList.add('success');
                        changePasswordForm.reset();
                        setTimeout(closePasswordModal, 2000); 
                    } else {
                        passwordChangeFeedback.classList.remove('success'); 
                        passwordChangeFeedback.classList.add('error');
                    }
                }
            } catch (error) {
                console.error('Erro na requisição de alteração de senha:', error);
                if (passwordChangeFeedback) {
                    passwordChangeFeedback.textContent = 'Erro de comunicação. Tente novamente.';
                    passwordChangeFeedback.classList.remove('success');
                    passwordChangeFeedback.classList.add('error');
                }
            }
        });
    }
    
    // --- DELEÇÃO DE CONTA ---
    const openDeleteAccountModalBtn = document.getElementById('openDeleteAccountModal');

    if (openDeleteAccountModalBtn) {
        openDeleteAccountModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isConfirmed = confirm('Você tem certeza que deseja deletar sua conta?\n\nSua conta e dados serão desativados em 7 dias. Fazer login novamente durante este período cancelará a exclusão.\n\nEsta ação não pode ser desfeita após o período de 7 dias.');

            if (isConfirmed) {
                deleteAccount();
            }
        });
    }

    async function deleteAccount() {
        try {
            const response = await fetch('solicitar_delecao.php', {
                method: 'POST',
            });
            const result = await response.json();

            alert(result.message); 

            if (result.success) {
                // Redireciona para a página de login após o logout forçado
                window.location.href = '../Login/login.php';
            }

        } catch (error) {
            console.error('Erro na requisição para deletar conta:', error);
            alert('Ocorreu um erro de comunicação. Tente novamente.');
        }
    }
    
    // --- NAVEGAÇÃO ENTRE ABAS ---
    function setupTabNavigation(buttonSelector, contentSelector, activeClass = 'active') {
        const tabButtons = document.querySelectorAll(buttonSelector);
        const tabContents = document.querySelectorAll(contentSelector);

        if (tabButtons.length === 0) return;

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => {
                    btn.classList.remove(activeClass);
                    btn.setAttribute('aria-selected', 'false');
                });
                tabContents.forEach(content => content.classList.remove(activeClass));

                button.classList.add(activeClass);
                button.setAttribute('aria-selected', 'true');
                const tabId = button.getAttribute('data-tab');
                const activeContent = document.getElementById(tabId);
                if (activeContent) {
                    activeContent.classList.add(activeClass);
                }
            });
        });
    }

    setupTabNavigation('.tab-btn', '.tab-content');
    setupTabNavigation('.tab-secondary-btn', '.tab-secondary-content');

    // --- IMAGENS & FORMULÁRIO DE CADASTRO ---
    function setupImageUpload() {
        setupImageUploadForForm('imagens-ponto', 'preview-container-ponto');
        setupImageUploadForForm('imagens-evento', 'preview-container-evento');
    }

    function setupImageUploadForForm(fileInputId, previewContainerId) {
        const fileInput = document.getElementById(fileInputId);
        if (!fileInput) return;

        const form = fileInput.closest('form');
        if (!form) return;

        const previewContainer = form.querySelector(`#${previewContainerId}`);
        const fileNameDisplay = fileInput.closest('.file-upload-area')?.querySelector('.file-name');

        if (!previewContainer || !fileNameDisplay) {
            console.warn(`Elementos de preview ou nome de arquivo não encontrados para ${fileInputId}`);
            return;
        }

        let currentFileObjects = new DataTransfer();

        fileInput.addEventListener('change', function(event) {
            const newFiles = Array.from(event.target.files);
            previewContainer.innerHTML = ''; 

            currentFileObjects = new DataTransfer(); 
            newFiles.forEach(file => currentFileObjects.items.add(file));

            this.files = currentFileObjects.files; 

            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files.length === 1 ? this.files[0].name : `${this.files.length} arquivos selecionados`;

                Array.from(this.files).forEach(file => {
                    if (!file.type.match('image.*')) {
                        console.warn(`Arquivo ${file.name} não é uma imagem.`);
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'image-preview';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Preview de ${file.name}">
                            <button type="button" class="remove-image" data-file-name="${file.name}" aria-label="Remover ${file.name}">×</button>
                        `;
                        previewContainer.appendChild(previewDiv);

                        previewDiv.querySelector('.remove-image').addEventListener('click', function() {
                            const nameToRemove = this.dataset.fileName;

                            const newFilesListAfterRemove = new DataTransfer();
                            for(let i = 0; i < currentFileObjects.items.length; i++){
                                if(currentFileObjects.items[i].getAsFile().name !== nameToRemove){
                                    newFilesListAfterRemove.items.add(currentFileObjects.items[i].getAsFile());
                                }
                            }
                            currentFileObjects = newFilesListAfterRemove;
                            fileInput.files = currentFileObjects.files; 

                            previewDiv.remove(); 
                            updateFileNameDisplay(fileInput, fileNameDisplay);
                        });
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                updateFileNameDisplay(this, fileNameDisplay); 
            }
        });
    }

    function updateFileNameDisplay(fileInput, fileNameDisplayElement) {
        if (fileInput.files.length === 0) {
            fileNameDisplayElement.textContent = 'Nenhum arquivo selecionado';
        } else {
            fileNameDisplayElement.textContent = fileInput.files.length === 1 ? fileInput.files[0].name : `${fileInput.files.length} arquivos selecionados`;
        }
    }

    function setupFormSubmission() {
        setupFormValidator('cidade-form', function(form) {
            const nomeCidade = form.querySelector('#nome-cidade')?.value.trim();
            if (!nomeCidade) {
                alert('Por favor, preencha o campo Nome da Cidade.');
                form.querySelector('#nome-cidade')?.focus();
                return false;
            }
            return true;
        });

        setupFormValidator('ponto-form', function(form) {
            const pontoTuristico = form.querySelector('#ponto-turistico-nome')?.value.trim();
            if (!pontoTuristico) {
                alert('Por favor, preencha o campo Nome do Ponto Turístico.');
                form.querySelector('#ponto-turistico-nome')?.focus();
                return false;
            }
            const idCidade = form.querySelector('#id_cidade-ponto')?.value;
            if (!idCidade) {
                alert('Por favor, selecione a cidade do ponto turístico.');
                form.querySelector('#id_cidade-ponto')?.focus();
                return false;
            }
            const tipoPonto = form.querySelector('#tipo-ponto')?.value;
            if(!tipoPonto){
                alert('Por favor, selecione o tipo do ponto turístico.');
                form.querySelector('#tipo-ponto')?.focus();
                return false;
            }
            return true;
        });

        setupFormValidator('evento-form', function(form) {
            const nomeEvento = form.querySelector('#nome-evento')?.value.trim();
            if (!nomeEvento) {
                alert('Por favor, preencha o campo Nome do Evento.');
                form.querySelector('#nome-evento')?.focus();
                return false;
            }
            const idCidade = form.querySelector('#id_cidade-evento')?.value;
            if (!idCidade) {
                alert('Por favor, selecione a cidade do evento.');
                form.querySelector('#id_cidade-evento')?.focus();
                return false;
            }
            const tipoEvento = form.querySelector('#tipo-evento')?.value;
            if(!tipoEvento){
                alert('Por favor, selecione o tipo do evento.');
                form.querySelector('#tipo-evento')?.focus();
                return false;
            }
            const abertura = form.querySelector('#abertura-evento')?.value;
            const fechamento = form.querySelector('#fechamento-evento')?.value;
            if(!abertura || !fechamento){
                alert('Por favor, preencha os horários de abertura e fechamento do evento.');
                if(!abertura) form.querySelector('#abertura-evento')?.focus();
                else form.querySelector('#fechamento-evento')?.focus();
                return false;
            }
            if(new Date(fechamento) <= new Date(abertura)){
                alert('O horário de fechamento deve ser posterior ao horário de abertura.');
                form.querySelector('#fechamento-evento')?.focus();
                return false;
            }
            const localEvento = form.querySelector('#local-evento')?.value.trim();
            if(!localEvento){
                alert('Por favor, preencha o local do evento.');
                form.querySelector('#local-evento')?.focus();
                return false;
            }
            return true;
        });
    }

    function setupFormValidator(formId, validationFunction) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const serverErrorMessages = document.querySelectorAll('.cadastro-feedback-area .feedback-error, .cadastro-feedback-area .feedback-success, .cadastro-feedback-area .feedback-warning');
            serverErrorMessages.forEach(msg => msg.remove());

            if (!validationFunction(form)) {
                e.preventDefault();
            } else {
                if (formId === 'ponto-form' || formId === 'evento-form') {
                    const imagensInput = form.querySelector(formId === 'ponto-form' ? '#imagens-ponto' : '#imagens-evento');
                    if (imagensInput && imagensInput.files.length === 0 && form.querySelectorAll('.image-preview img').length === 0) {
                        const confirmar = confirm('Nenhuma imagem foi selecionada. Deseja continuar sem imagens?');
                        if (!confirmar) {
                            e.preventDefault();
                        }
                    }
                }
            }
        });
    }

    setupImageUpload();
    setupFormSubmission();

}); 