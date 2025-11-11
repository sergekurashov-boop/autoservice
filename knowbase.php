<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Включаем шапку с навбаром
define('ACCESS', true);
include 'templates/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База знаний автосервиса</title>
        <!-- Подключаем TinyMCE -->
    <script src="/autoservice/assets/js/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea', // Выберите селектор для textarea, в котором будет редактор
    //  Дополнительные настройки, например:
    //  height: 500,
    //  plugins: 'link image code',
    //  toolbar: 'undo redo | styleselect | bold italic | link image | code',
  });
</script>
	
	
   
</head>
<body>
    <!-- Шапка -->
    <header class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-journal-bookmark"></i> База знаний автосервиса</h1>
                    <p class="lead">Найдите ответы на все вопросы по обслуживанию и ремонту автомобилей</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Поиск по базе знаний...">
                        <button class="btn btn-light" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Основной контент -->
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Добавить новую статью</h3>
                        <div>
                            <span class="badge bg-info">TinyMCE 6.6</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Заголовок статьи</label>
                            <input type="text" class="form-control form-control-lg" placeholder="Введите заголовок...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Категория</label>
                            <select class="form-select">
                                <option selected>Выберите категорию</option>
                                <option>Техническое обслуживание</option>
                                <option>Ремонт двигателя</option>
                                <option>Трансмиссия</option>
                                <option>Электроника</option>
                                <option>Кузовные работы</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Содержание статьи</label>
                            <div class="editor-container">
                                <textarea id="kb-editor">
                                    <h2>Руководство по сезонному обслуживанию автомобиля</h2>
                                    <p>Регулярное сезонное обслуживание автомобиля - залог его долгой и бесперебойной работы. В этом руководстве мы рассмотрим основные этапы подготовки автомобиля к летнему и зимнему сезонам.</p>
                                    
                                    <h3>Подготовка к зиме</h3>
                                    <p>Зимняя эксплуатация автомобиля требует особого внимания:</p>
                                    <ul>
                                        <li>Замена резины на зимнюю</li>
                                        <li>Проверка системы охлаждения и антифриза</li>
                                        <li>Диагностика аккумулятора</li>
                                        <li>Проверка работы печки и системы обогрева</li>
                                    </ul>
                                    
                                    <img src="https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" alt="Зимнее обслуживание">
                                    
                                    <h3>Подготовка к лету</h3>
                                    <p>Летняя эксплуатация также имеет свои особенности:</p>
                                    <ol>
                                        <li>Замена масла и фильтров</li>
                                        <li>Проверка системы кондиционирования</li>
                                        <li>Диагностика тормозной системы</li>
                                        <li>Контроль уровня охлаждающей жидкости</li>
                                    </ol>
                                    
                                    <div class="alert alert-info mt-4">
                                        <strong>Совет специалиста:</strong> Проводите сезонное обслуживание за 2-3 недели до наступления сезона, чтобы избежать очередей в сервисе.
                                    </div>
                                </textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="featured">
                                    <label class="form-check-label" for="featured">
                                        Показать в рекомендуемых статьях
                                    </label>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-secondary me-2">
                                    <i class="bi bi-save"></i> Сохранить черновик
                                </button>
                                <button class="btn btn-primary">
                                    <i class="bi bi-send"></i> Опубликовать статью
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="mb-0">Последние добавленные статьи</h3>
                    </div>
                    <div class="card-body">
                        <div class="featured-article mb-4">
                            <h4><i class="bi bi-star-fill text-warning me-2"></i>Диагностика и ремонт ABS</h4>
                            <p class="text-muted">Подробное руководство по самостоятельной диагностике системы ABS. Распространенные ошибки и способы их устранения.</p>
                            <div class="d-flex justify-content-between">
                                <span class="category-badge">Электроника</span>
                                <small class="text-muted">Добавлено 2 дня назад, 145 просмотров</small>
                            </div>
                        </div>
                        
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Замена масла в автоматической коробке передач</h5>
                                    <small>1 день назад</small>
                                </div>
                                <p class="mb-1">Пошаговая инструкция по замене ATF жидкости с фотографиями и рекомендациями.</p>
                                <span class="category-badge">Трансмиссия</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Полировка фар своими руками</h5>
                                    <small>3 дня назад</small>
                                </div>
                                <p class="mb-1">Как восстановить прозрачность фар без специального оборудования.</p>
                                <span class="category-badge">Кузовные работы</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Диагностика инжекторных двигателей</h5>
                                    <small>5 дней назад</small>
                                </div>
                                <p class="mb-1">Основные методы диагностики и частые проблемы инжекторных систем.</p>
                                <span class="category-badge">Ремонт двигателя</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Сайдбар -->
            <div class="col-lg-4">
                <div class="stats-card mb-4">
                    <h4><i class="bi bi-graph-up me-2"></i>Статистика базы знаний</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Всего статей</span>
                            <span class="badge bg-primary">142</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Категорий</span>
                            <span class="badge bg-success">8</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Популярная статья</span>
                            <span class="text-truncate" style="max-width: 150px;">Замена тормозных колодок</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Просмотров за месяц</span>
                            <span class="badge bg-info">2,458</span>
                        </li>
                    </ul>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bookmarks me-2"></i>Категории знаний</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="category-badge">Техническое обслуживание</span>
                            <span class="category-badge">Ремонт двигателя</span>
                            <span class="category-badge">Трансмиссия</span>
                            <span class="category-badge">Электроника</span>
                            <span class="category-badge">Кузовные работы</span>
                            <span class="category-badge">Шины и диски</span>
                            <span class="category-badge">Тормозная система</span>
                            <span class="category-badge">Система охлаждения</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Популярные статьи</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Как проверить уровень масла</h6>
                                    <small>1,245 просмотров</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Замена воздушного фильтра</h6>
                                    <small>987 просмотров</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Диагностика аккумулятора</h6>
                                    <small>843 просмотров</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Замена тормозных колодок</h6>
                                    <small>792 просмотров</small>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Расшифровка маркировки шин</h6>
                                    <small>721 просмотр</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Инициализация TinyMCE -->
    <script>
        tinymce.init({
            selector: '#kb-editor',
            height: 400,
            plugins: 'preview importcss searchreplace autolink autosave save directionality visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
            toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen preview | insertfile image media link anchor codesample | ltr rtl',
            toolbar_sticky: true,
            autosave_ask_before_unload: true,
            autosave_interval: "30s",
            autosave_retention: "2m",
            image_advtab: true,
            content_css: '//www.tiny.cloud/css/codepen.min.css',
            link_list: [
                { title: 'Главная', value: 'https://вашавтосервис.ру' },
                { title: 'О нас', value: 'https://вашавтосервис.ру/about' },
                { title: 'Услуги', value: 'https://вашавтосервис.ру/services' }
            ],
            templates: [
                { title: 'Совет', description: 'Блок с полезным советом', content: '<div class="tip"><strong>Совет:</strong> <p>Текст совета...</p></div>' },
                { title: 'Предупреждение', description: 'Блок с предупреждением', content: '<div class="warning"><strong>Внимание!</strong> <p>Текст предупреждения...</p></div>' },
                { title: 'Пошаговая инструкция', description: 'Шаги выполнения работы', content: '<h3>Пошаговая инструкция</h3><ol><li>Шаг 1</li><li>Шаг 2</li><li>Шаг 3</li></ol>' }
            ],
            file_picker_callback: function(callback, value, meta) {
                // Логика загрузки файлов
                if (meta.filetype === 'image') {
                    // Симуляция выбора файла
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.click();
                    
                    input.onchange = function() {
                        const file = this.files[0];
                        const reader = new FileReader();
                        
                        reader.onload = function() {
                            callback(reader.result, { title: file.name });
                        };
                        reader.readAsDataURL(file);
                    };
                }
            },
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('TinyMCE инициализирован!');
                });
            }
        });
    </script>
	<?php include 'templates/footer.php'; ?>
</body>
</html>