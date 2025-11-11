<!-- Навигационная панель -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">
           &#128736;АВТОСЕРВИС
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link text-dark" href="clients.php">Клиенты</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="cars.php">Автомобили</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="orders.php">Заказы</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="services.php">Услуги</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="mechanics.php">Механики</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="parts.php">Запчасти</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="reports.php">Отчеты</a></li>
            </ul>
           
        </div>
    </div>
</nav>

<style>
.navbar {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.navbar-nav .nav-link {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    margin: 0 0.1rem;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover {
    color: #0D6EFD !important;
    background-color: rgba(13, 110, 253, 0.1);
}

.navbar-nav .nav-link:focus {
    color: #0D6EFD !important;
}

.navbar-nav .nav-item.active .nav-link {
    color: #0D6EFD !important;
    background-color: rgba(13, 110, 253, 0.1);
    font-weight: 600;
}

.navbar-toggler {
    border: none;
    padding: 0.25rem;
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
}

@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .navbar-nav .nav-link {
        margin: 0.2rem 0;
    }
    
    .d-flex {
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
}
</style>