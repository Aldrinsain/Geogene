<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$activeNav = 'tree';

$stmt = $pdo->prepare("SELECT * FROM persons WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$self = $stmt->fetch();

$parents = $grandparents = $children = $spouses = $siblings = [];

if ($self) {
    $selfId = $self['person_id'];

    $fetchByType = function ($fromId, $type) use ($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.* FROM relationships r
            JOIN persons p ON p.person_id = r.related_person_id
            WHERE r.person_id = ? AND r.relationship_type = ?
        ");
        $stmt->execute([$fromId, $type]);
        return $stmt->fetchAll();
    };

    $parents  = $fetchByType($selfId, 'parent');
    $children = $fetchByType($selfId, 'child');
    $spouses  = $fetchByType($selfId, 'spouse');
    $siblings = $fetchByType($selfId, 'sibling');

    // Grandparents = parents of parents
    foreach ($parents as $p) {
        $grandparents = array_merge($grandparents, $fetchByType($p['person_id'], 'parent'));
    }
}

function nodeHtml($p, $gen, $rel, $isSelf = false) {
    $initial = strtoupper(substr($p['first_name'], 0, 1));
    $name = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);
    $cls = $isSelf ? 'tree-node self' : 'tree-node';
    echo '<div class="' . $cls . '" data-gen="' . $gen . '" data-rel="' . $rel . '">
            <div class="tree-avatar">' . $initial . '</div>
            <div class="tree-name">' . $name . '</div>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Family Tree</title>
<link rel="stylesheet" href="../assets/css/main.css">