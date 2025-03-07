<?php
session_start();
require_once 'includes/db.php';

// Vérifier la connexion et le rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: ../oauth/login.php');
    exit;
}

// Récupérer les informations du vendeur
$stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les produits en stock
$products = $pdo->query("SELECT * FROM Produit WHERE quantite > 0 ORDER BY nom")->fetchAll();

// Récupérer la dernière vente
$lastSale = $pdo->query("SELECT v.*, p.nom FROM Ventes v 
                        JOIN Produit p ON v.product_id = p.id 
                        WHERE v.user_id = {$_SESSION['user_id']} 
                        ORDER BY v.created_at DESC LIMIT 1")->fetch();

// Traitement du formulaire de vente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM Produit WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product && $product['quantite'] >= $quantity) {
            $newQuantity = $product['quantite'] - $quantity;
            $stmt = $pdo->prepare("UPDATE Produit SET quantite = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $productId]);

            $total = $quantity * $product['prix'];
            $stmt = $pdo->prepare("INSERT INTO Ventes (user_id, product_id, quantite, prix_total) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $productId, $quantity, $total]);

            $pdo->commit();
            $success = "Vente enregistrée avec succès!";
            header("Refresh:0"); // Rafraîchir la page pour mettre à jour la dernière vente
        } else {
            $error = "Stock insuffisant pour cette vente";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la transaction: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Vendeur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
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
            line-height: 1.6;
        }

        .header {
            background: var(--primary);
            color: var(--white);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .grid {
            display: grid;
            gap: 2rem;
        }

        .card {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sales-form select, .sales-form input {
            width: 100%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary { background: var(--secondary); color: var(--white); }
        .btn-danger { background: var(--danger); color: var(--white); }
        .alert-success { background: var(--success); color: var(--white); padding: 1rem; border-radius: 4px; }
        .alert-danger { background: var(--danger); color: var(--white); padding: 1rem; border-radius: 4px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: var(--primary);
            color: var(--white);
        }

        tr:hover {
            background: #f8f9fa;
        }

        @media (min-width: 768px) {
            .grid {
                grid-template-columns: 1fr 2fr;
            }
        }

        @media (max-width: 767px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            .sales-form, .stock-list {
                margin: 1rem 0;
            }
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1><i class="fas fa-store"></i> CDR Pharma - Bonjour <?= htmlspecialchars($user['nom']) ?>!</h1>
        <a href="../oauth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </header>

    <div class="container">
        <div class="grid">
            <!-- Formulaire de vente et dernière vente -->
            <div>
                <section class="card sales-form">
                    <h2><i class="fas fa-cart-plus"></i> Nouvelle Vente</h2>
                    <?php if(isset($success)): ?>
                        <div class="alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if(isset($error)): ?>
                        <div class="alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <label for="product_id">Produit</label>
                        <select name="product_id" id="product_id" required>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['nom']) ?> - 
                                    Stock: <?= $product['quantite'] ?> - 
                                    <?= number_format($product['prix'], 2) ?> €
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="quantity">Quantité</label>
                        <input type="number" name="quantity" id="quantity" min="1" required>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Valider
                        </button>
                    </form>
                </section>

                <?php if($lastSale): ?>
                    <section class="card" style="margin-top: 2rem;">
                        <h2><i class="fas fa-history"></i> Dernière Vente</h2>
                        <p>Produit: <?= htmlspecialchars($lastSale['nom']) ?></p>
                        <p>Quantité: <?= $lastSale['quantite'] ?></p>
                        <p>Total: <?= number_format($lastSale['prix_total'], 2) ?> €</p>
                        <p>Date: <?= date('d/m/Y H:i', strtotime($lastSale['created_at'])) ?></p>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Tableau des stocks -->
            <section class="card stock-list">
                <h2><i class="fas fa-boxes"></i> Stock Disponible</h2>
                <?php if(empty($products)): ?>
                    <p>Aucun produit en stock</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Fournisseur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['nom']) ?></td>
                                    <td><?= htmlspecialchars($product['categorie']) ?></td>
                                    <td><?= number_format($product['prix'], 2) ?> €</td>
                                    <td><?= $product['quantite'] ?></td>
                                    <td><?= htmlspecialchars($product['fournisseur']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>