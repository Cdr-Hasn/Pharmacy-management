<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: oauth/login.php');
    exit;
}

$admin_name = isset($_SESSION['nom']) ? $_SESSION['nom'] : 'Administrateur';

// Fetch users
$users = $pdo->query("SELECT * FROM Utilisateur ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Same CSS as above */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f1c40f;
            --light: #f0f2f5;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--light); color: #333; display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px; background: var(--primary); color: var(--white); position: fixed;
            height: 100%; padding: 2rem 1rem; transition: width 0.3s ease; display: flex; flex-direction: column;
        }
        .sidebar a { display: block; color: var(--white); padding: 1rem; text-decoration: none; border-radius: 4px; margin: 0.5rem 0; transition: background 0.3s ease; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); }
        .sidebar .logout-link { margin-top: auto; }
        .main-content { margin-left: 250px; padding: 2rem; width: calc(100% - 250px); }
        .card { background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: var(--primary); color: var(--white); }
        tr:hover { background: #f5f5f5; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar span { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
    </style>
</head>
<body>
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

    <div class="main-content">
        <h1><i class="fas fa-users"></i> Utilisateurs - Bonjour <?= htmlspecialchars($admin_name) ?></h1>
        <div class="card">
            <h3>Liste des Utilisateurs</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date de création</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['role'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>