<?php
$page_title = "Технические мануалы";
include 'templates/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-book me-2"></i>Технические мануалы</h2>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Доступные руководства</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 hover-scale">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                Руководство по ремонту двигателей
                            </h5>
                            <p class="card-text">
                                Полное руководство по диагностике и ремонту бензиновых и дизельных двигателей.
                            </p>
                            <a href="/autoservice/docs/engine_manual.pdf" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download me-1"></i> Скачать (PDF, 12MB)
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100 hover-scale">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                Электрические системы
                            </h5>
                            <p class="card-text">
                                Схемы электрооборудования, диагностика неисправностей, ремонт проводки.
                            </p>
                            <a href="/autoservice/docs/electrical_manual.pdf" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download me-1"></i> Скачать (PDF, 8MB)
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100 hover-scale">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                Трансмиссия и подвеска
                            </h5>
                            <p class="card-text">
                                Обслуживание и ремонт коробок передач, дифференциалов, подвески.
                            </p>
                            <a href="/autoservice/docs/transmission_manual.pdf" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download me-1"></i> Скачать (PDF, 10MB)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h4 class="border-bottom pb-2">Популярные разделы</h4>
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-question-circle me-2"></i>
                        Как диагностировать ошибку P0300?
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-question-circle me-2"></i>
                        Замена сцепления на переднеприводных автомобилях
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-question-circle me-2"></i>
                        Калибровка датчиков АБС после замены ступицы
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>