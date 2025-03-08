<?php
require_once 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("
    INSERT INTO Produit (nom, categorie, reference, prix, quantite, fournisseur) 
    VALUES (:nom, :categorie, :reference, :prix, :quantite, :fournisseur)
");
$result = $stmt->execute([
    'nom' => $data['nom'],
    'categorie' => $data['categorie'],
    'reference' => $data['reference'],
    'prix' => $data['prix'],
    'quantite' => $data['quantite'],
    'fournisseur' => $data['fournisseur'] ?: null
]);
header('Content-Type: application/json');
echo json_encode(['success' => $result, 'message' => $result ? 'Produit ajouté' : 'Erreur lors de l\'ajout']);