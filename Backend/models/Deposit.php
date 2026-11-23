<?php
declare(strict_types=1);
class Deposit {
private PDO $db;
public function __construct(PDO $db) {
$this->db = $db;
}
/**
* Valida el código de depósito, acredita el saldo y registra la operación.
*/
public function makeDeposit(int $accountId, float $amount, string $validationCode): array {
$validationCode = trim($validationCode);
if ($amount <= 0) {
return ['status' => false, 'message' => 'La cantidad a ingresar debe ser mayor a $0.00.'];
}
if ($validationCode === '') {
return ['status' => false, 'message' => 'Debe proporcionar un código de validación.'];
}
$this->db->beginTransaction();
try {
$stmtCode = $this->db->prepare(
"SELECT id, amount, is_used FROM codes WHERE code = :c AND type = 'deposit' LIMIT 1

FOR UPDATE"
);
$stmtCode->execute([':c' => $validationCode]);
$codeRow = $stmtCode->fetch();
if (!$codeRow) {
throw new Exception("El código de validación '$validationCode' no existe.");
}
if ((int)$codeRow['is_used'] === 1) {
throw new Exception("Este código de validación ya fue canjeado anteriormente.");
}
if (abs((float)$codeRow['amount'] - $amount) > 0.001) {
$expected = number_format((float)$codeRow['amount'], 2);
throw new Exception("El monto no coincide con el valor del código (Valor válido:

$$expected MXN).");
}
// 1. Marcar el código como utilizado
$stmtUpdCode = $this->db->prepare("UPDATE codes SET is_used = 1, used_at = NOW() WHERE id

= :id");

$stmtUpdCode->execute([':id' => $codeRow['id']]);
// 2. Sumar el monto al saldo de la cuenta
$stmtUpdAcc = $this->db->prepare("UPDATE accounts SET balance = balance + :amt WHERE id =

:acc");

$stmtUpdAcc->execute([
':amt' => $amount,
':acc' => $accountId
]);
// 3. Registrar el depósito en la tabla deposits
$stmtInsDep = $this->db->prepare(
"INSERT INTO deposits (account_id, code_id, amount) VALUES (:acc, :cid, :amt)"
);
$stmtInsDep->execute([
':acc' => $accountId,
':cid' => $codeRow['id'],
':amt' => $amount
]);
$this->db->commit();
return [
'status' => true,
'message' => 'Depósito por $' . number_format($amount, 2) . ' MXN acreditado

exitosamente.'
];
} catch (Exception $e) {
$this->db->rollBack();
return ['status' => false, 'message' => $e->getMessage()];
}
}
}
?>