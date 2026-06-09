<?php
// Подключаем конфиг с данными от БД
require_once 'config.php';

try {
    // 1. Подключение к БД через PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // 2. Получаем контакты из БД (ВК и Телефон)
    $stmtContacts = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $dbContacts = $stmtContacts->fetch();
    
    // Если в базе пусто, ставим заглушки, чтобы сайт не ломался
    $contacts = [
        'vk' => $dbContacts['vk'] ?? '#',
        'phone' => $dbContacts['phone'] ?? '+7 999 000 00 00'
    ];

    // 3. Получаем услуги (Прайс) из БД
    $stmtServices = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    $services = $stmtServices->fetchAll();

    // 4. Получаем портфолио из БД
    $stmtPortfolio = $pdo->query("SELECT * FROM portfolio ORDER BY uploaded_at DESC");
    $portfolio = $stmtPortfolio->fetchAll();

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ДУЛГАР | Фотограф</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* === ВАШ CSS ОСТАЕТСЯ БЕЗ ИЗМЕНЕНИЙ === */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --cream: #FAF8F5;
            --beige: #F0EBE3;
            --gold: #C9B99A;
            --gold-light: #D4C4B0;
            --gray: #8B8680;
            --dark: #3D3D3D;
            --black: #1A1A1A;
        }
        
        body { 
            font-family: 'Montserrat', sans-serif; 
            background-color: var(--cream);
            color: var(--dark);
            line-height: 1.6;
        }
        
        h1, h2, h3 { 
            font-family: 'Cormorant Garamond', serif; 
            font-weight: 400;
        }

        /* === НАВИГАЦИЯ === */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(250, 248, 245, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 15px 0;
            border-bottom: 1px solid rgba(201, 185, 154, 0.3);
        }

        nav .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8em;
            letter-spacing: 3px;
            color: var(--black);
            text-decoration: none;
            font-weight: 400;
        }

        .nav-logo span { color: var(--gold); }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 35px;
        }

        .nav-menu a {
            color: var(--dark);
            text-decoration: none;
            font-size: 0.8em;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: color 0.3s;
            position: relative;
        }

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--gold);
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold);
            transition: width 0.3s;
        }

        .nav-menu a:hover::after { width: 100%; }

        .nav-toggle {
            display: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--dark);
        }
        
        /* Header */
        header {
            text-align: center;
            padding: 120px 40px 60px;
            background: linear-gradient(to bottom, var(--beige), var(--cream));
        }
        
        .logo {
            font-size: 4em;
            letter-spacing: 15px;
            color: var(--black);
            margin-bottom: 15px;
            font-weight: 300;
        }
        
        .tagline {
            font-size: 0.9em;
            letter-spacing: 5px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 300;
            font-style: italic;
        }
        
        /* Main Container */
        .main-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            padding: 60px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Left Column */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        /* About Section */
        .about-section {
            background: white;
            padding: 60px 50px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.06);
        }
        
        .section-label {
            font-size: 0.7em;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .section-label::before,
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--gold-light));
        }
        
        .about-photo {
            width: 100%;
            height: 450px;
            object-fit: cover;
            margin-bottom: 35px;
            filter: grayscale(20%);
            transition: filter 0.5s;
        }
        
        .about-photo:hover {
            filter: grayscale(0%);
        }
        
        .about-title {
            font-size: 2.5em;
            letter-spacing: 4px;
            color: var(--black);
            margin-bottom: 30px;
            text-transform: uppercase;
            text-align: center;
        }
        
        .about-text {
            font-size: 0.9em;
            line-height: 2.2;
            color: var(--dark);
            margin-bottom: 20px;
            font-weight: 300;
            text-align: justify;
        }
        
        .about-features {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid var(--beige);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .feature-item {
            padding: 20px;
            background: var(--cream);
            transition: transform 0.3s;
        }
        
        .feature-item:hover {
            transform: translateY(-3px);
        }
        
        .feature-title {
            font-size: 0.8em;
            letter-spacing: 2px;
            color: var(--black);
            margin-bottom: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .feature-text {
            font-size: 0.8em;
            color: var(--gray);
            line-height: 1.8;
        }
        
        /* Portfolio Grid */
        .portfolio-section {
            background: white;
            padding: 50px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.06);
        }

        /* === ФИЛЬТРЫ ПОРТФОЛИО === */
        .portfolio-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: transparent;
            border: 1px solid var(--beige);
            padding: 8px 20px;
            font-size: 0.75em;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Montserrat', sans-serif;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--black);
        }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .portfolio-item {
            position: relative;
            overflow: hidden;
            height: 300px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .portfolio-item:hover {
            transform: translateY(-5px);
        }
        
        .portfolio-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: grayscale(30%);
            transition: all 0.5s;
        }
        
        .portfolio-item:hover img {
            filter: grayscale(0%);
            transform: scale(1.05);
        }

        .portfolio-item.hidden {
            display: none;
        }
        
        .portfolio-caption {
            text-align: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4em;
            color: var(--dark);
            font-style: italic;
            letter-spacing: 2px;
        }

        /* === LIGHTBOX (МОДАЛЬНОЕ ОКНО) === */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 26, 26, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            padding: 40px;
        }

        .lightbox.active {
            opacity: 1;
            visibility: visible;
        }

        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .lightbox-img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: var(--gold);
            font-size: 2em;
            cursor: pointer;
            transition: color 0.3s;
        }

        .lightbox-close:hover {
            color: var(--white);
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
            font-size: 3em;
            cursor: pointer;
            transition: color 0.3s;
            user-select: none;
        }

        .lightbox-nav:hover {
            color: var(--white);
        }

        .lightbox-prev { left: -60px; }
        .lightbox-next { right: -60px; }
        
        /* Right Column */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        /* === Services Section - КАК НА ФОТО === */
        .services-section {
            background: white;
            padding: 60px 50px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.06);
            border-radius: 2px;
        }
        
        .services-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .services-title {
            font-size: 3em;
            letter-spacing: 8px;
            color: var(--black);
            margin-bottom: 15px;
            text-transform: uppercase;
            font-weight: 300;
        }
        
        .services-subtitle {
            font-size: 0.85em;
            letter-spacing: 3px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 300;
        }
        
        /* Прайс-лист как на фото */
        .price-list {
            margin-top: 40px;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 25px 0;
            border-bottom: 1px solid var(--beige);
            position: relative;
            transition: all 0.3s;
        }
        
        .price-item:hover {
            background: rgba(201, 185, 154, 0.05);
            margin: 0 -10px;
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .price-item-content {
            flex: 1;
            padding-right: 30px;
        }
        
        .price-item-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.4em;
            color: var(--black);
            margin-bottom: 8px;
            font-weight: 400;
            letter-spacing: 1px;
        }
        
        .price-item-description {
            font-size: 0.8em;
            color: var(--gray);
            line-height: 1.6;
            font-weight: 300;
        }
        
        .price-item-description ul {
            list-style: none;
            margin-top: 8px;
        }
        
        .price-item-description li {
            display: inline;
            margin-right: 15px;
        }
        
        .price-item-description li::after {
            content: '•';
            margin-left: 15px;
            color: var(--gold);
        }
        
        .price-item-description li:last-child::after {
            content: '';
        }
        
        .price-item-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.6em;
            color: var(--gold);
            font-weight: 600;
            letter-spacing: 2px;
            white-space: nowrap;
        }
        
        .price-item-note {
            font-size: 0.75em;
            color: var(--gray);
            margin-top: 5px;
            font-weight: 300;
        }
        
        /* Info Box */
        .info-box {
            background: var(--black);
            color: var(--cream);
            padding: 50px;
            text-align: center;
            margin-top: 20px;
        }
        
        .info-title {
            font-size: 1.5em;
            letter-spacing: 4px;
            margin-bottom: 25px;
            color: var(--gold);
        }
        
        .info-text {
            font-size: 0.9em;
            line-height: 2;
            color: var(--gray);
            margin-bottom: 15px;
            font-weight: 300;
        }
        
        .info-highlight {
            color: var(--gold-light);
            font-weight: 500;
        }

        /* === FAQ SECTION (НОВОЕ) === */
        .faq-section {
            background: white;
            padding: 50px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.06);
            border-radius: 2px;
        }

        .faq-item {
            border-bottom: 1px solid var(--beige);
            margin-bottom: 15px;
        }

        .faq-question {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 15px 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3em;
            color: var(--dark);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.3s;
        }

        .faq-question:hover {
            color: var(--gold);
        }

        .faq-icon {
            font-size: 1.5em;
            color: var(--gold);
            transition: transform 0.3s;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
            padding-right: 20px;
        }

        .faq-answer p {
            padding-bottom: 20px;
            font-size: 0.9em;
            color: var(--gray);
            line-height: 1.8;
        }

        .faq-item.active .faq-icon {
            transform: rotate(45deg);
        }

        .faq-item.active .faq-answer {
            max-height: 200px; /* Достаточно для текста */
        }
        
        /* Contact Section */
        .contact-section {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(to bottom, var(--cream), var(--beige));
        }
        
        .contact-title {
            font-size: 2.5em;
            letter-spacing: 8px;
            color: var(--black);
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .contact-text {
            font-size: 1em;
            color: var(--gray);
            margin-bottom: 40px;
            font-style: italic;
            font-weight: 300;
        }
        
        .contact-links {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        
        .contact-link {
            color: var(--dark);
            text-decoration: none;
            font-size: 0.85em;
            letter-spacing: 3px;
            text-transform: uppercase;
            transition: all 0.3s;
            padding: 15px 30px;
            border: 1px solid var(--gold-light);
            position: relative;
            overflow: hidden;
        }
        
        .contact-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gold);
            transition: left 0.3s;
            z-index: -1;
        }
        
        .contact-link:hover::before {
            left: 0;
        }
        
        .contact-link:hover {
            color: white;
            border-color: var(--gold);
        }
        
        /* Footer */
        footer {
            text-align: center;
            padding: 40px;
            background: var(--black);
            color: var(--gray);
            font-size: 0.8em;
            letter-spacing: 2px;
        }
        
        /* Decorative Elements */
        .decorative-line {
            width: 100px;
            height: 1px;
            background: var(--gold);
            margin: 30px auto;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 40px 30px;
            }

            nav .container {
                padding: 0 20px;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--cream);
                flex-direction: column;
                padding: 20px;
                gap: 15px;
                border-bottom: 1px solid var(--beige);
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-toggle {
                display: block;
            }
            
            .about-features {
                grid-template-columns: 1fr;
            }
            
            .portfolio-grid {
                grid-template-columns: 1fr;
            }

            .lightbox-nav {
                font-size: 2em;
            }
            .lightbox-prev { left: 10px; }
            .lightbox-next { right: 10px; }
            
            .price-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .price-item-content {
                padding-right: 0;
            }
        }
        
        @media (max-width: 768px) {
            header {
                padding: 100px 20px 40px;
            }
            .logo {
                font-size: 2.5em;
                letter-spacing: 8px;
            }
            
            .services-title {
                font-size: 2em;
                letter-spacing: 5px;
            }
            
            .about-title {
                font-size: 1.8em;
            }
            
            .price-item-title {
                font-size: 1.2em;
            }
            
            .price-item-value {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>

<!-- === НАВИГАЦИЯ === -->
<nav>
    <div class="container">
        <a href="#" class="nav-logo">DUL<span>GAR</span></a>
        <div class="nav-toggle" id="navToggle">☰</div>
        <ul class="nav-menu" id="navMenu">
            <li><a href="#about" class="active">Обо мне</a></li>
            <li><a href="#portfolio">Портфолио</a></li>
            <li><a href="#services">Прайс</a></li>
            <li><a href="#contacts">Контакты</a></li>
        </ul>
    </div>
</nav>

<header>
    <h1 class="logo">ДУЛГАР</h1>
    <p class="tagline">Фотограф, который ловит моменты</p>
</header>

<div class="main-container">
    <!-- Left Column -->
    <div class="left-column">
        <!-- About Section -->
        <div class="about-section">
            <div class="section-label">Обо мне</div>
            <img src="https://images.unsplash.com/photo-1554048612-387768052bf7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="ДУЛГАР" class="about-photo">
            
            <h2 class="about-title">Приветствую</h2>
            
            <p class="about-text">
                Я создаю фотографии, которые рассказывают истории. Мои кадры — это не просто изображения, это эмоции, застывшие во времени.
            </p>
            <p class="about-text">
                Верю, что лучшие фотографии получаются тогда, когда человек забывает о камере и живет настоящим моментом. Моя задача — создать атмосферу доверия и комфорта, чтобы вы могли быть собой.
            </p>
            
            <div class="about-features">
                <div class="feature-item">
                    <div class="feature-title">✦ Естественность</div>
                    <div class="feature-text">Никаких заученных поз. Только живые эмоции и искренние моменты.</div>
                </div>
                <div class="feature-item">
                    <div class="feature-title">✦ Атмосфера</div>
                    <div class="feature-text">Работаю со светом, тенями и пространством для создания настроения.</div>
                </div>
                <div class="feature-item">
                    <div class="feature-title">✦ Комфорт</div>
                    <div class="feature-text">Помогу с позированием, локацией и образом. Со мной легко.</div>
                </div>
                <div class="feature-item">
                    <div class="feature-title">✦ Качество</div>
                    <div class="feature-text">Профессиональная техника и внимательная обработка каждого кадра.</div>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Grid -->
        <div class="portfolio-section" id="portfolio">
            <div class="section-label">Портфолио</div>
            
            <!-- === ФИЛЬТРЫ === -->
            <div class="portfolio-filters">
                <button class="filter-btn active" data-filter="Все">Все</button>
                <button class="filter-btn" data-filter="Индивидуальная">Индивидуальная</button>
                <button class="filter-btn" data-filter="Love Story">Love Story</button>
                <button class="filter-btn" data-filter="Семейная">Семейная</button>
            </div>
            
            <div class="portfolio-grid">
                <?php if (!empty($portfolio)): ?>
                    <?php foreach ($portfolio as $index => $item): ?>
                        <div class="portfolio-item" 
                             data-category="<?= htmlspecialchars($item['category'] ?? 'Все') ?>"
                             data-index="<?= $index ?>"
                             onclick="openLightbox(<?= $index ?>)">
                            <!-- Путь к фото берется из колонки src в БД -->
                            <img src="<?= htmlspecialchars($item['src']) ?>" alt="<?= htmlspecialchars($item['alt_text'] ?? 'Работа') ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--gray);">В портфолио пока нет фотографий.</p>
                <?php endif; ?>
            </div>
            <div class="portfolio-caption">Каждая фотография — это история</div>
            <div style="text-align: center; margin-top: 25px;">
                <a href="photo.php" style="color: var(--gold); text-decoration: none; font-size: 0.85em; letter-spacing: 3px; text-transform: uppercase; border-bottom: 1px solid var(--gold); padding-bottom: 5px;">Больше работ на сайте</a>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="right-column">
        <!-- Services Section - КАК НА ФОТО === -->
        <div class="services-section" id="services">
            <div class="services-header">
                <h2 class="services-title">ПРАЙС</h2>
                <p class="services-subtitle">Стоимость услуг</p>
            </div>
            
            <div class="price-list">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                    <div class="price-item">
                        <div class="price-item-content">
                            <h3 class="price-item-title"><?= htmlspecialchars($service['title']) ?></h3>
                            <div class="price-item-description">
                                <?php 
                                // Разбиваем details по запятой, если они хранятся строкой
                                $details = explode(',', $service['details']);
                                $count = count($details);
                                foreach ($details as $idx => $detail): 
                                ?>
                                    <?= htmlspecialchars(trim($detail)) ?>
                                    <?php if ($idx < $count - 1): ?>•<?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="price-item-value">
                            <?= htmlspecialchars($service['price']) ?>
                            <div class="price-item-note">за съёмку</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--gray);">Услуги пока не добавлены.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="info-box">
            <h3 class="info-title">Как записаться</h3>
            <p class="info-text">
                Напишите мне в <span class="info-highlight">Вконтакте</span>
            </p>
            <p class="info-text">
                Аренда студии, визажист и прочее <span class="info-highlight">оплачивается</span> отдельно.
            </p>
            <div class="decorative-line" style="background: var(--gold-light); margin: 25px auto;"></div>
            <p class="info-text">
                Срок сдачи: <span class="info-highlight">2-3 недели</span><br>
                Фото отдаю в <span class="info-highlight">электроном варианте</span> с ссылкой на облаке
                <br>
                Срок хранения <span class="info-highlight">2 месяца</span> 

            </p>
        </div>

        <!-- FAQ Section (НОВОЕ) -->
        <div class="faq-section">
            <div class="section-label">Частые вопросы</div>
            
            <div class="faq-item">
                <button class="faq-question">
                    Как забронировать дату?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Для брони даты необходима предоплата 30%. Без предоплаты дата не резервируется. Остаток суммы вносится в день съемки.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    Помогаете ли вы с позированием?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Конечно! Я всегда подсказываю удачные позы, помогаю с расположением рук и работой со взглядом. Вам не нужно готовиться заранее.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    Отдаете ли вы исходники?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Да, все удачные исходники (JPEG) входят в стоимость любой съемки. RAW-файлы обсуждаются отдельно или входят в расширенные пакеты.</p>
                </div>
            </div>
            
             <div class="faq-item">
                <button class="faq-question">
                    Что если я заболею?
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Ничего страшного. Мы можем перенести съемку на другую доступную дату без потери предоплаты, предупредив меня за 24 часа.</p>
                </div>
            </div>

        </div>
        <!-- Конец FAQ Section -->

    </div>
</div>

<!-- Contact Section -->
<section class="contact-section" id="contacts">
    <h2 class="contact-title">Контакты</h2>
    <p class="contact-text">Готовы создать что-то прекрасное? Давайте знакомиться!</p>
    <div class="contact-links">
        <!-- Кнопка ВКонтакте -->
        <a href="<?= htmlspecialchars($contacts['vk']) ?>" target="_blank" class="contact-link">Вконтакте</a>
        
        <!-- Кнопка Телефона -->
        <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $contacts['phone'])) ?>" class="contact-link">
            <?= htmlspecialchars($contacts['phone']) ?>
        </a>
    </div>
</section>

<footer>
    <p>© 2026 ДУЛГАР | Профессиональный фотограф</p>
    <p style="margin-top: 10px; font-size: 0.9em;">Создаю фотографии, которые остаются с вами навсегда</p>
</footer>

<!-- === LIGHTBOX === -->
<div class="lightbox" id="lightbox">
    <div class="lightbox-content">
        <span class="lightbox-close" onclick="closeLightbox()">×</span>
        <span class="lightbox-nav lightbox-prev" onclick="changeLightbox(-1)">❮</span>
        <img src="" alt="" class="lightbox-img" id="lightboxImg">
        <span class="lightbox-nav lightbox-next" onclick="changeLightbox(1)">❯</span>
    </div>
</div>

<script>
    // === НАВИГАЦИЯ ===
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
    
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // === ФИЛЬТРЫ ПОРТФОЛИО ===
    const filterBtns = document.querySelectorAll('.filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const filter = btn.dataset.filter;
            
            portfolioItems.forEach(item => {
                if (filter === 'Все' || item.dataset.category === filter) {
                    item.classList.remove('hidden');
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 100);
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    });

    // === LIGHTBOX ===
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    let currentIndex = 0;
    const visibleItems = () => Array.from(document.querySelectorAll('.portfolio-item:not(.hidden)'));

    function openLightbox(index) {
        const items = visibleItems();
        if (index >= items.length) return; 
        
        const item = items[index];
        currentIndex = index;
        const img = item.querySelector('img');
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function changeLightbox(direction) {
        const items = visibleItems();
        if (items.length === 0) return;

        currentIndex += direction;
        
        if (currentIndex < 0) currentIndex = items.length - 1;
        if (currentIndex >= items.length) currentIndex = 0;
        
        const img = items[currentIndex].querySelector('img');
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
    }

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') changeLightbox(-1);
        if (e.key === 'ArrowRight') changeLightbox(1);
    });

    // === FAQ ACCORDION (НОВОЕ) ===
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            item.classList.toggle('active');
        });
    });
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.feature-item, .portfolio-item, .price-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
</script>

</body>
</html>