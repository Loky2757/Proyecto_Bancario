<?php
declare(strict_types=1);
class Account {
private PDO $db;
public function __construct(PDO $db) {
$this->db = $db;
}
/**
* Obtiene información actualizada de la cuenta y usuario mediante INNER JOIN.
*/
public function getAccountByUserId(int $userId): ?array {
$sql = "SELECT

u.id AS user_id,
u.username,
u.phone,
u.role,
a.id AS account_id,
a.account_number,
a.balance
FROM accounts a
INNER JOIN users u ON a.user_id = u.id
WHERE u.id = :uid
LIMIT 1";
$stmt = $this->db->prepare($sql);
$stmt->execute([':uid' => $userId]);
$row = $stmt->fetch();
return $row ?: null;
}
/**
* Búsqueda avanzada de clientes por usuario o número de cuenta (AJAX autocomplete).
*/
public function searchRecipients(string $query, int $excludeAccountId): array {
$cleanQuery = trim($query);
if ($cleanQuery === '') {
return [];
}
$sql = "SELECT

a.id AS account_id,
a.account_number,
u.username
FROM accounts a
INNER JOIN users u ON a.user_id = u.id
WHERE (u.username LIKE :q1 OR a.account_number LIKE :q2)
AND a.id != :exclude_id
AND u.role = 'client'
ORDER BY u.username ASC
LIMIT 10";
$stmt = $this->db->prepare($sql);
$term = '%' . $cleanQuery . '%';
$stmt->execute([
':q1' => $term,
':q2' => $term,
':exclude_id' => $excludeAccountId
]);
return $stmt->fetchAll();
}
}
?>
