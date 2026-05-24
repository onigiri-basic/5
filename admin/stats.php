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

// Получаем статистику по языкам программирования
$stmt = $pdo->query("
    SELECT 
        pl.id,
        pl.name as language_name,
        COUNT(DISTINCT al.application_id) as users_count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id, pl.name
    ORDER BY users_count DESC, pl.name ASC
");
$languageStats = $stmt->fetchAll();

// Общее количество пользователей (анкет)
$totalUsers = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// Количество пользователей, выбравших хотя бы один язык
$usersWithLanguages = $pdo->query("
    SELECT COUNT(DISTINCT application_id) FROM application_languages
")->fetchColumn();

// Топ-5 языков
$topLanguages = array_slice($languageStats, 0, 5);

// Данные для графика (Chart.js)
$chartLabels = [];
$chartData = [];
foreach ($languageStats as $lang) {
    $chartLabels[] = $lang['language_name'];
    $chartData[] = $lang['users_count'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика языков программирования</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stats-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .stats-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .stats-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
        }
        .stats-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-card {
            background: white;
            padding: 1.25rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-2px);
        }
        .summary-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1e293b;
        }
        .summary-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .charts-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .chart-container {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 1rem;
        }
        .chart-container h3 {
            color: #1e293b;
            margin-bottom: 1rem;
            text-align: center;
        }
        .chart-canvas {
            max-height: 300px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .table-wrapper {
            padding: 0 2rem 2rem;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .stats-table th, .stats-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .stats-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #1e293b;
        }
        .stats-table tr:hover {
            background: #f8fafc;
        }
        .progress-bar {
            background: #e2e8f0;
            border-radius: 1rem;
            overflow: hidden;
            height: 1.5rem;
            width: 100%;
        }
        .progress-fill {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            height: 100%;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 0.5rem;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            transition: width 0.5s ease;
        }
        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #e2e8f0;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .rank-1 { background: #fbbf24; color: #78350f; }
        .rank-2 { background: #94a3b8; color: #1e293b; }
        .rank-3 { background: #cd7f32; color: white; }
        .back-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #2563eb;
        }
        .top-badge {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .charts-wrapper {
                grid-template-columns: 1fr;
            }
            .stats-header, .stats-summary, .charts-wrapper, .table-wrapper {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="stats-container">
        <div class="stats-header">
            <div>
                <h1>📊 Статистика языков программирования</h1>
                <p>Анализ предпочтений пользователей</p>
            </div>
            <div>
                <a href="index.php" class="back-btn">← Вернуться к списку</a>
            </div>
        </div>
        
        <div class="stats-summary">
            <div class="summary-card">
                <div class="summary-number"><?php echo $totalUsers; ?></div>
                <div class="summary-label">Всего пользователей</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo $usersWithLanguages; ?></div>
                <div class="summary-label">Выбрали хотя бы один язык</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo count($languageStats); ?></div>
                <div class="summary-label">Доступных языков</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">
                    <?php 
                    $totalSelections = array_sum(array_column($languageStats, 'users_count'));
                    $avgPerUser = $usersWithLanguages > 0 ? round($totalSelections / $usersWithLanguages, 1) : 0;
                    echo $avgPerUser;
                    ?>
                </div>
                <div class="summary-label">Языков в среднем на пользователя</div>
            </div>
        </div>
        
        <div class="charts-wrapper">
            <div class="chart-container">
                <h3>📊 Популярность языков (столбчатая диаграмма)</h3>
                <canvas id="barChart" class="chart-canvas"></canvas>
            </div>
            <div class="chart-container">
                <h3>🥧 Распределение предпочтений</h3>
                <canvas id="pieChart" class="chart-canvas"></canvas>
            </div>
        </div>
        
        <div class="table-wrapper">
            <h3 style="margin-bottom: 1rem; color: #1e293b;">🏆 Детальная статистика по языкам</h3>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Язык программирования</th>
                        <th>Количество пользователей</th>
                        <th>Процент от всех пользователей</th>
                        <th>Процент от выбравших языки</th>
                        <th>Популярность</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    $maxCount = $languageStats[0]['users_count'] ?? 1;
                    foreach ($languageStats as $lang): 
                        $percentageOfTotal = $totalUsers > 0 ? round($lang['users_count'] / $totalUsers * 100, 1) : 0;
                        $percentageOfLangUsers = $usersWithLanguages > 0 ? round($lang['users_count'] / $usersWithLanguages * 100, 1) : 0;
                        $barWidth = ($lang['users_count'] / $maxCount) * 100;
                        
                        $rankClass = '';
                        if ($rank == 1) $rankClass = 'rank-1';
                        elseif ($rank == 2) $rankClass = 'rank-2';
                        elseif ($rank == 3) $rankClass = 'rank-3';
                    ?>
                        <tr>
                            <td>
                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($lang['language_name']); ?></strong>
                                <?php if ($rank <= 3): ?>
                                    <span class="top-badge">TOP <?php echo $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $lang['users_count']; ?></td>
                            <td><?php echo $percentageOfTotal; ?>%</td>
                            <td><?php echo $percentageOfLangUsers; ?>%</td>
                            <td style="min-width: 150px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $barWidth; ?>%;">
                                        <?php if ($barWidth > 15): ?>
                                            <?php echo $lang['users_count']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
            
            <?php if (empty($languageStats)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    Нет данных для отображения
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Данные для графиков
        const labels = <?php echo json_encode($chartLabels); ?>;
        const data = <?php echo json_encode($chartData); ?>;
        
        // Цветовая палитра
        const colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
            '#14b8a6', '#d946ef'
        ];
        
        // Столбчатая диаграмма
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Количество пользователей',
                    data: data,
                    backgroundColor: colors,
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw + ' пользователей';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Количество пользователей'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Языки программирования'
                        },
                        ticks: {
                            rotate: 45,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
        
        // Круговая диаграмма
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} пользователей (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>