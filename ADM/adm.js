document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------
    // FORMATAR DATAS
    // -------------------------------
    function formatDateToDDMMYYYY(input) {
        const value = input.value;
        if (!value) return;
        const [year, month, day] = value.split("-");
        input.setAttribute("data-date", `${day}-${month}-${year}`);
        input.valueAsDate = new Date(value);
    }

    const dateInputs = document.querySelectorAll(".custom-date");
    dateInputs.forEach(input => {
        input.addEventListener("change", () => formatDateToDDMMYYYY(input));
        formatDateToDDMMYYYY(input);
    });

    function getTodayDateString() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function updateMinDate() {
        const todayStr = getTodayDateString();
        const date2Input = document.getElementById("date2");
        if (date2Input) {
            date2Input.setAttribute("min", todayStr);
        }
    }

    updateMinDate();
    setInterval(updateMinDate, 86400000);

    function setInitialDates() {
        const today = getTodayDateString();
        const input1 = document.getElementById("date1");
        const input2 = document.getElementById("date2");

        if (input1) input1.value = today;
        if (input2) input2.value = today;

        if (input1) formatDateToDDMMYYYY(input1);
        if (input2) formatDateToDDMMYYYY(input2);
    }

    setInitialDates();

    // -------------------------------
    // SIDEBAR
    // -------------------------------
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        if (sidebar) {
            sidebar.classList.toggle("visible");
        }
    }

    const sidebarToggleBtn = document.querySelector(".user-btn");
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }

    window.addEventListener("click", function (event) {
        const sidebar = document.getElementById("sidebar");
        const button = document.querySelector(".user-btn");
        if (sidebar && button &&
            !sidebar.contains(event.target) &&
            !button.contains(event.target)) {
            sidebar.classList.remove("visible");
        }
    });

    // -------------------------------
    // MENU CATEGORIA
    // -------------------------------
    const btnCategoria = document.getElementById('btnCategoria');
    const menuCategoria = document.getElementById('menuCategoria');
    const overlay = document.getElementById('overlay');

    if (btnCategoria && menuCategoria && overlay) {
        btnCategoria.addEventListener('click', () => {
            menuCategoria.style.display = 'grid';
            overlay.style.display = 'block';
        });

        overlay.addEventListener('click', () => {
            menuCategoria.style.display = 'none';
            overlay.style.display = 'none';
        });
    }

    // -------------------------------
    // EDIÇÃO DE CAMPOS
    // -------------------------------
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const formGroup = button.closest('.form-group');
            const input = formGroup.querySelector('.form-input');
            const currentValue = input.value;

            if (!input.classList.contains('editable')) {
                input.classList.add('editable');
                input.removeAttribute('readonly');
                input.focus();
                button.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                `;
            } else {
                input.classList.remove('editable');
                input.setAttribute('readonly', true);
                button.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                `;

                console.log(`Updated ${input.previousElementSibling.textContent}: ${input.value}`);

                if (input.type === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        alert('Por favor, insira um email válido');
                        input.value = currentValue;
                    }
                }

                if (input.type === 'tel') {
                    const phoneRegex = /^\d{10,11}$/;
                    if (!phoneRegex.test(input.value.replace(/\D/g, ''))) {
                        alert('Por favor, insira um número de telefone válido');
                        input.value = currentValue;
                    }
                }
            }
        });
    });

    // -------------------------------
    // ALTERAÇÃO DE SENHA
    // -------------------------------
    const passwordLink = document.querySelector('.password-link');
    if (passwordLink) {
        passwordLink.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Funcionalidade de alteração de senha será implementada');
        });
    }

    // -------------------------------
    // MÁSCARA DE TELEFONE
    // -------------------------------
    function maskPhone(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);

        if (value.length <= 10) {
            value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
        } else {
            value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        }

        input.value = value;
    }

    const phoneInput = document.querySelector('input[type="tel"][value="**********"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', () => maskPhone(phoneInput));
    }

    // -------------------------------
    // NAVEGAÇÃO ENTRE ABAS
    // -------------------------------
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });

    const tabSecondaryButtons = document.querySelectorAll('.tab-secondary-btn');
    const tabSecondaryContents = document.querySelectorAll('.tab-secondary-content');

    tabSecondaryButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabSecondaryButtons.forEach(btn => btn.classList.remove('active'));
            tabSecondaryContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // -------------------------------
    // AÇÕES: EDITAR, APROVAR, EXCLUIR
    // -------------------------------
    const editActions = document.querySelectorAll('.edit-action');
    const approveActions = document.querySelectorAll('.approve-action');
    const deleteActions = document.querySelectorAll('.delete-action');

    editActions.forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            alert(`Editar ${name}`);
        });
    });

    approveActions.forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            if (confirm(`Deseja aprovar ${name}?`)) {
                alert(`${name} aprovado com sucesso!`);
            }
        });
    });

    deleteActions.forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            if (confirm(`Tem certeza que deseja excluir ${name}?`)) {
                row.remove();
                alert(`${name} excluído com sucesso!`);
            }
        });
    });

    // -------------------------------
    // PESQUISA NA TABELA
    // -------------------------------
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const activeTab = document.querySelector('.tab-secondary-content.active');
            const rows = activeTab.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // -------------------------------
    // PAGINAÇÃO
    // -------------------------------
    const prevButtons = document.querySelectorAll('.pagination-btn.prev');
    const nextButtons = document.querySelectorAll('.pagination-btn.next');

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            alert('Página anterior');
        });
    });

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            alert('Próxima página');
        });
    });

    // -------------------------------
    // FORMULÁRIO DE PONTOS TURÍSTICOS
    // -------------------------------
     const pontoForm = document.getElementById('ponto-form');
    if (pontoForm) {
        pontoForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const nomeCidadePonto = document.getElementById('nome-cidade-ponto').value;
            const pontoTuristico = document.getElementById('ponto-turistico').value;
            const estadoPonto = document.getElementById('estado-ponto').value;
            const categoriaSelect = document.getElementById('categoria-ponto');
            const categoriasArray = Array.from(categoriaSelect.selectedOptions).map(option => option.value);

            if (!nomeCidadePonto || !pontoTuristico || !estadoPonto) {
                alert('Por favor, preencha os campos obrigatórios: Nome da Cidade, Ponto Turístico e Estado.');
                return;
            }

            console.log('Ponto turístico cadastrado:', {
                cidade: nomeCidadePonto,
                pontTuristico: pontoTuristico,
                estado: estadoPonto,
                descricao: document.getElementById('descricao-ponto').value,
                endereco: document.getElementById('endereco-ponto').value,
                categoria: categoriasArray,
                horario: document.getElementById('horario-ponto').value,
                preco: document.getElementById('preco-ponto').value,
                arquivos: document.getElementById('imagens-ponto').files
            });

            alert(`Ponto turístico ${pontoTuristico} cadastrado com sucesso!`);
            pontoForm.reset();
            document.querySelector('.file-name').textContent = 'Nenhum arquivo selecionado';
        });
    }
});
function setupImageUpload() {
    const fileInput = document.getElementById('imagens-ponto');
    const fileName = document.querySelector('.file-name');
    const previewContainer = document.getElementById('preview-container');
    
    if (!fileInput || !fileName || !previewContainer) return;
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            if (this.files.length === 1) {
                fileName.textContent = this.files[0].name;
            } else {
                fileName.textContent = `${this.files.length} arquivos selecionados`;
            }
            
            previewContainer.innerHTML = '';
            
            Array.from(this.files).forEach((file, index) => {
                if (!file.type.match('image.*')) return;
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'image-preview';
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <span class="remove-image" data-index="${index}">×</span>
                    `;
                    previewContainer.appendChild(previewDiv);
                    
                    previewDiv.querySelector('.remove-image').addEventListener('click', function() {
                        previewDiv.remove();
                        updateFileInputAfterRemoval(fileInput);
                    });
                };
                
                reader.readAsDataURL(file);
            });
        } else {
            fileName.textContent = 'Nenhum arquivo selecionado';
            previewContainer.innerHTML = '';
        }
    });
}

function updateFileInputAfterRemoval(fileInput) {
    const previewImagens = document.querySelectorAll('.image-preview');
    const fileName = document.querySelector('.file-name');
    
    if (previewImagens.length === 0) {
        fileInput.value = '';
        fileName.textContent = 'Nenhum arquivo selecionado';
    } else {
        fileName.textContent = `${previewImagens.length} arquivos mantidos`;
    }
}

function setupFormSubmission() {
    const pontoForm = document.getElementById('ponto-form');
    
    if (!pontoForm) return;
    
    pontoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nomeCidadePonto = document.getElementById('nome-cidade-ponto').value;
        const pontoTuristico = document.getElementById('ponto-turistico').value;
        const estadoPonto = document.getElementById('estado-ponto').value;
        
        if (!nomeCidadePonto || !pontoTuristico || !estadoPonto) {
            alert('Por favor, preencha os campos obrigatórios: Nome da Cidade, Ponto Turístico e Estado.');
            return;
        }
        
        const imagens = document.getElementById('imagens-ponto').files;
        
        if (imagens.length === 0 && document.querySelectorAll('.image-preview').length === 0) {
            const confirmar = confirm('Nenhuma imagem foi selecionada. Deseja continuar sem imagens?');
            if (!confirmar) return;
        }
        
        const formData = new FormData(pontoForm);
        
        console.log('Ponto turístico cadastrado com as seguintes imagens:');
        for (let i = 0; i < imagens.length; i++) {
            console.log(`- ${imagens[i].name} (${Math.round(imagens[i].size / 1024)} KB)`);
        }
        
        alert(`Ponto turístico ${pontoTuristico} cadastrado com sucesso!`);
        pontoForm.reset();
        document.querySelector('.file-name').textContent = 'Nenhum arquivo selecionado';
        document.getElementById('preview-container').innerHTML = '';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setupImageUpload();
    setupFormSubmission();
});
const periodoSelect = document.getElementById('periodo-relatorio');
const datasPersonalizadas = document.getElementById('datas-personalizadas');

periodoSelect.addEventListener('change', function() {
    if (this.value === 'custom') {
        datasPersonalizadas.style.display = 'block';
    } else {
        datasPersonalizadas.style.display = 'none';
    }
});


const today = new Date();
const lastMonth = new Date();
lastMonth.setMonth(lastMonth.getMonth() - 1);
            
document.getElementById('data-final').valueAsDate = today;
document.getElementById('data-inicial').valueAsDate = lastMonth;

            // -------------------------------
            // GERAR RELATÓRIO 
            // -------------------------------
            const tipoRelatorio = document.getElementById('tipo-relatorio');
            const estadoRelatorio = document.getElementById('estado-relatorio');
            const statusRelatorio = document.getElementById('status-relatorio');
            const btnExportar = document.querySelector('.btn-exportar');
            const relatorioResultado = document.getElementById('relatorio-resultado');


            [tipoRelatorio, estadoRelatorio, periodoSelect, statusRelatorio].forEach(filter => {
                filter.addEventListener('change', gerarRelatorio);
            });

            // Evento para o botão de exportar
            btnExportar.addEventListener('click', function() {
                if (tipoRelatorio.value === '') {
                    alert('Por favor, selecione um tipo de relatório antes de exportar.');
                    return;
                }
                alert('Relatório exportado com sucesso!');
            });

            function gerarRelatorio() {
                if (tipoRelatorio.value === '') {
                    relatorioResultado.innerHTML = 'Selecione os filtros acima para gerar um relatório';
                    return;
                }

                relatorioResultado.innerHTML = '<div style="text-align: center; padding: 40px;">Carregando relatório...</div>';

                setTimeout(() => {
                    let htmlConteudo = '';
                    
                    if (tipoRelatorio.value === 'cidades') {
                        htmlConteudo = `
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Nome da Cidade</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Estado</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Pontos Cadastrados</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Status</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Data de Cadastro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Juazeiro do Norte</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">CE</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">4</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #fff8e6; color: #ffa000;">Em Análise</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">10/05/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">RJ</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">12</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #fff8e6; color: #ffa000;">Em Análise</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">18/04/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">São Paulo</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">SP</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">8</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #e6fff0; color: #23d160;">Aprovado</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">05/04/2025</td>
                                    </tr>
                                </tbody>
                            </table>
                        `;
                    } else if (tipoRelatorio.value === 'pontos') {
                        htmlConteudo = `
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Nome do Ponto</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Cidade</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Categoria</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Status</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Data de Cadastro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Padre Cícero</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Juazeiro do Norte</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Monumento</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #fff8e6; color: #ffa000;">Em Análise</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">10/05/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Cristo Redentor</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Monumento</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #e6fff0; color: #23d160;">Aprovado</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">15/03/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Praia de Copacabana</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Praia</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #e6fff0; color: #23d160;">Aprovado</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">18/02/2025</td>
                                    </tr>
                                </tbody>
                            </table>
                        `;
                    } else if (tipoRelatorio.value === 'eventos') {
                        htmlConteudo = `
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Nome do Evento</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Cidade</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Data do Evento</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Status</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Data de Cadastro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Festival de Inverno</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Juazeiro do Norte</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">15/07/2025</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #fff8e6; color: #ffa000;">Em Análise</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">05/05/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Carnaval 2026</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">28/02/2026</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background-color: #e6fff0; color: #23d160;">Aprovado</span></td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">10/04/2025</td>
                                    </tr>
                                </tbody>
                            </table>
                        `;
                    } else if (tipoRelatorio.value === 'visitas') {
                        htmlConteudo = `
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Ponto Turístico</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Cidade</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Número de Visitas</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Avaliação Média</th>
                                        <th style="padding: 12px 16px; background-color: #f7f7f7; font-weight: 500; text-align: left; border-bottom: 1px solid #e0e0e0;">Período</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Padre Cícero</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Juazeiro do Norte</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">325</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">4.7 ⭐</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Abr-Mai/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Cristo Redentor</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">1245</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">4.9 ⭐</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Abr-Mai/2025</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Praia de Copacabana</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Rio de Janeiro</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">2187</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">4.5 ⭐</td>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">Abr-Mai/2025</td>
                                    </tr>
                                </tbody>
                            </table>
                        `;
                    }

                    // Adiciona o resumo do relatório
                    const periodoTexto = getPeriodoTexto();
                    const estadoTexto = estadoRelatorio.value ? `Estado: ${estadoRelatorio.options[estadoRelatorio.selectedIndex].text}` : '';
                    const statusTexto = statusRelatorio.value ? `Status: ${statusRelatorio.options[statusRelatorio.selectedIndex].text}` : '';
                    
                    const resumoRelatorio = `
                        <div class="resumo-relatorio" style="margin-bottom: 24px; padding: 16px; background-color: #f5f5f5; border-radius: 8px;">
                            <h3 style="margin-top: 0; font-size: 16px; font-weight: 600; color: #333;">Resumo do Relatório</h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                                <div style="flex: 1;">
                                    <p style="margin: 0; font-size: 14px;"><strong>Tipo:</strong> ${tipoRelatorio.options[tipoRelatorio.selectedIndex].text}</p>
                                    <p style="margin: 4px 0 0 0; font-size: 14px;"><strong>Período:</strong> ${periodoTexto}</p>
                                </div>
                                <div style="flex: 1;">
                                    <p style="margin: 0; font-size: 14px;">${estadoTexto}</p>
                                    <p style="margin: 4px 0 0 0; font-size: 14px;">${statusTexto}</p>
                                </div>
                            </div>
                        </div>
                    `;

                    // Verifica se o filtro de estado foi aplicado
                    const aplicarFiltroEstado = estadoRelatorio.value !== '';
                    
                    // Verifica se o filtro de status foi aplicado
                    const aplicarFiltroStatus = statusRelatorio.value !== '';
                    
                    // Adiciona uma mensagem se não houver dados após a filtragem
                    if ((aplicarFiltroEstado || aplicarFiltroStatus) && (estadoRelatorio.value === 'TO' || statusRelatorio.value === 'reprovado')) {
                        htmlConteudo = `
                            <div style="text-align: center; padding: 40px; background-color: #f5f5f5; border-radius: 8px;">
                                <p style="margin: 0; font-size: 16px; color: #666;">Nenhum dado encontrado para os filtros selecionados.</p>
                            </div>
                        `;
                    }

                    // Monta o resultado final do relatório
                    const conteudoFinal = resumoRelatorio + htmlConteudo;
                    
                    // Atualiza o conteúdo do relatório
                    relatorioResultado.innerHTML = conteudoFinal;

                    // Adiciona botões de controle ao fim da tabela se houver dados
                    if (!(aplicarFiltroEstado || aplicarFiltroStatus) || (estadoRelatorio.value !== 'TO' && statusRelatorio.value !== 'reprovado')) {
                        adicionarControlesPaginacao();
                    }
                }, 800); // Simula um delay para mostrar o carregamento
            }

            // Função para formatar o texto do período selecionado
            function getPeriodoTexto() {
                const periodo = periodoSelect.value;
                
                if (periodo === 'custom') {
                    const dataInicial = document.getElementById('data-inicial').value;
                    const dataFinal = document.getElementById('data-final').value;
                    return `${formatarData(dataInicial)} até ${formatarData(dataFinal)}`;
                } else if (periodo === '7dias') {
                    return 'Últimos 7 dias';
                } else if (periodo === '30dias') {
                    return 'Últimos 30 dias';
                } else if (periodo === '90dias') {
                    return 'Últimos 90 dias';
                } else {
                    return 'Todo o período';
                }
            }

            // Função para formatar a data no padrão brasileiro
            function formatarData(dataStr) {
                if (!dataStr) return '';
                
                const partes = dataStr.split('-');
                if (partes.length !== 3) return dataStr;
                
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }

            // Função para adicionar controles de paginação
            function adicionarControlesPaginacao() {
                const paginacao = document.createElement('div');
                paginacao.className = 'paginacao';
                paginacao.style.display = 'flex';
                paginacao.style.justifyContent = 'space-between';
                paginacao.style.alignItems = 'center';
                paginacao.style.marginTop = '24px';
                paginacao.style.padding = '12px 0';
                
                const infoRegistros = document.createElement('div');
                infoRegistros.className = 'info-registros';
                infoRegistros.style.fontSize = '14px';
                infoRegistros.style.color = '#666';
                infoRegistros.textContent = 'Exibindo 1-3 de 3 registros';
                
                const controles = document.createElement('div');
                controles.className = 'controles';
                controles.style.display = 'flex';
                controles.style.gap = '8px';
                
                // Botões de paginação
                const btnAnterior = criarBotaoPaginacao('Anterior', true);
                const btnProximo = criarBotaoPaginacao('Próximo', true);
                
                controles.appendChild(btnAnterior);
                controles.appendChild(btnProximo);
                
                paginacao.appendChild(infoRegistros);
                paginacao.appendChild(controles);
                
                relatorioResultado.appendChild(paginacao);
            }

            // Função para criar botões de paginação
            function criarBotaoPaginacao(texto, desabilitado) {
                const botao = document.createElement('button');
                botao.className = 'btn-paginacao';
                botao.style.padding = '8px 16px';
                botao.style.backgroundColor = desabilitado ? '#f5f5f5' : '#007bff';
                botao.style.color = desabilitado ? '#999' : '#fff';
                botao.style.border = 'none';
                botao.style.borderRadius = '4px';
                botao.style.cursor = desabilitado ? 'default' : 'pointer';
                botao.textContent = texto;
                botao.disabled = desabilitado;
                
                return botao;
            }

            // Inicializa a interface escondendo o seletor de datas personalizadas
            datasPersonalizadas.style.display = 'none';

            // Adiciona eventos para os campos de data personalizada
            document.getElementById('data-inicial').addEventListener('change', gerarRelatorio);
            document.getElementById('data-final').addEventListener('change', gerarRelatorio);

            // Executa a função de gerar relatório se já houver um tipo selecionado ao carregar a página
            if (tipoRelatorio.value !== '') {
                gerarRelatorio();
            }