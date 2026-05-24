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
$message = '';

// Получаем данные анкеты
$stmt = $pdo->prepare("
    SELECT a.*, GROUP_CONCAT(pl.name) as languages
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    WHERE a.id = ?
    GROUP BY a.id
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    die("Анкета не найдена");
}

// Получаем список всех языков
$languages = $pdo->query("SELECT name FROM programming_languages ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$selectedLanguages = $app['languages'] ? explode(',', $app['languages']) : [];

// Обработка обновления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $birthdate = $_POST['birthdate'] ?: null;
    $gender = $_POST['gender'];
    $biography = trim($_POST['biography']);
    $contract = isset($_POST['contract_agreed']) ? 1 : 0;
    $newLanguages = $_POST['languages'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Обновляем application
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET fullname = ?, phone = ?, email = ?, birthdate = ?, 
                gender = ?, biography = ?, contract_agreed = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullname, $phone, $email, $birthdate, $gender, $biography, $contract, $id]);
        
        // Обновляем языки
        $stmtDel = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmtDel->execute([$id]);
        
        $stmtLang = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        
        foreach ($newLanguages as $langName) {
            $stmtLang->execute([$langName]);
            $langId = $stmtLang->fetchColumn();
            if ($langId) {
                $stmtInsert->execute([$id, $langId]);
            }
        }
        
        $pdo->commit();
        $message = '<div class="alert success">✅ Данные успешно обновлены</div>';
        
        // Обновляем данные для отображения
        $app['fullname'] = $fullname;
        $app['email'] = $email;
        $app['phone'] = $phone;
        $app['birthdate'] = $birthdate;
        $app['gender'] = $gender;
        $app['biography'] = $biography;
        $app['contract_agreed'] = $contract;
        $selectedLanguages = $newLanguages;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$genders = ['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой', 'unspecified' => 'Не указан'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование анкеты #<?php echo $id; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .edit-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .edit-body {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            font-size: 0.95rem;
        }
        .form-group select[multiple] {
            min-height: 150px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        .radio-group {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            cursor: pointer;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input {
            width: auto;
        }
        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-save {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-cancel {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            display: inline-block;
        }
        .hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h2>✏️ Редактирование анкеты #<?php echo $id; ?></h2>
            <a href="index.php" style="color: white; text-decoration: none;">← Назад</a>
        </div>
        <div class="edit-body">
            <?php echo $message; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>ФИО *</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($app['fullname']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($app['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($app['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birthdate" value="<?php echo $app['birthdate']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Пол</label>
                    <div class="radio-group">
                        <?php foreach ($genders as $value => $label): ?>
                            <label>
                                <input type="radio" name="gender" value="<?php echo $value; ?>" 
                                       <?php echo $app['gender'] == $value ? 'checked' : ''; ?>>
                                <?php echo $label; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Языки программирования *</label>
                    <select name="languages[]" multiple size="8" required>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo $lang; ?>" 
                                    <?php echo in_array($lang, $selectedLanguages) ? 'selected' : ''; ?>>
                                <?php echo $lang; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Удерживайте Ctrl (Cmd) для выбора нескольких языков</div>
                </div>
                
                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="biography" rows="5"><?php echo htmlspecialchars($app['biography'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="contract_agreed" id="contract" <?php echo $app['contract_agreed'] ? 'checked' : ''; ?>>
                        <label for="contract">Я ознакомлен(а) с условиями пользовательского соглашения *</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">💾 Сохранить изменения</button>
                    <a href="view.php?id=<?php echo $id; ?>" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>