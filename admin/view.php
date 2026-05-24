<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

$host = 'localhost';
$dbname = 'u82671';
$username = 'u82671';
$password = '1266050';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных");
}

$id = $_GET['id'] ?? 0;

// ИСПРАВЛЕННЫЙ SQL-запрос - используем подзапросы
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        (SELECT GROUP_CONCAT(DISTINCT pl.name) 
         FROM application_languages al 
         JOIN programming_languages pl ON al.language_id = pl.id 
         WHERE al.application_id = a.id) as languages,
        (SELECT u.login 
         FROM users u 
         WHERE u.application_id = a.id LIMIT 1) as user_login,
        (SELECT u.created_at 
         FROM users u 
         WHERE u.application_id = a.id LIMIT 1) as user_created
    FROM applications a
    WHERE a.id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    die("Анкета не найдена");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр анкеты #<?php echo $app['id']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .detail-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .detail-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-body {
            padding: 2rem;
        }
        .detail-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-section h3 {
            color: #1e293b;
            margin-bottom: 1rem;
        }
        .detail-row {
            display: flex;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-label {
            width: 150px;
            font-weight: 600;
            color: #64748b;
        }
        .detail-value {
            flex: 1;
            color: #1e293b;
        }
        .back-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <div class="detail-header">
            <h2>📄 Анкета #<?php echo $app['id']; ?></h2>
            <a href="index.php" class="back-btn">← Назад</a>
        </div>
        <div class="detail-body">
            <div class="detail-section">
                <h3>👤 Личная информация</h3>
                <div class="detail-row">
                    <div class="detail-label">ФИО:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['fullname']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['email']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Телефон:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['phone'] ?? '-'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Дата рождения:</div>
                    <div class="detail-value"><?php echo $app['birthdate'] ?? '-'; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Пол:</div>
                    <div class="detail-value">
                        <?php
                        $genders = [
                            'male' => 'Мужской',
                            'female' => 'Женский',
                            'other' => 'Другой',
                            'unspecified' => 'Не указан'
                        ];
                        echo $genders[$app['gender']] ?? $app['gender'];
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>💻 Профессиональная информация</h3>
                <div class="detail-row">
                    <div class="detail-label">Языки программирования:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['languages'] ?? '-'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Биография:</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($app['biography'] ?? '-')); ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>🔐 Учётные данные</h3>
                <div class="detail-row">
                    <div class="detail-label">Логин:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['user_login'] ?? 'Не создан'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Дата регистрации:</div>
                    <div class="detail-value"><?php echo $app['user_created'] ? date('d.m.Y H:i:s', strtotime($app['user_created'])) : '-'; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Согласие с контрактом:</div>
                    <div class="detail-value"><?php echo $app['contract_agreed'] ? '✅ Да' : '❌ Нет'; ?></div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="edit.php?id=<?php echo $app['id']; ?>" class="back-btn" style="background: #f59e0b;">✏️ Редактировать</a>
                <a href="index.php" class="back-btn" style="background: #64748b;">📋 К списку</a>
            </div>
        </div>
    </div>
</body>
</html>
