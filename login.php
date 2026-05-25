<?php
session_start();

// Если уже авторизован, перенаправляем на редактирование
if (isset($_SESSION['user_id'])) {
    header('Location: edit.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Пожалуйста, заполните оба поля';
    } else {
        // Подключение к БД
        $host = 'localhost';
        $dbname = 'u82671';
        $username = 'u82671';
        $password_db = '1266050';
        
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password_db,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Поиск пользователя
            $stmt = $pdo->prepare("SELECT id, login, password_hash, application_id FROM users WHERE login = :login");
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Успешный вход
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['application_id'] = $user['application_id'];
                $_SESSION['user_login'] = $user['login'];
                
                header('Location: edit.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка подключения к базе данных';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }
        .login-card {
            background: white;
            border-radius: 2rem;
            padding: 2.5rem;
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.35);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #1f2e45;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #5b6e8c;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1f2e45;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.95rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        .login-btn {
            width: 100%;
            background: linear-gradient(95deg, #1e3a5f, #0f2b44);
            border: none;
            padding: 0.9rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 2rem;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.3);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9eef3;
        }
        .register-link a {
            color: #3b82f6;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #5b6e8c;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 Вход в систему</h1>
                <p>Введите логин и пароль для редактирования данных</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="login">📝 Логин</label>
                    <input type="text" id="login" name="login" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Войти</button>
            </form>
            
            <div class="register-link">
                <p>Нет аккаунта? <a href="index.php">Заполните форму регистрации</a></p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem; color: #5b6e8c;">
                    Логин и пароль будут сгенерированы автоматически при отправке формы
                </p>
            </div>
            
            <a href="index.php" class="back-link">← Вернуться к форме регистрации</a>
        </div>
    </div>
</body>
</html>
