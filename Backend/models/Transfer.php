<?php
declare(strict_types=1);
class Transfer {
private PDO $db;
public function __construct(PDO $db) {
$this->db = $db;
}
/**
* Ejecuta una transferencia atómica entre la cuenta origen y la cuenta destino.
*/
public function executeTransfer(int $senderAccountId, string $targetSearch, float $amount): array
{
$targetSearch = trim($targetSearch);
if ($targetSearch === '') {
return ['status' => false, 'message' => 'Debe indicar el usuario o número de cuenta

destinatario.'];
}
if ($amount <= 0) {
return ['status' => false, 'message' => 'La cantidad a transferir debe ser mayor a

$0.00.'];
}
$this->db->beginTransaction();
try {
$stmtSender = $this->db->prepare("SELECT id, account_number, balance FROM accounts WHERE

id = :id LIMIT 1 FOR UPDATE");

$stmtSender->execute([':id' => $senderAccountId]);
$sender = $stmtSender->fetch();
if (!$sender) {
throw new Exception("La cuenta emisora no es válida.");
}
if ((float)$sender['balance'] < $amount) {
throw new Exception("Saldo insuficiente. Su saldo disponible es $" .

number_format((float)$sender['balance'], 2));

}
$sqlReceiver = "SELECT a.id, a.account_number, u.username

FROM accounts a
INNER JOIN users u ON a.user_id = u.id
WHERE (a.account_number = :q1 OR u.username = :q2)
AND u.role = 'client'
LIMIT 1 FOR UPDATE";

$stmtReceiver = $this->db->prepare($sqlReceiver);
$stmtReceiver->execute([':q1' => $targetSearch, ':q2' => $targetSearch]);
$receiver = $stmtReceiver->fetch();
if (!$receiver) {
throw new Exception("No se encontró ningún asociado con el usuario o cuenta

'$targetSearch'.");
}
if ((int)$receiver['id'] === $senderAccountId) {
throw new Exception("Operación inválida: no puede transferirse dinero a su propia

cuenta.");
}
// Descontar saldo al emisor
$stmtDebit = $this->db->prepare("UPDATE accounts SET balance = balance - :amt WHERE id =

:id");

$stmtDebit->execute([':amt' => $amount, ':id' => $senderAccountId]);
// Acreditar saldo al receptor
$stmtCredit = $this->db->prepare("UPDATE accounts SET balance = balance + :amt WHERE id =

:id");

$stmtCredit->execute([':amt' => $amount, ':id' => $receiver['id']]);
// Registrar la transacción en la tabla transfers
$stmtLog = $this->db->prepare(
"INSERT INTO transfers (sender_account_id, receiver_account_id, amount) VALUES (:s,

:r, :a)"
);
$stmtLog->execute([
':s' => $senderAccountId,
':r' => $receiver['id'],
':a' => $amount
]);
$this->db->commit();
return [
'status' => true,
'message' => "Transferencia de $" . number_format($amount, 2) . " realizada a

{$receiver['username']} ({$receiver['account_number']})."

];
} catch (Exception $e) {
$this->db->rollBack();
return ['status' => false, 'message' => $e->getMessage()];
}
}
/**
* Consulta todos los movimientos de una cuenta (transferencias y depósitos) con INNER JOIN.
*/
public function getAccountMovements(int $accountId): array {
$sql = "(

SELECT t.created_at AS fecha, t.amount AS cantidad,
CASE WHEN t.sender_account_id = :acc_case THEN 'ENVIADA' ELSE 'RECIBIDA' END

AS tipo_movimiento,

u_dest.username AS destinatario_usuario, a_dest.account_number AS

destinatario_cuenta,

u_orig.username AS origen_usuario, a_orig.account_number AS origen_cuenta
FROM transfers t
INNER JOIN accounts a_dest ON t.receiver_account_id = a_dest.id
INNER JOIN users u_dest ON a_dest.user_id = u_dest.id
INNER JOIN accounts a_orig ON t.sender_account_id = a_orig.id
INNER JOIN users u_orig ON a_orig.user_id = u_orig.id
WHERE t.sender_account_id = :acc_s OR t.receiver_account_id = :acc_r
)
UNION ALL
(
SELECT d.created_at AS fecha, d.amount AS cantidad,
'DEPOSITO' AS tipo_movimiento,
u.username AS destinatario_usuario, a.account_number AS destinatario_cuenta,
'VENTANILLA / CÓDIGO' AS origen_usuario, c.code AS origen_cuenta
FROM deposits d
INNER JOIN accounts a ON d.account_id = a.id
INNER JOIN users u ON a.user_id = u.id
INNER JOIN codes c ON d.code_id = c.id
WHERE d.account_id = :acc_d
)
ORDER BY fecha DESC";
$stmt = $this->db->prepare($sql);
$stmt->execute([
':acc_case' => $accountId,
':acc_s' => $accountId,
':acc_r' => $accountId,
':acc_d' => $accountId
]);
return $stmt->fetchAll();
}
}
?>
