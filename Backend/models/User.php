<?php
declare(strict_types=1);
    class User {
    private PDO $db;
    public function __construct(PDO $db) {
    $this->db = $db;
     }
/**
* Registra un nuevo usuario (cliente o administrador) y crea su cuenta bancaria.
*/
public function register(
string $username,
string $password,
string $phone,
bool $isAdmin,
string $adminCode = ''
): array {
$username = trim($username);
$phone = trim($phone);
$adminCode = trim($adminCode);
if ($username === '' || $password === '' || $phone === '') {
return ['status' => false, 'message' => 'Todos los campos obligatorios deben

completarse.'];
}
if (strlen($password) < 4) {
return ['status' => false, 'message' => 'La contraseña debe tener al menos 4

caracteres.'];
}
$this->db->beginTransaction();
try {
$role = 'client';
$codeId = null;
if ($isAdmin) {
if ($adminCode === '') {
throw new Exception("Debe ingresar el código de verificación de administrador.");
  }
// Verificar que el nombre de usuario no exista previamente
$stmtCheck = $this->db->prepare("SELECT id FROM users WHERE username = :username LIMIT

1");

$stmtCheck->execute([':username' => $username]);
if ($stmtCheck->fetch()) {
throw new Exception("El nombre de usuario '$username' ya se encuentra registrado.");
}
// Insertar usuario con contraseña encriptada (BCRYPT)
$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$stmtUser = $this->db->prepare(
"INSERT INTO users (username, password, phone, role) VALUES (:u, :p, :ph, :r)"
);
$stmtUser->execute([
':u' => $username,
':p' => $passwordHash,
':ph' => $phone,
':r' => $role
]);
$userId = (int)$this->db->lastInsertId();
// Generar número de cuenta único de 12 dígitos
$accountNumber = $this->generateUniqueAccountNumber();
$stmtAcc = $this->db->prepare(
"INSERT INTO accounts (user_id, account_number, balance) VALUES (:uid, :acc, 0.00)"
);
$stmtAcc->execute([
':uid' => $userId,
':acc' => $accountNumber
]);
if ($isAdmin && $codeId !== null) {
$stmtUseCode = $this->db->prepare("UPDATE codes SET is_used = 1, used_at = NOW() WHERE

id = :cid");

$stmtUseCode->execute([':cid' => $codeId]);
}
$this->db->commit();
$roleLabel = ($role === 'admin') ? 'Administrador' : 'Cliente';
return [
'status' => true,
'message' => "Registro exitoso como $roleLabel. Su número de cuenta es:

$accountNumber"
];
} catch (Exception $e) {
$this->db->rollBack();
return ['status' => false, 'message' => $e->getMessage()];
}
}
/**
* Genera un número de cuenta único verificando colisiones en MySQL.
*/
private function generateUniqueAccountNumber(): string {
do {
$candidate = '4001' . str_pad((string)random_int(10000000, 99999999), 8, '0',

STR_PAD_LEFT);

$stmt = $this->db->prepare("SELECT id FROM accounts WHERE account_number = :acc LIMIT 1");
$stmt->execute([':acc' => $candidate]);
} while ($stmt->fetch());
return $candidate;
}
/**
* Autentica un usuario mediante INNER JOIN con su cuenta bancaria.
*/
public function login(string $username, string $password): array {
$username = trim($username);
if ($username === '' || $password === '') {
return ['status' => false, 'message' => 'Ingrese usuario y contraseña.'];
}
$sql = "SELECT u.id, u.username, u.password, u.phone, u.role,
a.id AS account_id, a.account_number, a.balance
FROM users u
INNER JOIN accounts a ON u.id = a.user_id
WHERE u.username = :username
LIMIT 1";
$stmt = $this->db->prepare($sql);
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();
if ($user && password_verify($password, $user['password'])) {
unset($user['password']);
return ['status' => true, 'user' => $user];
}
return ['status' => false, 'message' => 'Credenciales incorrectas. Verifique usuario y
contraseña.'];
}
/**
* Obtiene todos los clientes registrados (Vista de Administrador).
*/
public function getAllClients(): array {
$sql = "SELECT u.id, u.username, u.phone, u.created_at,
a.id AS account_id, a.account_number, a.balance
FROM users u
INNER JOIN accounts a ON u.id = a.user_id
WHERE u.role = 'client'
ORDER BY u.id ASC";
$stmt = $this->db->query($sql);
return $stmt->fetchAll();
}
}
?>
