<?php
// Проверка HTTP-авторизации
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authorization Required';
    exit;
}

// Подключение к БД
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

// Обработка DELETE запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header('Location: index.php?deleted=1');
    exit;
}

// Поиск
$search = $_GET['search'] ?? '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "WHERE a.fullname LIKE :search OR a.email LIKE :search OR a.phone LIKE :search";
    $params[':search'] = "%$search%";
}

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Получаем общее количество записей
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM applications a $searchCondition");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// ИСПРАВЛЕННЫЙ SQL-запрос - используем подзапросы вместо GROUP BY
$sql = "
    SELECT 
        a.*,
        (SELECT GROUP_CONCAT(DISTINCT pl.name) 
         FROM application_languages al 
         JOIN programming_languages pl ON al.language_id = pl.id 
         WHERE al.application_id = a.id) as languages,
        (SELECT u.login 
         FROM users u 
         WHERE u.application_id = a.id LIMIT 1) as user_login
    FROM applications a
    $searchCondition
    ORDER BY a.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Управление анкетами</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1>📊 Админ-панель</h1>
                <p>Управление анкетами пользователей</p>
            </div>
            <div class="admin-user">
                <span>👑 Администратор: <?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER']); ?></span>
                <a href="logout.php" class="logout-link">🚪 Выйти</a>
            </div>
        </div>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalRecords; ?></div>
                <div class="stat-label">Всего анкет</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></div>
                <div class="stat-label">Зарегистрированных пользователей</div>
            </div>
        </div>
        
        <div class="admin-controls">
            <a href="stats.php" class="stats-btn">📈 Статистика языков</a>
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Поиск по ФИО, email или телефону..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Найти</button>
                <?php if ($search): ?>
                    <a href="index.php" class="reset-btn">Сбросить</a>
                <?php endif; ?>
            </form>
            <a href="export.php" class="export-btn">📥 Экспорт в CSV</a>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert success">✅ Анкета успешно удалена</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert success">✅ Данные успешно обновлены</div>
        <?php endif; ?>
        
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Дата создания</th>
                        <th>Логин</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">Нет данных</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo $app['id']; ?></td>
                                <td><?php echo htmlspecialchars($app['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo htmlspecialchars($app['phone'] ?? '-'); ?></td>
                                <td><?php echo $app['birthdate'] ?? '-'; ?></td>
                                <td>
                                    <?php
                                    $genders = [
                                        'male' => '♂ Мужской',
                                        'female' => '♀ Женский',
                                        'other' => '⚥ Другой',
                                        'unspecified' => '❓ Не указан'
                                    ];
                                    echo $genders[$app['gender']] ?? $app['gender'];
                                    ?>
                                </td>
                                <td class="languages-cell"><?php echo htmlspecialchars($app['languages'] ?? '-'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($app['user_login'] ?? '-'); ?></td>
                                <td class="actions">
                                    <a href="view.php?id=<?php echo $app['id']; ?>" class="btn-view">👁️</a>
                                    <a href="edit.php?id=<?php echo $app['id']; ?>" class="btn-edit">✏️</a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Удалить анкету №<?php echo $app['id']; ?>?')">
                                        <input type="hidden" name="delete_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn-delete">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
