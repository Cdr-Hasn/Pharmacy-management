<?php
require_once 'includes/db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM Produit WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($product);