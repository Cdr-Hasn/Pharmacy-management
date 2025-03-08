<?php
require_once 'includes/db.php';

$query = $_POST['query'] ?? '';
$stmt = $pdo->prepare("
    SELECT * FROM Produit 
    WHERE nom LIKE :query OR categorie LIKE :query OR reference LIKE :query 
    ORDER BY created_at DESC
");
$stmt->execute(['query' => "%$query%"]);
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $stock_class = $product['quantite'] < 5 ? 'stock-low' : ($product['quantite'] > 100 ? 'stock-high' : '');
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>" . htmlspecialchars($product['nom']) . "</td>";
    echo "<td>" . htmlspecialchars($product['categorie']) . "</td>";
    echo "<td>" . htmlspecialchars($product['reference']) . "</td>";
    echo "<td>" . number_format($product['prix'], 2) . "</td>";
    echo "<td class='$stock_class'>{$product['quantite']}</td>";
    echo "<td>" . htmlspecialchars($product['fournisseur'] ?? 'N/A') . "</td>";
    echo "<td>
        <button class='btn btn-warning' onclick='openModal(\"edit\", {$product['id']})'>Modifier</button>
        <button class='btn btn-danger' onclick='deleteProduct({$product['id']})'>Supprimer</button>
    </td>";
    echo "</tr>";
}