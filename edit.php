<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id']) || !isset($_SESSION['application_id'])) {
    header('Location: login.php');
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

// Загрузка данных пользователя
$stmt = $pdo->prepare("
    SELECT a.*, GROUP_CONCAT(l.name) as languages_list
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages l ON al.language_id = l.id
    WHERE a.id = :application_id
    GROUP BY a.id
");
$stmt->execute([':application_id' => $_SESSION['application_id']]);
$userData = $stmt->fetch();

if (!$userData) {
    session_destroy();
    header('Location: login.php?error=data_not_found');
    exit;
}

// Преобразуем строку языков в массив
$userLanguages = $userData['languages_list'] ? explode(',', $userData['languages_list']) : [];

// Загрузка ошибок из cookies
$errors = [];
$error_cookie_name = 'form_errors';
if (isset($_COOKIE[$error_cookie_name])) {
    $errors = json_decode($_COOKIE[$error_cookie_name], true);
    setcookie($error_cookie_name, '', time() - 3600, '/');
}

// Функция для получения CSS класса ошибки
function getErrorClass($field, $errors) {
    return isset($errors[$field]) ? 'field-error' : '';
}

// Функция для получения сообщения об ошибке
function getErrorMessage($field, $errors) {
    if (isset($errors[$field])) {
        return $errors[$field]['message'] . ' ' . $errors[$field]['allowed_chars'];
    }
    return '';
}

// Функция для проверки выбранного языка
function isLanguageSelected($lang, $userLanguages) {
    return in_array($lang, $userLanguages) ? 'selected' : '';
}

// Функция для безопасного вывода
function safeOutput($value) {
    return htmlspecialchars($value ?? '');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование анкеты</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-header {
            background: linear-gradient(135deg, #1e3a5f, #0f2b44);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .user-info {
            font-size: 0.9rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .update-info {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="edit-header">
            <div>
                <h2>✏️ Редактирование анкеты</h2>
                <div class="user-info">👤 Вы вошли как: <?php echo safeOutput($_SESSION['user_login']); ?></div>
            </div>
            <a href="logout.php" class="logout-btn">🚪 Выйти</a>
        </div>
        
        <div class="form-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    ❌ При обработке формы обнаружены ошибки. Пожалуйста, исправьте их.
                </div>
                
                <div class="global-errors">
                    <strong>Пожалуйста, исправьте следующие ошибки:</strong>
                    <ul>
                        <?php foreach ($errors as $field => $error): ?>
                            <li><strong><?php echo ucfirst($field); ?>:</strong> <?php echo safeOutput($error['message']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="update-info">
                💡 Вы можете изменить любые данные. После редактирования нажмите "Обновить данные".
            </div>
            
            <form method="POST" action="process_form.php">
                <input type="hidden" name="update_data" value="1">
                
                <!-- ФИО -->
                <div class="field-group <?php echo getErrorClass('fullname', $errors); ?>">
                    <label for="fullname">👤 ФИО *</label>
                    <div class="input-wrapper">
                        <input type="text" id="fullname" name="fullname" 
                               value="<?php echo safeOutput($userData['fullname']); ?>" required>
                        <?php if (isset($errors['fullname'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('fullname', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Телефон -->
                <div class="field-group <?php echo getErrorClass('phone', $errors); ?>">
                    <label for="phone">📞 Телефон</label>
                    <div class="input-wrapper">
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo safeOutput($userData['phone']); ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('phone', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="field-group <?php echo getErrorClass('email', $errors); ?>">
                    <label for="email">✉️ E-mail *</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" 
                               value="<?php echo safeOutput($userData['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('email', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Дата рождения -->
                <div class="field-group <?php echo getErrorClass('birthdate', $errors); ?>">
                    <label for="birthdate">🎂 Дата рождения</label>
                    <div class="input-wrapper">
                        <input type="date" id="birthdate" name="birthdate" 
                               value="<?php echo safeOutput($userData['birthdate']); ?>">
                        <?php if (isset($errors['birthdate'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('birthdate', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Пол -->
                <div class="field-group <?php echo getErrorClass('gender', $errors); ?>">
                    <label>⚥ Пол</label>
                    <div class="input-wrapper radio-group">
                        <label><input type="radio" name="gender" value="male" <?php echo $userData['gender'] == 'male' ? 'checked' : ''; ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?php echo $userData['gender'] == 'female' ? 'checked' : ''; ?>> Женский</label>
                        <label><input type="radio" name="gender" value="other" <?php echo $userData['gender'] == 'other' ? 'checked' : ''; ?>> Другой</label>
                        <label><input type="radio" name="gender" value="unspecified" <?php echo $userData['gender'] == 'unspecified' ? 'checked' : ''; ?>> Не указан</label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                        <div class="error-message"><?php echo safeOutput(getErrorMessage('gender', $errors)); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Языки программирования -->
                <div class="field-group <?php echo getErrorClass('languages', $errors); ?>">
                    <label>💻 Любимые языки *</label>
                    <div class="input-wrapper">
                        <select name="fav_langs[]" id="fav_langs" multiple size="6" required>
                            <?php
                            $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                            foreach ($languages as $lang): ?>
                                <option value="<?php echo $lang; ?>" 
                                    <?php echo isLanguageSelected($lang, $userLanguages); ?>>
                                    <?php echo $lang; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size:0.7rem; color:#5b6e8c; margin-top:0.3rem;">
                            Удерживайте Ctrl (Cmd) для выбора нескольких языков
                        </div>
                        <?php if (isset($errors['languages'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('languages', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Биография -->
                <div class="field-group <?php echo getErrorClass('biography', $errors); ?>">
                    <label for="bio">📝 Биография</label>
                    <div class="input-wrapper">
                        <textarea id="bio" name="bio" rows="4"><?php echo safeOutput($userData['biography']); ?></textarea>
                        <?php if (isset($errors['biography'])): ?>
                            <div class="error-message"><?php echo safeOutput(getErrorMessage('biography', $errors)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Контракт -->
                <div class="field-group <?php echo getErrorClass('contract', $errors); ?>">
                    <label>📑 Согласие</label>
                    <div class="input-wrapper checkbox-wrapper">
                        <input type="checkbox" id="contractCheck" name="contract_agreed" 
                            <?php echo $userData['contract_agreed'] ? 'checked' : ''; ?>>
                        <label for="contractCheck">Я ознакомлен(а) с условиями пользовательского соглашения *</label>
                    </div>
                    <?php if (isset($errors['contract'])): ?>
                        <div class="error-message"><?php echo safeOutput(getErrorMessage('contract', $errors)); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Кнопки -->
                <div class="action-buttons" style="display: flex; gap: 1rem; justify-content: space-between;">
                    <a href="index.php" class="back-btn" style="background: #6c757d; text-decoration: none;">← Новая анкета</a>
                    <button type="submit" class="save-btn">💾 Обновить данные</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
