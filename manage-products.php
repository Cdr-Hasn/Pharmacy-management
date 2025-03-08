<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: oauth/login.php');
    exit;
}

$admin_name = isset($_SESSION['nom']) ? $_SESSION['nom'] : 'Administrateur';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total products for pagination
$total_products = $pdo->query("SELECT COUNT(*) FROM Produit")->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Fetch products with pagination
$products = $pdo->query("SELECT * FROM Produit ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Gestion des Produits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .card { background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; transition: background 0.3s ease; }
        th { background: var(--primary); color: var(--white); }
        tr:hover { background: #f5f5f5; }
        .search-bar { margin-bottom: 1rem; padding: 0.8rem; width: 100%; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; transition: transform 0.2s ease, background 0.3s ease; }
        .btn:hover { transform: scale(1.05); }
        .btn-success { background: var(--success); color: var(--white); }
        .btn-danger { background: var(--danger); color: var(--white); }
        .btn-warning { background: var(--warning); color: #333; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; }
        .modal-content { background: var(--white); padding: 2rem; width: 70%; max-width: 500px; border-radius: 8px; position: relative; }
        .pagination { margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center; }
        .pagination a { padding: 0.5rem 1rem; background: var(--primary); color: var(--white); text-decoration: none; border-radius: 4px; transition: background 0.3s ease; }
        .pagination a:hover { background: var(--secondary); }
        .stock-low { color: var(--danger); font-weight: bold; }
        .stock-high { color: var(--warning); font-weight: bold; }
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
        <h1><i class="fas fa-boxes"></i> Gestion des Produits - Bonjour <?= htmlspecialchars($admin_name) ?></h1>
        <div class="card">
            <h3>Liste des Produits</h3>
            <input type="text" id="search" class="search-bar" placeholder="Rechercher par nom, catégorie ou référence...">
            <button class="btn btn-success" onclick="openModal('add')">Ajouter Produit</button>
            <table id="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Référence</th>
                        <th>Prix (€)</th>
                        <th>Quantité</th>
                        <th>Fournisseur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="products-body">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['nom']) ?></td>
                            <td><?= htmlspecialchars($product['categorie']) ?></td>
                            <td><?= htmlspecialchars($product['reference']) ?></td>
                            <td><?= number_format($product['prix'], 2) ?></td>
                            <td class="<?= $product['quantite'] < 5 ? 'stock-low' : ($product['quantite'] > 100 ? 'stock-high' : '') ?>">
                                <?= $product['quantite'] ?>
                            </td>
                            <td><?= htmlspecialchars($product['fournisseur'] ?? 'N/A') ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="openModal('edit', <?= $product['id'] ?>)">Modifier</button>
                                <button class="btn btn-danger" onclick="deleteProduct(<?= $product['id'] ?>)">Supprimer</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" <?= $i === $page ? 'style="background: var(--secondary);"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <h2 id="modal-title"></h2>
            <form id="product-form">
                <input type="hidden" id="product-id">
                <div style="margin-bottom: 1rem;">
                    <label>Nom:</label>
                    <input type="text" id="nom" class="search-bar" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Catégorie:</label>
                    <input type="text" id="categorie" class="search-bar" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Référence:</label>
                    <input type="text" id="reference" class="search-bar" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Prix (€):</label>
                    <input type="number" step="0.01" id="prix" class="search-bar" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Quantité:</label>
                    <input type="number" id="quantite" class="search-bar" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Fournisseur:</label>
                    <input type="text" id="fournisseur" class="search-bar">
                </div>
                <button type="submit" class="btn btn-success">Enregistrer</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Annuler</button>
            </form>
        </div>
    </div>

    <script>
        // AJAX Search
        document.getElementById('search').addEventListener('input', function(e) {
            const query = e.target.value;
            fetch('search_products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'query=' + encodeURIComponent(query)
            })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau: ' + response.status);
                return response.text();
            })
            .then(data => {
                document.getElementById('products-body').innerHTML = data;
            })
            .catch(error => {
                console.error('Erreur lors de la recherche:', error);
                alert('Erreur lors de la recherche. Vérifiez la console pour plus de détails.');
            });
        });

        // Modal Functions
        function openModal(action, id = null) {
            const modal = document.getElementById('product-modal');
            const form = document.getElementById('product-form');
            const title = document.getElementById('modal-title');

            if (action === 'add') {
                title.textContent = 'Ajouter Produit';
                form.reset();
                document.getElementById('product-id').value = '';
            } else if (action === 'edit' && id) {
                title.textContent = 'Modifier Produit';
                fetch('get_product.php?id=' + id)
                    .then(response => {
                        if (!response.ok) throw new Error('Erreur réseau: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('product-id').value = data.id;
                        document.getElementById('nom').value = data.nom;
                        document.getElementById('categorie').value = data.categorie;
                        document.getElementById('reference').value = data.reference;
                        document.getElementById('prix').value = data.prix;
                        document.getElementById('quantite').value = data.quantite;
                        document.getElementById('fournisseur').value = data.fournisseur || '';
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement du produit:', error);
                        alert('Erreur lors du chargement du produit.');
                    });
            }
            modal.style.display = 'flex'; // Use flex for centering
        }

        function closeModal() {
            document.getElementById('product-modal').style.display = 'none';
        }

        // AJAX CRUD
        document.getElementById('product-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('product-id').value;
            const data = {
                id: id,
                nom: document.getElementById('nom').value,
                categorie: document.getElementById('categorie').value,
                reference: document.getElementById('reference').value,
                prix: document.getElementById('prix').value,
                quantite: document.getElementById('quantite').value,
                fournisseur: document.getElementById('fournisseur').value
            };

            fetch(id ? 'update_product.php' : 'add_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau: ' + response.status);
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement:', error);
                alert('Erreur lors de l\'enregistrement du produit.');
            });
        });

        function deleteProduct(id) {
            if (confirm('Voulez-vous vraiment supprimer ce produit ?')) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau: ' + response.status);
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la suppression:', error);
                    alert('Erreur lors de la suppression du produit.');
                });
            }
        }
    </script>
</body>
</html>