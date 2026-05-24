<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authorization Required';
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

// Поддержка поиска при экспорте (опционально)
$search = $_GET['search'] ?? '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "WHERE a.fullname LIKE :search OR a.email LIKE :search OR a.phone LIKE :search";
    $params[':search'] = "%$search%";
}

// Исправленный SQL-запрос
$sql = "
    SELECT 
        a.id,
        a.fullname,
        a.email,
        a.phone,
        a.birthdate,
        a.gender,
        a.biography,
        a.contract_agreed,
        a.created_at,
        (SELECT GROUP_CONCAT(DISTINCT pl.name ORDER BY pl.name SEPARATOR ', ') 
         FROM application_languages al 
         JOIN programming_languages pl ON al.language_id = pl.id 
         WHERE al.application_id = a.id) as languages,
        (SELECT u.login 
         FROM users u 
         WHERE u.application_id = a.id LIMIT 1) as user_login
    FROM applications a
    $searchCondition
    ORDER BY a.created_at DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$data = $stmt->fetchAll();

// Устанавливаем заголовки для CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="applications_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM для UTF-8

// Заголовки
fputcsv($output, [
    'ID',
    'ФИО', 
    'Email', 
    'Телефон', 
    'Дата рождения', 
    'Пол', 
    'Языки программирования',
    'Биография',
    'Согласие с контрактом', 
    'Дата создания', 
    'Логин пользователя'
]);

// Маппинг пола
$genderMap = [
    'male' => 'Мужской',
    'female' => 'Женский',
    'other' => 'Другой',
    'unspecified' => 'Не указан'
];

// Данные
foreach ($data as $row) {
    fputcsv($output, [
        $row['id'],
        $row['fullname'],
        $row['email'],
        $row['phone'] ?? '',
        $row['birthdate'] ?? '',
        $genderMap[$row['gender']] ?? $row['gender'],
        $row['languages'] ?? '',
        strip_tags($row['biography'] ?? ''),
        $row['contract_agreed'] ? 'Да' : 'Нет',
        date('d.m.Y H:i:s', strtotime($row['created_at'])),
        $row['user_login'] ?? ''
    ]);
}

fclose($output);
exit;
?>
