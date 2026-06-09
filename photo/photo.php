<?php
// ==========================================
// 1. НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ
// ==========================================
$host = 'localhost';
$db   = 'photographer_db';       // Имя твоей базы данных в phpMyAdmin
$user = 'root';            // Логин (обычно root)
$pass = '';                // Пароль (пустой для XAMPP/OpenServer)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Если ошибка подключения, показываем её и останавливаем скрипт
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// ==========================================
// 2. ОСНОВНАЯ ЛОГИКА
// ==========================================
$page_title = "ДУЛГАР | Портфолио";

// Категории для фильтра
$allowed_categories = ['all', 'individual', 'love_story', 'family', 'wedding'];
$current_category = $_GET['category'] ?? 'all';

if (!in_array($current_category, $allowed_categories)) {
    $current_category = 'all';
}

// Запрос к БД
$sql = "SELECT * FROM portfolio ORDER BY uploaded_at DESC, id DESC";
$params = [];

if ($current_category !== 'all') {
    $sql = "SELECT * FROM portfolio WHERE category = :cat ORDER BY uploaded_at DESC, id DESC";
    $params[':cat'] = $current_category;
} 

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$portfolio_raw = $stmt->fetchAll();

// Преобразуем данные в удобный формат для JS
$portfolio_array = [];
foreach ($portfolio_raw as $p) {
    $portfolio_array[] = [
        'id' => $p['id'],
        'src' => $p['src'],       // Путь к картинке (например: uploads/photo.jpg)
        'category' => $p['category'] // Категория (individual, love_story и т.д.)
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* === СТИЛИ DULGAR === */
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --cream: #FAF8F5;
    --beige: #F0EBE3;
    --gold: #C9B99A;
    --gold-dark: #B09D7D;
    --gray: #8B8680;
    --dark: #3D3D3D;
    --black: #1A1A1A;
    --sidebar-width: 280px;
}
body {
    font-family: 'Montserrat', sans-serif;
    background-color: var(--cream);
    color: var(--dark);
    line-height: 1.6;
    overflow-x: hidden;
}
h1, h2, h3 { font-family: 'Cormorant Garamond', serif; }

/* Header */
.header {
    position: fixed; width: 100%; top: 0; z-index: 1000;
    background: rgba(250, 248, 245, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(201, 185, 154, 0.3);
    padding: 15px 50px; transition: all 0.4s ease;
}
.header-inner { max-width: 1600px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
.logo { 
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px; font-weight: 400; letter-spacing: 3px; 
    color: var(--black); text-decoration: none; text-transform: uppercase; 
}
.logo span { color: var(--gold); }

.nav { display: flex; gap: 35px; align-items: center; }
.nav a { 
    color: var(--dark); text-decoration: none; 
    font-size: 13px; text-transform: uppercase; 
    letter-spacing: 1.5px; transition: color 0.3s ease; font-weight: 400; 
}
.nav a:hover { color: var(--gold); }

.burger-menu { display: none; flex-direction: column; gap: 5px; cursor: pointer; z-index: 1001; }
.burger-line { width: 25px; height: 2px; background-color: var(--black); transition: 0.3s; }

/* Layout */
.main-wrapper { display: flex; margin-top: 70px; min-height: calc(100vh - 70px); }

/* Sidebar */
.sidebar {
    width: var(--sidebar-width); min-width: var(--sidebar-width);
    background: white; border-right: 1px solid var(--beige);
    padding: 40px 0; position: fixed; height: calc(100vh - 70px);
    overflow-y: auto; z-index: 900; transition: transform 0.4s ease;
}
.sidebar-menu { list-style: none; padding: 0 25px; }
.sidebar-link {
    display: flex; align-items: center; gap: 15px; padding: 16px 20px;
    color: var(--gray); text-decoration: none; font-size: 15px;
    letter-spacing: 1px; transition: all 0.3s; border-radius: 4px; cursor: pointer;
    text-transform: uppercase; font-size: 12px; font-weight: 500;
}
.sidebar-link:hover, .sidebar-link.active { 
    background: var(--cream); color: var(--gold-dark); 
    border-left: 3px solid var(--gold);
}
.sidebar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--beige); transition: 0.3s; }
.sidebar-link.active .sidebar-dot { background: var(--gold); }

/* Content */
.content-area { flex: 1; margin-left: var(--sidebar-width); padding: 50px; }
.page-header { margin-bottom: 50px; text-align: center; }
.page-header h1 { font-size: 48px; font-weight: 300; letter-spacing: 5px; color: var(--black); margin-bottom: 10px; }
.page-header .subtitle { font-size: 18px; color: var(--gray); font-style: italic; }

/* Grid */
.projects-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
    gap: 20px; 
    opacity: 1; transition: opacity 0.3s ease; 
}
.projects-grid.fade-out { opacity: 0; }

.project-card {
    background: white; 
    overflow: hidden; 
    cursor: pointer;
    transition: all 0.4s; 
    border: 1px solid var(--beige);
    aspect-ratio: 3/4; /* Вертикальный формат */
    position: relative;
}
.project-card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 30px rgba(0,0,0,0.05); 
    border-color: var(--gold); 
}

.project-card img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    transition: transform 0.6s; 
    display: block; 
}
.project-card:hover img { transform: scale(1.05); }

.card-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    height: 100%;
}
.project-card:hover .card-overlay { opacity: 1; }
.card-category {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--gold);
    font-weight: 600;
}

/* Modal */
.modal-overlay { 
    display: none; position: fixed; inset: 0; 
    background: rgba(26, 26, 26, 0.98); z-index: 2000; 
    opacity: 0; transition: opacity 0.3s; 
}
.modal-overlay.active { display: flex; opacity: 1; }

.modal-container { 
    width: 100%; height: 100%;
    position: relative; 
    display: flex; 
    align-items: center; 
    justify-content: center;
}

.modal-close { 
    position: absolute; top: 30px; right: 30px; 
    width: 50px; height: 50px; 
    border: 1px solid rgba(255,255,255,0.3); 
    border-radius: 50%; 
    color: white; 
    font-size: 30px; 
    cursor: pointer; 
    z-index: 10; 
    display: flex; align-items: center; justify-content: center; 
    transition: 0.3s; 
    background: rgba(0,0,0,0.5);
}
.modal-close:hover { background: var(--gold); border-color: var(--gold); color: black; }

.modal-slider-wrapper { 
    width: 100%; height: 100%; 
    display: flex; align-items: center; justify-content: center;
    position: relative;
}
.slider-track { 
    display: flex; 
    height: 90vh; 
    transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
    align-items: center;
}
.slider-slide { 
    min-width: 100vw; 
    height: 100%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding: 0 50px; 
    box-sizing: border-box;
}
.slider-slide img { 
    max-width: 100%; 
    max-height: 100%; 
    object-fit: contain; 
    box-shadow: 0 0 50px rgba(0,0,0,0.5);
}

.slider-btn { 
    position: absolute; top: 50%; transform: translateY(-50%); 
    width: 60px; height: 60px; 
    background: rgba(255,255,255,0.1); 
    border: 1px solid rgba(255,255,255,0.2); 
    border-radius: 50%; 
    color: white; 
    font-size: 30px; 
    cursor: pointer; 
    z-index: 5; 
    transition: 0.3s; 
    display: flex; align-items: center; justify-content: center;
}
.slider-btn:hover { background: var(--gold); border-color: var(--gold); color: black; }
.slider-btn.prev { left: 40px; }
.slider-btn.next { right: 40px; }

/* Responsive */
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-100%); z-index: 999; background: white; }
    .sidebar.open { transform: translateX(0); box-shadow: 5px 0 20px rgba(0,0,0,0.1); }
    .content-area { margin-left: 0; padding: 30px 20px; }
    .burger-menu { display: flex; }
    .nav { display: none; }
    .projects-grid { grid-template-columns: repeat(2, 1fr); }
    
    .slider-slide { padding: 0 20px; }
    .slider-btn.prev { left: 10px; }
    .slider-btn.next { right: 10px; }
}
@media (max-width: 768px) {
    .projects-grid { grid-template-columns: 1fr; }
    .header { padding: 15px 20px; }
    .logo { font-size: 22px; }
    .page-header h1 { font-size: 32px; }
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
<div class="header-inner">
    <div class="burger-menu" onclick="toggleSidebar()">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    </div>

    <a href="index.php" class="logo">DUL<span>GAR</span></a>
    <nav class="nav">
        <a href="index.php#about">Обо мне</a>
        <a href="portfolio.php">Портфолио</a>
        <a href="index.php#services">Прайс</a>
        <a href="index.php#contacts">Контакты</a>
    </nav>
</div>
</header>

<div class="main-wrapper">
<aside class="sidebar" id="sidebar">
<ul class="sidebar-menu">
    <li><div class="sidebar-link <?= $current_category === 'all' ? 'active' : '' ?>" data-category="all"><span class="sidebar-dot"></span><span>Все работы</span></div></li>
    <li><div class="sidebar-link <?= $current_category === 'individual' ? 'active' : '' ?>" data-category="individual"><span class="sidebar-dot"></span><span>Индивидуальная</span></div></li>
    <li><div class="sidebar-link <?= $current_category === 'love_story' ? 'active' : '' ?>" data-category="love_story"><span class="sidebar-dot"></span><span>Love Story</span></div></li>
    <li><div class="sidebar-link <?= $current_category === 'family' ? 'active' : '' ?>" data-category="family"><span class="sidebar-dot"></span><span>Семейная</span></div></li>
    <li><div class="sidebar-link <?= $current_category === 'wedding' ? 'active' : '' ?>" data-category="wedding"><span class="sidebar-dot"></span><span>Свадьба</span></div></li>
</ul>
</aside>

<main class="content-area">
<div class="page-header">
    <h1>ПОРТФОЛИО</h1>
    <p class="subtitle">Моменты, которые хочется помнить</p>
</div>
<div class="projects-grid" id="projectsGrid"></div>
</main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
<div class="modal-container">
    <button class="modal-close" id="modalClose">&times;</button>
    <div class="modal-slider-wrapper">
        <div class="slider-track" id="sliderTrack"></div>
        <button class="slider-btn prev" id="sliderPrev">‹</button>
        <button class="slider-btn next" id="sliderNext">›</button>
    </div>
</div>
</div>

<script>
const initialCategory = <?= json_encode($current_category) ?>;
const allPhotos = <?= json_encode($portfolio_array, JSON_UNESCAPED_UNICODE) ?>;

let currentSlide = 0;
let currentFilteredPhotos = []; 

const projectsGrid = document.getElementById('projectsGrid');
const modalOverlay = document.getElementById('modalOverlay');
const modalClose = document.getElementById('modalClose');
const sliderTrack = document.getElementById('sliderTrack');
const sidebarLinks = document.querySelectorAll('.sidebar-link');
const sidebar = document.getElementById('sidebar');
const burgerMenu = document.querySelector('.burger-menu');

function toggleSidebar() {
    sidebar.classList.toggle('open');
}

document.addEventListener('click', (e) => {
    if (window.innerWidth <= 1024 && !sidebar.contains(e.target) && !burgerMenu.contains(e.target) && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
    }
});

function renderProjects(category) {
    projectsGrid.innerHTML = '';
    
    const filtered = category === 'all' 
        ? allPhotos 
        : allPhotos.filter(p => p.category === category);

    currentFilteredPhotos = filtered;

    if (filtered.length === 0) {
        projectsGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--gray); padding: 50px;">В этой категории пока нет работ.</p>';
        return;
    }

    filtered.forEach((photo, index) => {
        const card = document.createElement('div');
        card.className = 'project-card';
        card.onclick = () => openModal(index);
        
        let catName = photo.category;
        if(catName === 'individual') catName = 'Индивидуальная';
        if(catName === 'love_story') catName = 'Love Story';
        if(catName === 'family') catName = 'Семейная';
        if(catName === 'wedding') catName = 'Свадьба';

        card.innerHTML = `
            <img src="${photo.src}" alt="Photo" loading="lazy">
            <div class="card-overlay">
                <span class="card-category">${catName}</span>
            </div>
        `;
        projectsGrid.appendChild(card);
    });
}

sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
        sidebarLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        if (window.innerWidth <= 1024) sidebar.classList.remove('open');
        
        projectsGrid.classList.add('fade-out');
        setTimeout(() => {
            renderProjects(link.dataset.category);
            history.replaceState(null, '', '?category=' + link.dataset.category);
            setTimeout(() => projectsGrid.classList.remove('fade-out'), 50);
        }, 300);
    });
});

renderProjects(initialCategory);

function openModal(index) {
    currentSlide = index;
    renderSlider();
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

function renderSlider() {
    if (currentFilteredPhotos.length === 0) return;
    
    sliderTrack.innerHTML = currentFilteredPhotos.map(photo => `
        <div class="slider-slide"><img src="${photo.src}" alt="Portfolio Image"></div>
    `).join('');
    
    updateSlider();
}

function updateSlider() {
    sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
}

function nextSlide() { 
    if(currentFilteredPhotos.length === 0) return;
    currentSlide = (currentSlide + 1) % currentFilteredPhotos.length; 
    updateSlider(); 
}

function prevSlide() { 
    if(currentFilteredPhotos.length === 0) return;
    currentSlide = (currentSlide - 1 + currentFilteredPhotos.length) % currentFilteredPhotos.length; 
    updateSlider(); 
}

modalClose.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });
document.getElementById('sliderNext').addEventListener('click', (e) => { e.stopPropagation(); nextSlide(); });
document.getElementById('sliderPrev').addEventListener('click', (e) => { e.stopPropagation(); prevSlide(); });

document.addEventListener('keydown', (e) => {
    if (!modalOverlay.classList.contains('active')) return;
    if (e.key === 'Escape') closeModal();
    if (e.key === 'ArrowRight') nextSlide();
    if (e.key === 'ArrowLeft') prevSlide();
});

let touchStartX = 0;
const sliderWrapper = document.querySelector('.modal-slider-wrapper');
sliderWrapper.addEventListener('touchstart', e => touchStartX = e.changedTouches[0].screenX, {passive: true});
sliderWrapper.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].screenX;
    if (Math.abs(diff) > 50) diff > 0 ? nextSlide() : prevSlide();
}, {passive: true});

</script>
</body>
</html>