<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: oauth/login.php');
    exit;
}

// Get admin name safely
$admin_name = isset($_SESSION['nom']) ? $_SESSION['nom'] : 'Administrateur';

// Statistiques générales
$stats = $pdo->query("
    SELECT 
        (SELECT SUM(prix_total) FROM Ventes WHERE DATE(created_at) = CURDATE()) AS daily_sales,
        (SELECT SUM(prix_total) FROM Ventes WHERE MONTH(created_at) = MONTH(CURDATE())) AS monthly_sales,
        (SELECT COUNT(*) FROM Produit WHERE quantite < 5) AS low_stock,
        (SELECT COUNT(*) FROM Produit WHERE quantite > 100) AS high_stock
")->fetch();

// Top vendeurs
$top_sellers = $pdo->query("
    SELECT u.nom, SUM(v.prix_total) as total, COUNT(v.id) as sales_count 
    FROM Ventes v 
    JOIN Utilisateur u ON v.user_id = u.id 
    GROUP BY v.user_id, u.nom 
    ORDER BY total DESC LIMIT 3
")->fetchAll();

// Données pour le graphique avec format de date personnalisé
$monthly_data = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%d %M %Y') as month, SUM(prix_total) as total 
    FROM Ventes 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at
")->fetchAll();

$daily_data = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%d %M %Y') as day, SUM(prix_total) as total 
    FROM Ventes 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY created_at
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f1c40f;
            --light: #f0f2f5;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: var(--light);
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--primary);
            color: var(--white);
            position: fixed;
            height: 100%;
            padding: 2rem 1rem;
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar a {
            display: block;
            color: var(--white);
            padding: 1rem;
            text-decoration: none;
            border-radius: 4px;
            margin: 0.5rem 0;
            transition: background 0.3s ease;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
        }

        .sidebar .logout-link {
            margin-top: auto; /* Pushes logout to bottom */
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }

        .chart-container {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .sidebar span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div>
            <h2 style="margin-bottom: 2rem;"><i class="fas fa-tachometer-alt"></i> Admin</h2>
            <a href="manage-products.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a href="sales.php"><i class="fas fa-shopping-cart"></i> <span>Ventes</span></a>
            <a href="users.php"><i class="fas fa-users"></i> <span>Utilisateurs</span></a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        </div>
        <a href="oauth/logout.php" class="logout-link" style="background: var(--danger);"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <h1><i class="fas fa-chart-line"></i> Reporting - Bonjour <?= htmlspecialchars($admin_name) ?></h1>

        <!-- Statistiques -->
        <div class="dashboard-grid">
            <div class="card">
                <h3><i class="fas fa-coins"></i> Ventes Journalières</h3>
                <div class="stat-value"><?= number_format($stats['daily_sales'] ?? 0, 2) ?> Ar</div>
            </div>
            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Ventes Mensuelles</h3>
                <div class="stat-value"><?= number_format($stats['monthly_sales'] ?? 0, 2) ?> Ar</div>
            </div>
            <div class="card">
                <h3><i class="fas fa-exclamation-triangle"></i> Stock Critique</h3>
                <div class="stat-value" style="color: var(--danger);"><?= $stats['low_stock'] ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-warehouse"></i> Stock Élevé</h3>
                <div class="stat-value" style="color: var(--warning);"><?= $stats['high_stock'] ?></div>
            </div>
        </div>

        <!-- Top Vendeurs -->
        <div class="dashboard-grid">
            <div class="card" style="grid-column: span 2;">
                <h3><i class="fas fa-trophy"></i> Top Vendeurs</h3>
                <?php foreach($top_sellers as $i => $seller): ?>
                    <div style="margin: 1rem 0;">
                        <strong>#<?= $i+1 ?> <?= htmlspecialchars($seller['nom']) ?></strong>
                        <p><?= number_format($seller['total'], 2) ?> Ar (<?= $seller['sales_count'] ?> ventes)</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Graphiques -->
            <div class="card" style="grid-column: span 2;">
                <h3><i class="fas fa-chart-area"></i> Évolution des Ventes</h3>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('salesChart').getContext('2d');
        const monthlyLabels = [<?php echo "'" . implode("','", array_column($monthly_data, 'month')) . "'"; ?>];
        const monthlyData = [<?php echo implode(',', array_column($monthly_data, 'total')); ?>];
        const dailyLabels = [<?php echo "'" . implode("','", array_column($daily_data, 'day')) . "'"; ?>];
        const dailyData = [<?php echo implode(',', array_column($daily_data, 'total')); ?>];

        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Ventes Mensuelles (Ar)',
                    data: monthlyData,
                    borderColor: 'var(--primary)',
                    backgroundColor: 'rgba(44, 62, 80, 0.2)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Ventes Journalières (Ar)',
                    data: dailyData,
                    borderColor: 'var(--secondary)',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'category',
                        labels: monthlyLabels.concat(dailyLabels)
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Montant (Ar)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>