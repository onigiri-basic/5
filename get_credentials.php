<?php
session_start();

if (isset($_SESSION['generated_login']) && isset($_SESSION['generated_password'])) {
    echo json_encode([
        'login' => $_SESSION['generated_login'],
        'password' => $_SESSION['generated_password']
    ]);
    // Удаляем после показа (показываем только один раз)
    unset($_SESSION['generated_login']);
    unset($_SESSION['generated_password']);
} else {
    echo json_encode(['login' => null, 'password' => null]);
}
?>