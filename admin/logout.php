<?php
// HTTP Basic авторизация не имеет прямого logout
// Отправляем заголовок 401 для сброса авторизации
header('HTTP/1.0 401 Unauthorized');
header('WWW-Authenticate: Basic realm="Admin Panel"');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Выход из админ-панели</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: system-ui;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            margin: 0;
        }
        .logout-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 400px;
        }
        .btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <h2>🔐 Выход из админ-панели</h2>
        <p>Вы успешно вышли из системы.</p>
        <p><small>Закройте браузер для полного завершения сессии, либо нажмите кнопку ниже.</small></p>
        <a href="index.php" class="btn">Войти снова</a>
    </div>
</body>
</html>