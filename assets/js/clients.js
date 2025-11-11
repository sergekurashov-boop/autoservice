console.log('Clients.js loaded successfully');

class ClientsManager {
    constructor() {
        this.searchTimeout = null;
        this.init();
    }

    init() {
        console.log('Initializing ClientsManager');
        this.initSectionToggling();
        this.initClientTypeSwitching();
        this.initSearch();
    }

    initSectionToggling() {
        const toggleButtons = document.querySelectorAll('.toggle-btn');
        console.log('Found toggle buttons:', toggleButtons.length);
        
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                console.log('Toggle button clicked:', e.currentTarget.getAttribute('data-target'));
                
                // Убрать active у всех кнопок
                document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                // Убрать active у всех разделов
                document.querySelectorAll('.hidden-section').forEach(s => s.classList.remove('active'));
                
                // Добавить active текущей кнопке
                e.currentTarget.classList.add('active');
                const target = e.currentTarget.getAttribute('data-target');
                const targetElement = document.getElementById(target);
                if (targetElement) {
                    targetElement.classList.add('active');
                }
            });
        });
    }

    initClientTypeSwitching() {
        const clientTypeRadios = document.querySelectorAll('input[name="client_type"]');
        console.log('Found client type radios:', clientTypeRadios.length);
        
        clientTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                console.log('Client type changed:', e.target.value);
                
                // Убрать active у всех опций
                document.querySelectorAll('.client-type-option').forEach(opt => opt.classList.remove('active'));
                // Добавить active текущей опции
                e.target.parentElement.classList.add('active');
                
                // Скрыть все секции
                document.querySelectorAll('.client-type-section').forEach(s => s.classList.remove('active'));
                // Показать нужную секцию
                const targetSection = document.getElementById(e.target.value + '-section');
                if (targetSection) {
                    targetSection.classList.add('active');
                }
            });
        });
    }

    initSearch() {
        const searchInput = document.getElementById('clientSearch');
        if (!searchInput) {
            console.error('Search input not found');
            return;
        }

        console.log('Search input found, setting up listeners');

        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            const searchTerm = e.target.value.trim();
            
            if (searchTerm.length < 2) {
                const resultsContainer = document.getElementById('searchResults');
                if (resultsContainer) {
                    resultsContainer.style.display = 'none';
                }
                return;
            }
            
            this.searchTimeout = setTimeout(() => this.performSearch(searchTerm), 300);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                const resultsContainer = document.getElementById('searchResults');
                if (resultsContainer) {
                    resultsContainer.style.display = 'none';
                }
            }
        });
    }

    async performSearch(searchTerm) {
        console.log('Performing search for:', searchTerm);
        try {
            const response = await fetch('?search=' + encodeURIComponent(searchTerm));
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const clients = await response.json();
            console.log('Search results:', clients);
            this.displaySearchResults(clients);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(clients) {
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) {
            console.error('Search results container not found');
            return;
        }
        
        resultsContainer.innerHTML = '';
        
        if (clients && clients.length > 0) {
            clients.forEach(client => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                
                const clientName = client.client_type === 'individual' ? 
                    (client.name || '') : 
                    (client.company_name || '');
                
                const contractInfo = client.contract_number ? 
                    '• Договор: ' + client.contract_number : '';
                
                item.innerHTML = `
                    <div class="client-info">
                        <div class="client-main">
                            <strong>${this.escapeHtml(clientName)}</strong>
                            <div class="client-details">
                                ${this.escapeHtml(client.phone || '')} • ${this.escapeHtml(client.email || 'нет email')}
                                ${contractInfo}
                            </div>
                        </div>
                        <span class="type-badge ${client.client_type === 'individual' ? 'badge-individual' : 'badge-legal'}">
                            ${client.client_type === 'individual' ? 'Физ. лицо' : 'Юр. лицо'}
                        </span>
                    </div>
                `;
                
                item.addEventListener('click', () => {
                    this.selectClient(client);
                });
                
                resultsContainer.appendChild(item);
            });
        } else {
            resultsContainer.innerHTML = '<div class="search-result-item">Клиенты не найдены</div>';
        }
        resultsContainer.style.display = 'block';
    }

    selectClient(client) {
        console.log('Client selected:', client);
        
        // Проверяем, открыта ли страница для выбора клиента
        const urlParams = new URLSearchParams(window.location.search);
        const returnTo = urlParams.get('return_to');
        
        if (returnTo) {
            // Если открыто из create_order.php - редиректим обратно
            window.location.href = `${returnTo}?selected_client=${client.id}`;
        } else {
            // Обычное поведение для clients.php
            const searchInput = document.getElementById('clientSearch');
            if (searchInput) {
                searchInput.value = client.client_type === 'individual' ? 
                    (client.name || '') : 
                    (client.company_name || '');
            }
            
            const resultsContainer = document.getElementById('searchResults');
            if (resultsContainer) {
                resultsContainer.style.display = 'none';
            }
            
            this.displaySelectedClient(client);
        }
    }

    displaySelectedClient(client) {
        const selectedContainer = document.getElementById('selectedClient');
        if (!selectedContainer) {
            console.error('Selected client container not found');
            return;
        }
        
        const clientName = client.client_type === 'individual' ? 
            (client.name || '') : 
            (client.company_name || '');
        
        const emailInfo = client.email ? 
            '<strong>Email:</strong> ' + this.escapeHtml(client.email) + '<br>' : '';
        
        const contractInfo = client.contract_number ? 
            '<strong>Договор:</strong> ' + this.escapeHtml(client.contract_number) + '<br>' : '';
        
        const innInfo = client.inn ? 
            '<strong>ИНН:</strong> ' + this.escapeHtml(client.inn) : '';
        
        selectedContainer.innerHTML = `
            <div class="enhanced-card">
                <div class="enhanced-card-header">
                    ✅ Выбран клиент
                    <button type="button" onclick="clientsManager.clearSelection()" class="btn-1c-secondary" style="float: right;">✕</button>
                </div>
                <div class="card-body">
                    <div class="client-info">
                        <div class="client-main">
                            <h5>${this.escapeHtml(clientName)}</h5>
                            <div class="client-details">
                                <strong>Телефон:</strong> ${this.escapeHtml(client.phone || '')}<br>
                                ${emailInfo}
                                ${contractInfo}
                                ${innInfo}
                            </div>
                        </div>
                        <span class="type-badge ${client.client_type === 'individual' ? 'badge-individual' : 'badge-legal'}">
                            ${client.client_type === 'individual' ? 'Физ. лицо' : 'Юр. лицо'}
                        </span>
                    </div>
                    <div class="mt-3">
                        <a href="client_edit.php?id=${client.id}" class="btn-1c-warning">✏️ Редактировать</a>
                        <a href="cars.php?client_id=${client.id}" class="btn-1c-primary">🚗 Автомобили клиента</a>
                    </div>
                </div>
            </div>
        `;
        selectedContainer.style.display = 'block';
    }

    clearSelection() {
        console.log('Clearing selection');
        const searchInput = document.getElementById('clientSearch');
        if (searchInput) {
            searchInput.value = '';
        }
        
        const selectedContainer = document.getElementById('selectedClient');
        if (selectedContainer) {
            selectedContainer.style.display = 'none';
            selectedContainer.innerHTML = '';
        }
    }

    switchToAddSection() {
        console.log('Switching to add section');
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.hidden-section').forEach(s => s.classList.remove('active'));
        
        const addButton = document.querySelector('[data-target="add-section"]');
        const addSection = document.getElementById('add-section');
        
        if (addButton && addSection) {
            addButton.classList.add('active');
            addSection.classList.add('active');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Инициализация после загрузки DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM fully loaded - initializing ClientsManager');
        window.clientsManager = new ClientsManager();
    });
} else {
    console.log('DOM already loaded - initializing ClientsManager immediately');
    window.clientsManager = new ClientsManager();
}