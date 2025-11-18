// Функции для работы с автозаполнением автомобилей
function getCarInfoByLicensePlate() {
    const licensePlate = document.getElementById('licensePlateInput').value.trim();
    
    if (!licensePlate) {
        alert('Введите госномер для поиска');
        return;
    }
    
    if (!selectedClient) {
        alert('Сначала выберите клиента');
        return;
    }
    
    const licensePlateInput = document.getElementById('licensePlateInput');
    licensePlateInput.disabled = true;
    
    fetch('get_car_info_dadata.php?license_plate=' + encodeURIComponent(licensePlate))
        .then(response => response.json())
        .then(data => {
            licensePlateInput.disabled = false;
            
            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }
            
            if (data.selection_required) {
                // Показываем селектор модификаций
                openCarModelSelector(data, licensePlate);
            } else {
                // Старая логика (если вернулись готовые данные)
                processCarData(data, licensePlate);
            }
        })
        .catch(error => {
            licensePlateInput.disabled = false;
            console.error('Ошибка запроса:', error);
            alert('Ошибка подключения к сервису');
        });
}

// 🔧 СЕЛЕКТОР МОДИФИКАЦИЙ
function openCarModelSelector(data, licensePlate) {
    // Создаем модальное окно выбора модификации
    const modalHtml = `
        <div id="carModelModal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>🚗 Выбор модификации автомобиля</h3>
                    <span class="close" onclick="closeCarModelSelector()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="license-plate-info" style="background: #e3f2fd; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                        <strong>Госномер:</strong> ${licensePlate}
                    </div>
                    
                    <div id="modelSelectionSteps">
                        <!-- Шаги выбора будут здесь -->
                        ${renderBrandSelection(data.selection_steps.brand)}
                    </div>
                    
                    <div id="selectedModelInfo" style="display: none; background: #f8fff9; padding: 15px; border: 2px solid #28a745; border-radius: 4px; margin-top: 20px;">
                        <h4>✅ Выбранная модификация:</h4>
                        <div id="modelDetails"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-1c-secondary" onclick="closeCarModelSelector()">Отмена</button>
                    <button type="button" class="btn-1c-primary" id="confirmModelBtn" onclick="confirmModelSelection()" disabled>Подтвердить выбор</button>
                </div>
            </div>
        </div>
    `;
    
    // Добавляем модальное окно в DOM
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    
    // Сохраняем данные для последующих шагов
    window.carSelectionData = {
        licensePlate: licensePlate,
        currentStep: 'brand',
        selectedBrand: null,
        selectedModel: null,
        selectedYear: null,
        selectedEngine: null
    };
}

function renderBrandSelection(brandData) {
    let html = `
        <div class="selection-step active">
            <h4>${brandData.title}</h4>
            <div class="brand-selection" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
    `;
    
    brandData.options.forEach(brand => {
        if (brand.group) {
            html += `<div class="brand-group" style="grid-column: 1 / -1; font-weight: bold; color: #2c3e50; margin-top: 10px;">${brand.name}</div>`;
        } else {
            const parentClass = brand.parent ? `brand-${brand.parent}` : '';
            html += `
                <button type="button" class="brand-btn ${parentClass}" 
                        onclick="selectBrand('${brand.id}', '${brand.name}')"
                        style="padding: 12px; border: 2px solid #ddd; background: white; cursor: pointer; text-align: left; border-radius: 4px;">
                    <strong>${brand.name}</strong>
                </button>
            `;
        }
    });
    
    html += `</div></div>`;
    return html;
}

function selectBrand(brandId, brandName) {
    window.carSelectionData.selectedBrand = { id: brandId, name: brandName };
    
    // Показываем следующий шаг - выбор модели
    loadModelsForBrand(brandId);
}

function loadModelsForBrand(brandId) {
    // Здесь будет AJAX запрос к базе моделей
    // Пока используем демо-данные
    const demoModels = {
        'vw': [
            { id: 'polo', name: 'Polo', generations: ['2010-2014', '2015-2017', '2018-2021', '2022-н.в.'] },
            { id: 'golf', name: 'Golf', generations: ['Golf 7 (2012-2017)', 'Golf 7.5 (2017-2020)', 'Golf 8 (2020-н.в.)'] },
            { id: 'tiguan', name: 'Tiguan', generations: ['1gen (2007-2011)', '1gen facelift (2011-2016)', '2gen (2016-2020)', '2gen facelift (2020-н.в.)'] },
            { id: 'passat', name: 'Passat', generations: ['B7 (2010-2014)', 'B8 (2014-2019)', 'B8 facelift (2019-2023)'] }
        ],
        'audi': [
            { id: 'a4', name: 'A4', generations: ['B8 (2007-2011)', 'B8 facelift (2011-2015)', 'B9 (2015-2019)', 'B9 facelift (2019-2023)'] },
            { id: 'a6', name: 'A6', generations: ['C7 (2011-2014)', 'C7 facelift (2014-2018)', 'C8 (2018-2023)'] }
        ]
        // ... другие марки
    };
    
    const models = demoModels[brandId] || [
        { id: 'model1', name: 'Модель 1', generations: ['2015-2020'] },
        { id: 'model2', name: 'Модель 2', generations: ['2018-2023'] }
    ];
    
    renderModelSelection(models);
}

function renderModelSelection(models) {
    const stepsContainer = document.getElementById('modelSelectionSteps');
    
    stepsContainer.innerHTML += `
        <div class="selection-step active">
            <h4>Выберите модель</h4>
            <div class="model-selection" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-top: 15px;">
                ${models.map(model => `
                    <div class="model-card" onclick="selectModel('${model.id}', '${model.name}')"
                         style="border: 2px solid #e0e0e0; padding: 15px; border-radius: 4px; cursor: pointer; background: white;">
                        <div style="font-weight: bold; margin-bottom: 8px;">${model.name}</div>
                        <div style="font-size: 0.8em; color: #666;">
                            Поколения: ${model.generations.join(', ')}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function selectModel(modelId, modelName) {
    window.carSelectionData.selectedModel = { id: modelId, name: modelName };
    
    // Показываем информацию о выборе
    showSelectedModelInfo();
    
    // Активируем кнопку подтверждения
    document.getElementById('confirmModelBtn').disabled = false;
}

function showSelectedModelInfo() {
    const infoContainer = document.getElementById('selectedModelInfo');
    const detailsContainer = document.getElementById('modelDetails');
    
    const data = window.carSelectionData;
    
    detailsContainer.innerHTML = `
        <p><strong>Марка:</strong> ${data.selectedBrand.name}</p>
        <p><strong>Модель:</strong> ${data.selectedModel.name}</p>
        <p><strong>Госномер:</strong> ${data.licensePlate}</p>
        <p><em>После подтверждения можно будет уточнить поколение и двигатель</em></p>
    `;
    
    infoContainer.style.display = 'block';
}

function confirmModelSelection() {
    const data = window.carSelectionData;
    
    // Заполняем форму выбранными данными
    document.getElementById('newCarMake').value = data.selectedBrand.name;
    document.getElementById('newCarModel').value = data.selectedModel.name;
    document.getElementById('newCarLicense').value = data.licensePlate;
    
    // Закрываем селектор
    closeCarModelSelector();
    
    // Открываем модальное окно добавления авто
    openAddCarModal();
    
    // Показываем уведомление
    alert(`Выбрана модификация: ${data.selectedBrand.name} ${data.selectedModel.name}`);
}

function closeCarModelSelector() {
    const modal = document.getElementById('carModelModal');
    if (modal) {
        modal.remove();
    }
    window.carSelectionData = null;
}

// Старая функция для обратной совместимости
function processCarData(data, licensePlate) {
    if (data.length > 0 && data[0].result) {
        const carInfo = data[0].result;
        
        // Заполняем форму данными
        document.getElementById('newCarMake').value = carInfo.brand || '';
        document.getElementById('newCarModel').value = carInfo.model || '';
        document.getElementById('newCarLicense').value = licensePlate;
        document.getElementById('newCarYear').value = carInfo.year || '';
        document.getElementById('newCarVin').value = carInfo.vin || '';
        
        // Автоматически открываем модальное окно добавления авто
        openAddCarModal();
        
        // Показываем информацию о найденном авто
        showCarInfoPopup(carInfo);
    } else {
        alert('Автомобиль по госномеру "' + licensePlate + '" не найден');
    }
}

function showCarInfoPopup(carInfo) {
    const popup = document.createElement('div');
    popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 10000;
        max-width: 400px;
        border: 2px solid #28a745;
    `;
    
    popup.innerHTML = `
        <h4 style="margin: 0 0 15px 0; color: #28a745;">🚗 Найден автомобиль:</h4>
        <div style="margin-bottom: 15px;">
            <strong>${carInfo.brand || ''} ${carInfo.model || ''}</strong><br>
            ${carInfo.year ? `Год: ${carInfo.year}<br>` : ''}
            ${carInfo.vin ? `VIN: ${carInfo.vin}<br>` : ''}
            ${carInfo.category ? `Категория: ${carInfo.category}<br>` : ''}
            ${carInfo.engine_power ? `Мощность: ${carInfo.engine_power} л.с.<br>` : ''}
            ${carInfo.engine_volume ? `Объем: ${carInfo.engine_volume} см³<br>` : ''}
        </div>
        <button onclick="this.parentElement.remove()" style="
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        ">OK</button>
    `;
    
    document.body.appendChild(popup);
}