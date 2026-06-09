<?php
require_once 'config.php';

$message = "";
$uploadDir = 'uploads/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

try {
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

    // Получаем текущие данные для отображения в формах
    $stmtContacts = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $contacts = $stmtContacts->fetch() ?: ['vk'=>'', 'phone'=>''];

    $stmtServices = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    $services = $stmtServices->fetchAll();

    $stmtPortfolio = $pdo->query("SELECT * FROM portfolio ORDER BY uploaded_at DESC");
    $portfolio = $stmtPortfolio->fetchAll();

} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Сохранение контактов (ВК и Телефон)
    if (isset($_POST['save_contacts'])) {
        $stmt = $pdo->prepare("UPDATE settings SET vk=?, phone=? WHERE id=1");
        $stmt->execute([$_POST['vk'], $_POST['phone']]);
        $message = "Контакты обновлены";
        $contacts = ['vk' => $_POST['vk'], 'phone' => $_POST['phone']];
    }
    
    // 2. Загрузка фото
    if (isset($_POST['add_photo']) && isset($_FILES['photo_file'])) {
        $file = $_FILES['photo_file'];
        $category = $_POST['category'] ?? 'Все';
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'photo_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $newName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $stmt = $pdo->prepare("INSERT INTO portfolio (src, category, alt_text) VALUES (?, ?, ?)");
                    $stmt->execute([$uploadPath, $category, $category . ' съемка']);
                    $message = "Фото загружено";
                }
            }
        }
    }
    
    // 3. Удаление фото
    if (isset($_POST['delete_photo_id'])) {
        $id = $_POST['delete_photo_id'];
        $stmt = $pdo->prepare("SELECT src FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $photo = $stmt->fetch();
        
        if ($photo) {
            if (file_exists($photo['src'])) unlink($photo['src']);
            $pdo->prepare("DELETE FROM portfolio WHERE id = ?")->execute([$id]);
            $message = "Фото удалено";
        }
    }
    
    // 4. Сохранение услуг (Прайс)
    if (isset($_POST['save_services'])) {
        try {
            $pdo->beginTransaction();
            $pdo->exec("DELETE FROM services"); // Очищаем старый прайс
            
            $titles = $_POST['service_title'] ?? [];
            $prices = $_POST['service_price'] ?? [];
            $details = $_POST['service_details'] ?? [];
            $count = is_array($titles) ? count($titles) : 0;
            
            $stmt = $pdo->prepare("INSERT INTO services (title, price, details) VALUES (?, ?, ?)");
            for ($i = 0; $i < $count; $i++) {
                if (!empty($titles[$i])) {
                    $stmt->execute([$titles[$i], $prices[$i] ?? '', $details[$i] ?? '']);
                }
            }
            $pdo->commit();
            $message = "Прайс-лист успешно обновлен!";
            
            // Обновляем список для отображения сразу после сохранения
            $services = $pdo->query("SELECT * FROM services ORDER BY id ASC")->fetchAll();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Ошибка обновления: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DULGAR | Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Стили остаются без изменений для красоты */
        :root { --cream: #FAF8F5; --beige: #F0EBE3; --gold: #C9B99A; --dark: #1A1A1A; --gray: #8B8680; --white: #FFFFFF; --danger: #D9534F; --success: #5CB85C; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--cream); color: var(--dark); display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: var(--dark); color: var(--gold); padding: 40px 30px; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .logo { font-family: 'Cormorant Garamond', serif; font-size: 2.5em; text-align: center; border-bottom: 1px solid var(--gold); padding-bottom: 20px; color: white; }
        .logo span { color: var(--gold); }
        .nav-links { list-style: none; margin-top: 40px; }
        .nav-links li { margin-bottom: 15px; }
        .nav-links a { color: var(--gray); text-decoration: none; padding: 10px; display: block; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: white; border-left: 3px solid var(--gold); padding-left: 15px; }
        .main-content { margin-left: 280px; flex-grow: 1; padding: 40px; }
        .panel { background: white; padding: 40px; margin-bottom: 40px; border: 1px solid var(--beige); }
        .panel-title { font-family: 'Cormorant Garamond', serif; font-size: 2em; margin-bottom: 30px; color: var(--dark); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9em; color: var(--gray); }
        input[type="text"], textarea, select { width: 100%; padding: 12px; border: 1px solid var(--beige); background: var(--cream); }
        .btn { background: var(--dark); color: var(--gold); border: none; padding: 12px 30px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }
        .btn:hover { background: var(--gold); color: var(--dark); }
        .alert { background: #dff0d8; color: #3c763d; padding: 15px; margin-bottom: 20px; border: 1px solid #d6e9c6; }
        .portfolio-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .portfolio-item { position: relative; aspect-ratio: 3/4; }
        .portfolio-item img { width: 100%; height: 100%; object-fit: cover; }
        .delete-overlay { position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .portfolio-item:hover .delete-overlay { opacity: 1; }
        .btn-danger { background: var(--danger); color: white; border: none; padding: 5px 10px; cursor: pointer; }
        .service-editor { border: 1px solid var(--beige); padding: 20px; margin-bottom: 20px; background: var(--cream); }
        .service-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 15px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">DUL<span>GAR</span></div>
    <ul class="nav-links">
        <li><a href="#contacts" class="active">Контакты</a></li>
        <li><a href="#portfolio">Портфолио</a></li>
        <li><a href="#services">Услуги</a></li>
    </ul>
    <a href="index.php" target="_blank" style="margin-top: auto; color: var(--gold); text-decoration: none; text-align: center;">Открыть сайт →</a>
</aside>

<main class="main-content">
    <?php if ($message): ?><div class="alert"><?= $message ?></div><?php endif; ?>

    <!-- КОНТАКТЫ -->
    <section id="contacts" class="panel">
        <h2 class="panel-title">Контакты (ВК и Телефон)</h2>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Ссылка на ВКонтакте</label>
                    <input type="text" name="vk" value="<?= htmlspecialchars($contacts['vk'] ?? '') ?>" placeholder="https://vk.com/id...">
                </div>
                <div class="form-group">
                    <label>Номер телефона</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($contacts['phone'] ?? '') ?>" placeholder="+7 999 000 00 00">
                </div>
            </div>
            <button type="submit" name="save_contacts" class="btn">Сохранить контакты</button>
        </form>
    </section>

    <!-- ПОРТФОЛИО -->
    <section id="portfolio" class="panel">
        <h2 class="panel-title">Портфолио</h2>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px; display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label>Файл</label>
                <input type="file" name="photo_file" accept="image/*" required>
            </div>
            <div style="width: 200px;">
                <label>Категория</label>
                <select name="category">
                    <option value="Все">Все</option>
                    <option value="Индивидуальная">Индивидуальная</option>
                    <option value="Love Story">Love Story</option>
                    <option value="Семейная">Семейная</option>
                </select>
            </div>
            <button type="submit" name="add_photo" class="btn">Загрузить</button>
        </form>

        <div class="portfolio-grid">
            <?php foreach ($portfolio as $item): ?>
                <div class="portfolio-item">
                    <img src="<?= htmlspecialchars($item['src']) ?>" alt="">
                    <div class="delete-overlay">
                        <form method="POST" onsubmit="return confirm('Удалить?');">
                            <input type="hidden" name="delete_photo_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-danger">X</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- УСЛУГИ (ПРАЙС) -->
    <section id="services" class="panel">
        <h2 class="panel-title">Услуги и Прайс</h2>
        <form method="POST">
            <?php foreach ($services as $service): ?>
                <div class="service-editor">
                    <div class="service-row">
                        <input type="text" name="service_title[]" value="<?= htmlspecialchars($service['title']) ?>" placeholder="Название услуги">
                        <input type="text" name="service_price[]" value="<?= htmlspecialchars($service['price']) ?>" placeholder="Цена">
                    </div>
                    <textarea name="service_details[]" rows="2" placeholder="Описание через запятую"><?= htmlspecialchars($service['details']) ?></textarea>
                </div>
            <?php endforeach; ?>
            
            <!-- Поле для добавления новой услуги -->
            <div class="service-editor" style="border-style: dashed;">
                <div class="service-row">
                    <input type="text" name="service_title[]" placeholder="Новая услуга">
                    <input type="text" name="service_price[]" placeholder="Цена">
                </div>
                <textarea name="service_details[]" rows="2" placeholder="Описание"></textarea>
            </div>

            <button type="submit" name="save_services" class="btn">Обновить прайс-лист</button>
        </form>
    </section>
</main>

</body>
</html>