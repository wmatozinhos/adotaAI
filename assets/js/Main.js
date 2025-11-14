
// AdotaAi - Sistema de Adoção de Pets
// main.js - Funções principais do sistema
// Este arquivo contém as funções principais do sistema AdotaAi, incluindo validações de formulários, pré-visualizações de imagens, filtros de pets, notificações, confirmações de exclusão e gráficos estatísticos.
// O código é modularizado para facilitar a manutenção e a legibilidade, utilizando eventos do DOM para inicializar funcionalidades após o carregamento da página.
// O sistema é desenvolvido com JavaScript puro e utiliza bibliotecas como Bootstrap e Chart.js para melhorar a experiência do usuário.
// O código é otimizado para garantir que as funcionalidades sejam carregadas de forma eficiente e responsiva, proporcionando uma experiência fluida para o usuário.
// O sistema é projetado para ser escalável, permitindo a adição de novas funcionalidades e melhorias no futuro.
// O código é comentado de forma clara e concisa, explicando cada parte da funcionalidade implementada.
// O sistema é testado em diferentes navegadores e dispositivos para garantir a compatibilidade e a responsividade.
// O código é estruturado para seguir as melhores práticas de desenvolvimento, incluindo a separação de responsabilidades e a reutilização de código.
/**
 * AdotaAi - Sistema de Adoção de Pets
 * main.js - Funções principais do sistema
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Inicializar popovers do Bootstrap
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Manipuladores de eventos para funcionalidades do site
    setupFormValidations();
    setupImagePreviews();
    setupPetFilters();
    setupNotifications();
    setupDeleteConfirmations();
    setupCharts();
});

/**
 * Configuração de validações de formulários
 */
function setupFormValidations() {
    // Seleciona todos os formulários que precisam de validação
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop sobre os formulários e previne envio se inválidos
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Validações personalizadas
    const passwordInputs = document.querySelectorAll('.validate-password');
    passwordInputs.forEach(input => {
        input.addEventListener('input', validatePassword);
    });

    // Validação de confirmação de senha
    const confirmPasswordInputs = document.querySelectorAll('.confirm-password');
    confirmPasswordInputs.forEach(input => {
        input.addEventListener('input', validatePasswordMatch);
    });
}

/**
 * Valida complexidade da senha
 */
function validatePassword(e) {
    const password = e.target.value;
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    const valid = password.length >= minLength && 
                hasUpperCase && 
                hasLowerCase && 
                hasNumbers && 
                hasSpecialChar;
    
    const feedback = e.target.nextElementSibling;
    if (feedback && feedback.classList.contains('password-feedback')) {
        if (!valid) {
            feedback.innerHTML = `A senha deve ter no mínimo ${minLength} caracteres e incluir:<br>
                                - Uma letra maiúscula<br>
                                - Uma letra minúscula<br> - Um número<br>
                                - Um caractere especial`;
            feedback.classList.add('text-danger');
            feedback.classList.remove('text-success');
            e.target.setCustomValidity('Senha inválida');
        } else {
            feedback.innerHTML = 'Senha adequada!';
            feedback.classList.remove('text-danger');
            feedback.classList.add('text-success');
            e.target.setCustomValidity('');
        }
    }
}

/**
 * Valida se as senhas são iguais
 */
function validatePasswordMatch(e) {
    const confirmPassword = e.target.value;
    const passwordInput = document.querySelector('.validate-password');
    
    // Verificar se o elemento de senha existe antes de acessar o valor
    if (!passwordInput) return;
    
    const password = passwordInput.value;
    const valid = confirmPassword === password;
    const feedback = e.target.nextElementSibling;
    
    if (feedback && feedback.classList.contains('password-match-feedback')) {
        if (!valid) {
            feedback.textContent = 'As senhas não coincidem';
            feedback.classList.add('text-danger');
            feedback.classList.remove('text-success');
            e.target.setCustomValidity('Senhas não coincidem');
        } else {
            feedback.textContent = 'Senhas iguais!';
            feedback.classList.remove('text-danger');
            feedback.classList.add('text-success');
            e.target.setCustomValidity('');
        }
    }
}

/**
 * Configura previsualizações de imagens para uploads
 */
function setupImagePreviews() {
    const fileInputs = document.querySelectorAll('.image-upload');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file) return;
            
            const container = this.closest('.image-upload-container');
            if (!container) return;
            
            const preview = container.querySelector('.image-preview');
            const previewText = container.querySelector('.preview-text');
            
            if (!preview) return;
            
            if (!file.type.match('image.*')) {
                alert('Por favor, selecione uma imagem válida.');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                } else {
                    // Limpa o conteúdo anterior
                    while (preview.firstChild) {
                        preview.removeChild(preview.firstChild);
                    }
                    
                    // Cria a imagem
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('img-fluid');
                    preview.appendChild(img);
                }
                
                // Esconde o texto de preview se existir
                if (previewText) {
                    previewText.style.display = 'none';
                }
                
                // Adiciona botão de remover se não existir
                if (!preview.querySelector('.remove-image')) {
                    const removeBtn = document.createElement('span');
                    removeBtn.classList.add('remove-image');
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.addEventListener('click', function() {
                        input.value = '';
                        if (preview.tagName === 'IMG') {
                            preview.src = '';
                            preview.style.display = 'none';
                        } else {
                            while (preview.firstChild) {
                                preview.removeChild(preview.firstChild);
                            }
                        }
                        if (previewText) {
                            previewText.style.display = 'block';
                        }
                        this.remove();
                    });
                    preview.appendChild(removeBtn);
                }
            };
            
            reader.readAsDataURL(file);
        });
    });
    
    // Para múltiplos uploads (galeria de imagens do pet)
    const multipleFileInputs = document.querySelectorAll('.multiple-images-upload');
    
    multipleFileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const files = this.files;
            const formGroup = this.closest('.form-group');
            if (!formGroup) return;
            
            const previewContainer = formGroup.querySelector('.multiple-images-preview');
            if (!files || !previewContainer) return;
            
            // Limitar número máximo de arquivos
            const maxFiles = 5;
            if (files.length > maxFiles) {
                alert(`Você pode enviar no máximo ${maxFiles} imagens.`);
                this.value = '';
                return;
            }
            
            // Limpar previsualizações anteriores
            previewContainer.innerHTML = '';
            
            // Array para manter controle de arquivos inválidos
            const invalidFiles = [];
            
            for (let i = 0; i < files.length; i++) {
                if (!files[i].type.match('image.*')) {
                    invalidFiles.push(files[i].name);
                    continue;
                }
                
                const reader = new FileReader();
                const preview = document.createElement('div');
                preview.classList.add('col-md-4', 'mb-3');
                
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="image-preview">
                            <img src="${e.target.result}" alt="Preview" class="img-fluid">
                            <span class="remove-image"><i class="fas fa-times"></i></span>
                        </div>
                        <div class="mt-2">
                            <div class="form-check">
                                <input class="form-check-input main-image-radio" type="radio" name="imagem_principal" value="${i}" ${i === 0 ? 'checked' : ''}>
                                <label class="form-check-label">Imagem principal</label>
                            </div>
                        </div>
                    `;
                    
                    // Adicionar manipulador para remover imagem
                    const removeBtn = preview.querySelector('.remove-image');
                    removeBtn.addEventListener('click', function() {
                        preview.remove();
                        
                        // Se não houver mais previews, limpar o input file
                        if (previewContainer.children.length === 0) {
                            input.value = '';
                        }
                    });
                };
                
                reader.readAsDataURL(files[i]);
                previewContainer.appendChild(preview);
            }
            
            // Avisar sobre arquivos inválidos
            if (invalidFiles.length > 0) {
                alert(`Os seguintes arquivos não são imagens válidas: ${invalidFiles.join(', ')}`);
                if (invalidFiles.length === files.length) {
                    // Se todos os arquivos forem inválidos, limpar o input
                    this.value = '';
                    previewContainer.innerHTML = '';
                }
            }
        });
    });
}

/**
 * Configurar filtros para busca de pets
 */
function setupPetFilters() {
    const filterForm = document.getElementById('petFilterForm');
    if (!filterForm) return;
    
    // Limpar filtros
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const inputs = filterForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            
            // Se estiver usando sliders para faixa de idade ou peso
            const sliders = filterForm.querySelectorAll('.range-slider');
            sliders.forEach(slider => {
                if (slider.noUiSlider) {
                    slider.noUiSlider.reset();
                }
            });
            
            // Submeter o formulário para atualizar resultados
            filterForm.submit();
        });
    }
    
    // Inicializar sliders de faixa se existirem (usando noUiSlider)
    const ageSlider = document.getElementById('ageSlider');
    if (ageSlider && window.noUiSlider) {
        // Verificar se o slider já foi inicializado
        if (!ageSlider.noUiSlider) {
            noUiSlider.create(ageSlider, {
                start: [0, 15],
                connect: true,
                step: 1,
                range: {
                    'min': 0,
                    'max': 20
                },
                format: {
                    to: function(value) {
                        return Math.round(value);
                    },
                    from: function(value) {
                        return Number(value);
                    }
                }
            });
            
            const ageMin = document.getElementById('idade_min');
            const ageMax = document.getElementById('idade_max');
            
            if (ageMin && ageMax) {
                ageSlider.noUiSlider.on('update', function(values, handle) {
                    const value = values[handle];
                    if (handle === 0) {
                        ageMin.value = value;
                    } else {
                        ageMax.value = value;
                    }
                });
            }
        }
    }
    
    // Atualizar contagem de resultados via AJAX (descomentar se necessário)
    /*
    const filterInputs = filterForm.querySelectorAll('input, select');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            const formData = new FormData(filterForm);
            formData.append('count_only', '1');
            
            fetch('buscar-pets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('resultCount');
                if (countElement) {
                    countElement.textContent = data.count;
                }
            })
            .catch(error => console.error('Erro:', error));
        });
    });
    */
}

/**
 * Configuração para sistema de notificações
 */
function setupNotifications() {
    // Marcar notificação como lida
    const markAsReadLinks = document.querySelectorAll('.mark-notification-read');
    
    markAsReadLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.getAttribute('data-id');
            if (!notificationId) return;
            
            const notificationItem = this.closest('.notification-item');
            if (!notificationItem) return;
            
            fetch('marcar-notificacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${notificationId}&action=read`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    notificationItem.classList.remove('unread');
                    
                    // Atualizar contador de notificações
                    const counter = document.querySelector('.notification-counter');
                    if (counter) {
                        const count = parseInt(counter.textContent) - 1;
                        counter.textContent = count > 0 ? count : '';
                        if (count <= 0) {
                            counter.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Erro:', error));
        });
    });
    
    // Marcar todas notificações como lidas
    const markAllReadBtn = document.getElementById('markAllNotificationsRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch('marcar-notificacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=read_all'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Atualizar UI
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Zerar contador
                    const counter = document.querySelector('.notification-counter');
                    if (counter) {
                        counter.textContent = '';
                        counter.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Erro:', error));
        });
    }
}

/**
 * Configurar confirmações de exclusão
 */
function setupDeleteConfirmations() {
    // Modal de confirmação de exclusão de pet
    const deletePetModal = document.getElementById('deletePetModal');
    if (deletePetModal && typeof bootstrap !== 'undefined') {
        deletePetModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            const petId = button.getAttribute('data-pet-id');
            const petName = button.getAttribute('data-pet-nome');
            
            const modalPetName = this.querySelector('.pet-name');
            const petIdInput = this.querySelector('input[name="pet_id"]');
            
            if (modalPetName) {
                modalPetName.textContent = petName || 'o pet';
            }
            
            if (petIdInput) {
                petIdInput.value = petId;
            }
        });
    }
    
    // Modal de confirmação de exclusão de usuário
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal && typeof bootstrap !== 'undefined') {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            const modalUserName = this.querySelector('.user-name');
            const userIdInput = this.querySelector('input[name="usuario_id"]');
            
            if (modalUserName) {
                modalUserName.textContent = userName || 'o usuário';
            }
            
            if (userIdInput) {
                userIdInput.value = userId;
            }
        });
    }
    
    // Modal de bloqueio/desbloqueio de usuário
    const toggleStatusModal = document.getElementById('toggleStatusModal');
    if (toggleStatusModal && typeof bootstrap !== 'undefined') {
        toggleStatusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            // Obter atributos do botão
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const newStatus = button.getAttribute('data-new-status');
            
            const modalUserName = this.querySelector('.user-name');
            const userIdInput = this.querySelector('input[name="usuario_id"]');
            const newStatusInput = this.querySelector('input[name="novo_status"]');
            const modalTitle = this.querySelector('.modal-title');
            const modalBody = this.querySelector('.modal-body p');
            const motivoGroup = this.querySelector('.motivo-group');
            
            if (modalUserName) {
                modalUserName.textContent = userName || 'o usuário';
            }
            
            if (userIdInput) {
                userIdInput.value = userId;
            }
            
            if (newStatusInput) {
                newStatusInput.value = newStatus;
            }
            
            if (modalTitle) {
                modalTitle.textContent = newStatus === 'ativo' 
                    ? 'Ativar Usuário' 
                    : 'Bloquear Usuário';
            }
            
            if (modalBody) {
                modalBody.innerHTML = newStatus === 'ativo'
                    ? `Tem certeza que deseja ativar o usuário <strong>${userName || 'selecionado'}</strong>?`
                    : `Tem certeza que deseja bloquear o usuário <strong>${userName || 'selecionado'}</strong>?`;
            }
            
            // Mostrar campo de motivo apenas para bloqueio
            if (motivoGroup) {
                motivoGroup.style.display = newStatus === 'ativo' ? 'none' : 'block';
            }
        });
    }
}

/**
 * Configurar gráficos estatísticos (usando Chart.js)
 */
function setupCharts() {
    // Gráfico de status dos pets
    const petStatusChart = document.getElementById('petStatusChart');
    if (petStatusChart && typeof Chart !== 'undefined') {
        // Dados devem vir do PHP para o template
        let labels = [];
        let data = [];
        let backgroundColor = [];
        
        try {
            // Pegar dados dos atributos data-* se estiverem disponíveis
            const statusLabels = petStatusChart.getAttribute('data-status-labels');
            const statusData = petStatusChart.getAttribute('data-status-data');
            const statusColors = petStatusChart.getAttribute('data-status-colors');
            
            if (statusLabels && statusData) {
                labels = statusLabels.split(',');
                data = statusData.split(',').map(item => parseInt(item, 10));
                
                // Se cores foram fornecidas, use-as; caso contrário, gere cores aleatórias
                if (statusColors) {
                    backgroundColor = statusColors.split(',');
                } else {
                    // Gerar cores para cada item
                    labels.forEach(() => {
                        backgroundColor.push(getRandomColor());
                    });
                }
            } else {
                // Dados padrão para teste
                labels = ['Disponível', 'Adotado', 'Em tratamento', 'Reservado'];
                data = [12, 19, 5, 8];
                backgroundColor = [
                    '#28a745', // verde para disponível
                    '#007bff', // azul para adotado
                    '#ffc107', // amarelo para em tratamento
                    '#fd7e14'  // laranja para reservado
                ];
            }
            
            // Configuração do gráfico
            const chartConfig = {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Status dos Pets',
                        data: data,
                        backgroundColor: backgroundColor,
                        borderColor: 'rgba(255, 255, 255, 0.8)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: "'Nunito', sans-serif",
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
            
            // Criar o gráfico
            new Chart(petStatusChart, chartConfig);
        } catch (error) {
            console.error('Erro ao configurar gráfico:', error);
        }
    }
    
    // Gráfico de adoções por mês (se existir)
    const adoptionsChart = document.getElementById('adoptionsChart');
    if (adoptionsChart && typeof Chart !== 'undefined') {
        try {
            // Tentar obter dados do elemento
            const monthsData = adoptionsChart.getAttribute('data-months');
            const countsData = adoptionsChart.getAttribute('data-counts');
            
            let months = [];
            let counts = [];
            
            if (monthsData && countsData) {
                months = monthsData.split(',');
                counts = countsData.split(',').map(item => parseInt(item, 10));
            } else {
                // Dados padrão para teste
                months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
                counts = [5, 8, 12, 7, 9, 11];
            }
            
            const chartConfig = {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Adoções',
                        data: counts,
                        backgroundColor: 'rgba(248, 105, 10, 0.7)',
                        borderColor: 'rgba(248, 105, 10, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            };
            
            new Chart(adoptionsChart, chartConfig);
        } catch (error) {
            console.error('Erro ao configurar gráfico de adoções:', error);
        }
    }
}
