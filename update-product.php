<?php
require_once 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("
    UPDATE Produit SET 
        nom = :nom, 
        categorie = :categorie, 
        reference = :reference, 
        prix = :prix, 
        quantite = :quantite, 
        fournisseur = :fournisseur 
    WHERE id = :id
");
$result = $stmt->execute([
    'id' => $data['id'],
    'nom' => $data['nom'],
    'categorie' => $data['categorie'],
    'reference' => $data['reference'],
    'prix' => $data['prix'],
    'quantite' => $data['quantite'],
    'fournisseur' => $data['fournisseur'] ?: null
]);
header('Content-Type: application/json');
echo json_encode(['success' => $result, 'message' => $result ? 'Produit mis à jour' : 'Erreur lors de la mise à jour']);