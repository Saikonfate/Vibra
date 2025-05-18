document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById("sidebar");
    const sidebarToggleBtn = document.querySelector(".user-btn");
    const overlay = document.getElementById('overlay');

    if (sidebarToggleBtn && sidebar) {
        window.toggleAdmSidebar = function() {
            const isVisible = sidebar.classList.toggle("visible");
            if (overlay) overlay.style.display = isVisible ? "block" : "none";
            sidebarToggleBtn.setAttribute('aria-expanded', isVisible.toString());
        };
        sidebarToggleBtn.addEventListener('click', window.toggleAdmSidebar);

        if (overlay) {
            overlay.addEventListener('click', () => {
                if (sidebar.classList.contains("visible")) {
                    sidebar.classList.remove("visible");
                    overlay.style.display = "none";
                    sidebarToggleBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
    }

    const tabButtons = document.querySelectorAll('.tabs .tab-btn');
    const tabContents = document.querySelectorAll('.container > .tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabContents.forEach(content => content.classList.remove('active'));
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');
            const activeContent = document.getElementById(targetTab);
            if (activeContent) activeContent.classList.add('active');
            if (targetTab === 'painel') {
                carregarItensPendentes();
            }
        });
    });

    const tabSecondaryButtons = document.querySelectorAll('#painel .tabs-secondary .tab-secondary-btn');
    const tabSecondaryContents = document.querySelectorAll('#painel .tab-secondary-content');

    tabSecondaryButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabSecondaryButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabSecondaryContents.forEach(content => content.classList.remove('active'));
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');
            const tabId = button.getAttribute('data-tab');
            const activeContent = document.getElementById(tabId);
            if (activeContent) activeContent.classList.add('active');
        });
    });

    const painelTabOnLoad = document.querySelector('.tab-btn[data-tab="painel"]');
    if (painelTabOnLoad && painelTabOnLoad.classList.contains('active')) {
        carregarItensPendentes();
    }

    const painelSearchInput = document.getElementById('painel-search-input');
    if (painelSearchInput) {
        painelSearchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            filtrarTabela(document.getElementById('eventos-pendentes-tbody'), searchTerm);
            filtrarTabela(document.getElementById('pontos-pendentes-tbody'), searchTerm);
        });
    }

    // Lógica de Relatórios
    const tipoRelatorioEl = document.getElementById('tipo-relatorio');
    const datasPersonalizadasEl = document.getElementById('datas-personalizadas');
    const periodoRelatorioEl = document.getElementById('periodo-relatorio');
    const btnExportarRelatorio = document.querySelector('#form-relatorio .btn-exportar');

    if (periodoRelatorioEl && datasPersonalizadasEl) {
        periodoRelatorioEl.addEventListener('change', function() {
            datasPersonalizadasEl.style.display = (this.value === 'custom') ? 'flex' : 'none';
        });
        datasPersonalizadasEl.style.display = 'none';
    }

    if (btnExportarRelatorio && tipoRelatorioEl) { // Checa se tipoRelatorioEl existe
        btnExportarRelatorio.addEventListener('click', function() {
            if (tipoRelatorioEl.value === '') {
                alert('Por favor, selecione um Tipo de Relatório.');
                return;
            }

            const estadoRelatorioVal = document.getElementById('estado-relatorio') ? document.getElementById('estado-relatorio').value : '';
            const periodoRelatorioVal = periodoRelatorioEl ? periodoRelatorioEl.value : '';
            const statusRelatorioVal = document.getElementById('status-relatorio') ? document.getElementById('status-relatorio').value : '';
            const dataInicialVal = document.getElementById('data-inicial') ? document.getElementById('data-inicial').value : '';
            const dataFinalVal = document.getElementById('data-final') ? document.getElementById('data-final').value : '';

            let queryString = `exportar_relatorio.php?action=exportar&tipo_relatorio=${encodeURIComponent(tipoRelatorioEl.value)}`;
            if (estadoRelatorioVal) queryString += `&estado=${encodeURIComponent(estadoRelatorioVal)}`;
            if (periodoRelatorioVal) {
                queryString += `&periodo=${encodeURIComponent(periodoRelatorioVal)}`;
                if (periodoRelatorioVal === 'custom' && dataInicialVal && dataFinalVal) {
                    queryString += `&data_inicial=${encodeURIComponent(dataInicialVal)}`;
                    queryString += `&data_final=${encodeURIComponent(dataFinalVal)}`;
                }
            }
            if (statusRelatorioVal) queryString += `&status=${encodeURIComponent(statusRelatorioVal)}`;
            
            console.log("Exportando com URL: ", queryString);
            window.location.href = queryString;
        });
    }

}); 

function filtrarTabela(tbody, termo) {
    if (!tbody) return;
    const rows = tbody.querySelectorAll('tr');
    let hasVisibleDataRows = false;
    const isCurrentlyEmptyOrLoading = tbody.querySelector('td[colspan="5"].loading-message, td[colspan="5"].empty-message');

    rows.forEach(row => {
        const isMessageRow = row.classList.contains('loading-message') || row.classList.contains('empty-message') || row.classList.contains('no-results-filter-message');
        if (isMessageRow) {
            row.style.display = termo ? 'none' : '';
            return;
        }
        const textContentRow = (row.textContent || row.innerText || "").toLowerCase();
        if (textContentRow.includes(termo)) {
            row.style.display = '';
            hasVisibleDataRows = true;
        } else {
            row.style.display = 'none';
        }
    });

    let noResultsFilterRow = tbody.querySelector('.no-results-filter-message');
    if (termo && !hasVisibleDataRows && !isCurrentlyEmptyOrLoading) {
        if (!noResultsFilterRow) {
            noResultsFilterRow = tbody.insertRow(-1); // Insere no final
            noResultsFilterRow.className = 'no-results-filter-message';
            const cell = noResultsFilterRow.insertCell();
            cell.colSpan = 5;
            cell.style.textAlign = 'center';
            cell.style.padding = '20px';
            cell.textContent = 'Nenhum item encontrado para o termo pesquisado.';
        }
        noResultsFilterRow.style.display = '';
    } else if (noResultsFilterRow) {
        noResultsFilterRow.style.display = 'none';
    }
}

function carregarItensPendentes() {
    const eventosTbody = document.getElementById('eventos-pendentes-tbody');
    const pontosTbody = document.getElementById('pontos-pendentes-tbody');

    if (!eventosTbody || !pontosTbody) {
        console.error("ADM JS: Elementos tbody para pendentes não encontrados.");
        return;
    }

    console.log("ADM JS: Iniciando carregarItensPendentes()...");
    const loadingHTML = '<tr><td colspan="5" class="loading-message" style="text-align:center; padding:20px;">Carregando...</td></tr>';
    eventosTbody.innerHTML = loadingHTML;
    pontosTbody.innerHTML = loadingHTML;

   
    fetch('processa_aprovacao.php?action=listar_pendentes')
        .then(response => {
            console.log("ADM JS: Resposta do fetch status:", response.status, response.statusText);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error("ADM JS: Erro na resposta do servidor (texto):", text);
                    let errorDetail = "Detalhes do erro não puderam ser lidos.";
                    try {
                       
                        const doc = new DOMParser().parseFromString(text, "text/html");
                        const bodyText = doc.body.textContent || "";
                        errorDetail = bodyText.trim().split('\n')[0].substring(0,150); 
                    } catch (e) {
                        errorDetail = text.substring(0, 150) + (text.length > 150 ? "..." : "");
                    }
                    throw new Error(`Erro HTTP ${response.status}: ${response.statusText}. Servidor: ${errorDetail}`);
                });
            }
            return response.json().catch(jsonError => {
                console.error("ADM JS: Erro ao parsear JSON:", jsonError);
                throw new Error("Resposta do servidor não é um JSON válido.");
            });
        })
        .then(data => {
            console.log("ADM JS: Dados JSON recebidos:", data);
            if (data && data.success) {
                renderizarTabelaItens(eventosTbody, data.eventos, 'evento');
                renderizarTabelaItens(pontosTbody, data.pontos, 'ponto');
            } else {
                const errorMsg = data.message || 'Falha ao carregar itens (resposta não bem-sucedida).';
                console.error("ADM JS: Erro nos dados recebidos:", errorMsg);
                const errorHTML = `<tr><td colspan="5" class="error-message" style="text-align:center; color:red; padding:20px;">${errorMsg}</td></tr>`;
                eventosTbody.innerHTML = errorHTML;
                pontosTbody.innerHTML = errorHTML;
            }
        })
        .catch(error => {
            console.error('ADM JS: Exceção/Erro no fetch para listar_pendentes:', error);
            const errorHTML = `<tr><td colspan="5" class="error-message" style="text-align:center; color:red; padding:20px;">Erro de comunicação. Verifique o console. Detalhe: ${error.message}</td></tr>`;
            eventosTbody.innerHTML = errorHTML;
            pontosTbody.innerHTML = errorHTML;
        });
}

function renderizarTabelaItens(tbodyElement, itens, tipoItem) {
    tbodyElement.innerHTML = '';
    const emptyMessageHTML = `<tr class="empty-message"><td colspan="5" style="text-align:center; padding: 20px;">Nenhum item ${tipoItem === 'evento' ? 'de evento' : 'de ponto turístico'} pendente no momento.</td></tr>`;

    if (!itens || itens.length === 0) {
        tbodyElement.innerHTML = emptyMessageHTML;
        return;
    }

    itens.forEach(item => {
        const row = tbodyElement.insertRow();
        row.setAttribute('data-id', item.id);
        row.setAttribute('data-tipo', tipoItem);

        row.insertCell().textContent = item.nome || 'N/D';
        row.insertCell().textContent = item.nome_cidade || 'N/D';

        let terceiraColunaConteudo = 'N/D';
       
        if (tipoItem === 'evento') {
            terceiraColunaConteudo = item.horario_abertura_fmt || item.horario_abertura || 'N/D';
        } else if (tipoItem === 'ponto') {
            terceiraColunaConteudo = item.tipo || 'N/D';
        }
        row.insertCell().textContent = terceiraColunaConteudo;

        row.insertCell().innerHTML = `<span class="status-badge analysis">Pendente</span>`;

        const acoesCell = row.insertCell();
        acoesCell.style.textAlign = "center";
        acoesCell.innerHTML = `
            <button class="action-btn approve-action" title="Aprovar" onclick="window.atualizarStatusItem(${item.id}, '${tipoItem}', 'aprovado', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="action-btn reprove-action" title="Reprovar" onclick="window.atualizarStatusItem(${item.id}, '${tipoItem}', 'reprovado', this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="red" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
    });
}

window.atualizarStatusItem = function(itemId, tipoItem, novoStatus, buttonElement) {
    const row = buttonElement.closest('tr');
    const nomeItem = row && row.cells[0] ? row.cells[0].textContent : 'este item';

    const confirmMessage = novoStatus === 'aprovado' ?
        `Deseja realmente APROVAR o item "${nomeItem}"?` :
        `Deseja realmente REPROVAR o item "${nomeItem}"?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'atualizar_status');
    formData.append('item_id', itemId);
    formData.append('item_tipo', tipoItem);
    formData.append('novo_status', novoStatus);

    const allActionButtonsInRow = row ? row.querySelectorAll('.action-btn') : [];
    allActionButtonsInRow.forEach(btn => btn.disabled = true);

    fetch('processa_aprovacao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
             return response.text().then(text => { throw new Error(`Erro HTTP ${response.status}: ${text || response.statusText}`) });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message); // Exibe a mensagem de sucesso ou erro específico do PHP
            if (row) {
                 
                if (data.message.includes("atualizado para")) { // Verifica se a mensagem é de sucesso real
                    row.style.transition = 'opacity 0.5s ease-out';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Verifica se a tabela ficou vazia
                        const tbody = row.parentNode;
                        if (tbody && tbody.rows.length === 0) {
                            renderizarTabelaItens(tbody, [], tipoItem); // Mostra mensagem de "Nenhum item"
                        }
                    }, 500);
                } else { // Outra mensagem (ex: "Nenhum item pendente...")
                     allActionButtonsInRow.forEach(btn => btn.disabled = false); // Reabilita botões
                }
            }
        } else {
            alert(`Erro do Servidor: ${data.message || 'Não foi possível atualizar o status.'}`);
            allActionButtonsInRow.forEach(btn => btn.disabled = false);
        }
    })
    .catch(error => {
        console.error('ADM JS: Erro ao atualizar status:', error);
        alert('Erro de comunicação ao atualizar status. Detalhe: ' + error.message);
        allActionButtonsInRow.forEach(btn => btn.disabled = false);
    });
};

function gerarRelatorioSimulado() {
    const tipoRelatorioEl = document.getElementById('tipo-relatorio');
    const relatorioResultadoEl = document.getElementById('relatorio-resultado');

    if (!tipoRelatorioEl || !relatorioResultadoEl) {
        console.warn("ADM JS: Elementos do relatório não encontrados.");
        return;
    }
    if (tipoRelatorioEl.value === '') {
        relatorioResultadoEl.innerHTML = '<p style="text-align:center; padding:20px;">Selecione os filtros acima para gerar um relatório.</p>';
        return;
    }
    relatorioResultadoEl.innerHTML = '<div style="text-align: center; padding: 40px;">Gerando relatório simulado... (Lógica de exportação está no PHP)</div>';
    console.log("ADM JS: Gerar relatório com os filtros atuais (simulação de visualização)...");
}