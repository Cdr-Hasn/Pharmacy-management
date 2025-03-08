<?php
require_once 'includes/db.php';

$id = $_POST['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM Produit WHERE id = ?");
$result = $stmt->execute([$id]);
echo json_encode(['success' => $result, 'message' => $result ? 'Produit supprimé' : 'Erreur lors de la suppression']);