document.addEventListener("DOMContentLoaded", () => {
    // --- Seletores Globais ---
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const userBtn = document.querySelector(".user-btn");
    const eventImageElement = document.getElementById("eventImage");
    const indicatorsContainer = document.getElementById("carouselIndicators");
    const arrowLeft = document.querySelector(".arrow-left");
    const arrowRight = document.querySelector(".arrow-right");
    const starsContainer = document.querySelector('.stars-input');
    const hiddenNotaInput = document.getElementById('nota-avaliacao');
    const formAvaliacao = document.getElementById('form-avaliacao');
    const reviewListContainer = document.getElementById('review-list-container');
    const feedbackDiv = document.getElementById('review-feedback');
    const detailsContainer = document.getElementById('detailsContainer');

    // --- Dados da Página (vindos do HTML) ---
    const eventImages = window.pageData?.eventImages || [];
    const initialImage = window.pageData?.initialImage || '../placeholder_geral.jpg';
    const itemId = window.pageData?.itemId;
    const itemTipo = window.pageData?.itemTipo;
    const idUsuarioLogado = window.pageData?.idUsuarioLogado;

    let currentImageIndex = 0;

    // --- Lógica da Sidebar ---
    function toggleSidebar() {
        if (sidebar && overlay && userBtn) {
            const isVisible = sidebar.classList.toggle("visible");
            userBtn.setAttribute('aria-expanded', isVisible.toString());
            overlay.style.display = isVisible ? "block" : "none";
        }
    }

    function closeSidebar() {
        if (sidebar && overlay && userBtn && sidebar.classList.contains("visible")) {
            sidebar.classList.remove("visible");
            userBtn.setAttribute('aria-expanded', 'false');
            overlay.style.display = "none";
        }
    }

    if (userBtn) {
        userBtn.addEventListener("click", toggleSidebar);
    }
    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    // --- Lógica do Carrossel ---
    function updateImage() {
        if (eventImageElement && eventImages.length > 0) {
            eventImageElement.src = eventImages[currentImageIndex];
            if (indicatorsContainer) updateIndicators();
        } else if (eventImageElement) {
             eventImageElement.src = initialImage;
        }
    }

    function prevImage() {
        if (eventImages.length > 1) {
            currentImageIndex = (currentImageIndex - 1 + eventImages.length) % eventImages.length;
            updateImage();
        }
    }

    function nextImage() {
        if (eventImages.length > 1) {
            currentImageIndex = (currentImageIndex + 1) % eventImages.length;
            updateImage();
        }
    }

    function updateIndicators() {
        if (!indicatorsContainer || eventImages.length <= 1) {
            if (indicatorsContainer) indicatorsContainer.style.display = 'none';
            return;
        }
        indicatorsContainer.style.display = 'flex';
        indicatorsContainer.innerHTML = "";
        eventImages.forEach((_, index) => {
            const dot = document.createElement("div");
            dot.classList.add("dot");
            if (index === currentImageIndex) dot.classList.add("active");
            dot.addEventListener("click", () => {
                currentImageIndex = index;
                updateImage();
            });
            indicatorsContainer.appendChild(dot);
        });
    }

    if (arrowLeft) arrowLeft.addEventListener("click", prevImage);
    if (arrowRight) arrowRight.addEventListener("click", nextImage);

    // Inicialização do Carrossel
    updateImage();
    const arrows = document.querySelectorAll('.arrow');
    if (eventImages.length <= 1) {
        arrows.forEach(arrow => arrow.style.display = 'none');
        if (indicatorsContainer) indicatorsContainer.style.display = 'none';
    } else {
        arrows.forEach(arrow => arrow.style.display = 'flex');
        updateIndicators();
    }


    // --- Lógica das Avaliações (Estrelas) ---
    const stars = starsContainer ? Array.from(starsContainer.querySelectorAll('span')) : [];
    stars.forEach(star => {
        star.addEventListener('mouseover', function () {
            const rating = parseInt(this.dataset.value);
            stars.forEach((s, i) => s.classList.toggle('hovered', i < rating));
        });
        star.addEventListener('mouseout', function () {
            stars.forEach(s => s.classList.remove('hovered'));
            const currentSelectedRating = starsContainer ? parseInt(starsContainer.dataset.rating) : 0;
            stars.forEach((s, i) => s.classList.toggle('selected', i < currentSelectedRating));
        });
        star.addEventListener('click', function () {
            const rating = parseInt(this.dataset.value);
            if (starsContainer) starsContainer.dataset.rating = rating;
            if (hiddenNotaInput) hiddenNotaInput.value = rating;
            stars.forEach((s, i) => s.classList.toggle('selected', i < rating));
        });
    });

    // --- Lógica do Formulário de Avaliação ---
    async function handleReviewSubmit(e) {
        e.preventDefault();
        if (!feedbackDiv || !hiddenNotaInput) return;

        const currentNota = parseInt(hiddenNotaInput.value);
        const isEditing = !!formAvaliacao.querySelector('input[name="avaliacao_id"]');

        if (currentNota === 0 && !isEditing) {
            feedbackDiv.textContent = 'Por favor, selecione uma nota (1 a 5 estrelas).';
            feedbackDiv.className = 'error';
            return;
        }

        feedbackDiv.textContent = 'Enviando...';
        feedbackDiv.className = '';

        const formData = new FormData(formAvaliacao);

        try {
            const response = await fetch('processa_avaliacao.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                feedbackDiv.textContent = result.message;
                feedbackDiv.classList.add('success');
                formAvaliacao.reset();
                if (hiddenNotaInput) hiddenNotaInput.value = 0;
                if (starsContainer) starsContainer.dataset.rating = 0;
                stars.forEach(s => s.classList.remove('selected'));

                const hiddenAvaliacaoIdField = formAvaliacao.querySelector('input[name="avaliacao_id"]');
                if (hiddenAvaliacaoIdField) hiddenAvaliacaoIdField.remove();

                const formTitle = formAvaliacao.querySelector('p');
                if (formTitle) formTitle.textContent = 'Deixe sua avaliação:';

                await carregarAvaliacoes(); // Recarrega a lista
                if (result.new_average_rating !== undefined && result.new_total_ratings !== undefined) {
                    updateAverageRatingDisplay(result.new_average_rating, result.new_total_ratings);
                }
            } else {
                feedbackDiv.textContent = result.message || 'Erro ao enviar avaliação.';
                feedbackDiv.classList.add('error');
            }
        } catch (error) {
            console.error('Erro:', error);
            feedbackDiv.textContent = 'Erro de comunicação ao enviar avaliação.';
            feedbackDiv.classList.add('error');
        }
    }

    if (formAvaliacao) {
        formAvaliacao.addEventListener('submit', handleReviewSubmit);
    }

    // --- Funções de Carregamento, Edição e Deleção de Avaliações ---
    async function carregarAvaliacoes() {
        if (!reviewListContainer || !itemId || !itemTipo) {
             if(reviewListContainer) reviewListContainer.innerHTML = '<p class="no-reviews" style="color:red;">Erro interno ao carregar avaliações.</p>';
            return;
        }

        reviewListContainer.innerHTML = '<p class="no-reviews">Carregando avaliações...</p>';

        try {
            const response = await fetch(`processa_avaliacao.php?action=carregar_avaliacoes&item_id=${itemId}&item_tipo=${itemTipo}`);
            const result = await response.json();
            reviewListContainer.innerHTML = '';

            if (result.success && result.avaliacoes && result.avaliacoes.length > 0) {
                result.avaliacoes.forEach(aval => {
                    const reviewDiv = document.createElement('div');
                    reviewDiv.className = 'review';
                    reviewDiv.setAttribute('data-avaliacao-id', aval.avaliacao_id);

                    let starsHTML = Array(5).fill(0).map((_, i) =>
                        `<span class="star-display">${i < aval.nota ? '★' : '☆'}</span>`
                    ).join('');

                    let actionsHTML = '';
                    if (idUsuarioLogado && parseInt(aval.id_usuario) === parseInt(idUsuarioLogado)) {
                         const sanitizedComment = aval.comentario ? aval.comentario.replace(/'/g, "\\'").replace(/\n/g, "\\n") : '';
                         actionsHTML = `
                            <div class="review-actions">
                                <button class="btn-edit-review" data-id="${aval.avaliacao_id}" data-nota="${aval.nota}" data-comentario="${sanitizedComment}">Editar</button>
                                <button class="btn-delete-review" data-id="${aval.avaliacao_id}">Deletar</button>
                            </div>
                         `;
                    }

                    reviewDiv.innerHTML = `
                        <div class="review-header">
                            <span class="review-user">${aval.nome_usuario || 'Anônimo'}</span>
                            <span class="review-date">${aval.data_formatada || ''}</span>
                        </div>
                        <div class="review-rating">${starsHTML}</div>
                        <p class="review-text">${aval.comentario ? aval.comentario.replace(/\n/g, '<br>') : '<em>Sem comentário.</em>'}</p>
                        ${actionsHTML}
                    `;
                    reviewListContainer.appendChild(reviewDiv);
                });
            } else {
                reviewListContainer.innerHTML = '<p class="no-reviews">Ainda não há avaliações. Seja o primeiro!</p>';
            }
        } catch (error) {
            console.error('Erro ao carregar avaliações:', error);
            reviewListContainer.innerHTML = '<p class="no-reviews" style="color:red;">Erro ao carregar avaliações.</p>';
        }
    }

    function preencherFormularioParaEdicao(avaliacaoId, notaAtual, comentarioAtual) {
        if (!formAvaliacao || !hiddenNotaInput || !starsContainer) return;

        const formTitle = formAvaliacao.querySelector('p');
        const textareaComentario = formAvaliacao.querySelector('textarea[name="comentario"]');

        if (formTitle) formTitle.textContent = 'Editando sua avaliação:';

        let hiddenAvaliacaoIdField = formAvaliacao.querySelector('input[name="avaliacao_id"]');
        if (!hiddenAvaliacaoIdField) {
            hiddenAvaliacaoIdField = document.createElement('input');
            hiddenAvaliacaoIdField.type = 'hidden';
            hiddenAvaliacaoIdField.name = 'avaliacao_id';
            formAvaliacao.appendChild(hiddenAvaliacaoIdField);
        }
        hiddenAvaliacaoIdField.value = avaliacaoId;

        hiddenNotaInput.value = notaAtual;
        starsContainer.dataset.rating = notaAtual;
        Array.from(starsContainer.querySelectorAll('span')).forEach((star, index) => {
            star.classList.toggle('selected', index < notaAtual);
        });

        if (textareaComentario) textareaComentario.value = comentarioAtual.replace(/\\n/g, '\n');

        formAvaliacao.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (textareaComentario) textareaComentario.focus();
    }

    async function deletarAvaliacao(avaliacaoId) {
        if (!confirm('Tem certeza que deseja deletar sua avaliação?')) return;
        if (!feedbackDiv) return;

        feedbackDiv.textContent = 'Deletando...';
        feedbackDiv.className = '';

        const formData = new FormData();
        formData.append('action', 'deletar_avaliacao');
        formData.append('avaliacao_id', avaliacaoId);
        formData.append('item_id', itemId);
        formData.append('item_tipo', itemTipo);

        try {
            const response = await fetch('processa_avaliacao.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                feedbackDiv.textContent = result.message;
                feedbackDiv.classList.add('success');
                await carregarAvaliacoes();
                if (result.new_average_rating !== undefined && result.new_total_ratings !== undefined) {
                    updateAverageRatingDisplay(result.new_average_rating, result.new_total_ratings);
                }
            } else {
                feedbackDiv.textContent = result.message || 'Erro ao deletar.';
                feedbackDiv.classList.add('error');
            }
        } catch (error) {
            console.error('Erro:', error);
            feedbackDiv.textContent = 'Erro de comunicação ao deletar.';
            feedbackDiv.classList.add('error');
        }
    }

    // Adiciona event listeners para os botões de editar/deletar (usando delegação)
    if (reviewListContainer) {
        reviewListContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('btn-edit-review')) {
                const button = event.target;
                preencherFormularioParaEdicao(
                    button.dataset.id,
                    parseInt(button.dataset.nota),
                    button.dataset.comentario
                );
            }
            if (event.target.classList.contains('btn-delete-review')) {
                deletarAvaliacao(event.target.dataset.id);
            }
        });
    }


    function updateAverageRatingDisplay(newAverage, newTotal) {
        const avgDisplayContainer = document.querySelector('.average-rating-display');
        if (avgDisplayContainer) {
            if (newTotal > 0) {
                const roundedAvg = Math.round(newAverage);
                let starsHTML = Array(5).fill(0).map((_, i) => i < roundedAvg ? '★' : '☆').join('');
                avgDisplayContainer.innerHTML = `
                    Média: <span class="stars-avg">${starsHTML}</span>
                    <strong>${parseFloat(newAverage).toFixed(1)}</strong>
                    <span class="rating-count-display">(${newTotal} ${newTotal === 1 ? 'avaliação' : 'avaliações'})</span>
                `;
            } else {
                avgDisplayContainer.innerHTML = '<span>Este item ainda não possui avaliações.</span>';
            }
        }
    }

    // --- Inicialização das Avaliações ---
    if (itemId) {
        carregarAvaliacoes();
    }
});
