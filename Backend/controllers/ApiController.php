<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Account.php';
require_once __DIR__ . '/../models/Deposit.php';
require_once __DIR__ . '/../models/Transfer.php';
try {
$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$accountModel = new Account($db);
$depositModel = new Deposit($db);
$transferModel = new Transfer($db);
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');
switch ($action) {
case 'register':
$res = $userModel->register(
(string)($input['username'] ?? ''),
(string)($input['password'] ?? ''),
(string)($input['phone'] ?? ''),
(bool)($input['is_admin'] ?? false),
(string)($input['admin_code'] ?? '')
);
echo json_encode($res);
break;
case 'login':
$res = $userModel->login(
(string)($input['username'] ?? ''),
(string)($input['password'] ?? '')
);
if ($res['status']) {
$_SESSION['user'] = $res['user'];
}
echo json_encode($res);
break;
case 'logout':
$_SESSION = [];
session_destroy();
echo json_encode(['status' => true, 'message' => 'Sesión cerrada correctamente.']);
break;
case 'session_info':
if (!isset($_SESSION['user'])) {
echo json_encode(['status' => false, 'message' => 'No hay sesión activa.']);
break;
}
$fresh = $accountModel->getAccountByUserId((int)$_SESSION['user']['id']);
if ($fresh) {
$_SESSION['user']['balance'] = $fresh['balance'];
}
echo json_encode(['status' => true, 'user' => $_SESSION['user']]);
break;
case 'search_clients':
if (!isset($_SESSION['user'])) {
throw new Exception("Acceso no autorizado.");
}
$q = (string)($input['query'] ?? '');
$accId = (int)$_SESSION['user']['account_id'];
echo json_encode(['status' => true, 'results' => $accountModel->searchRecipients($q,

$accId)]);
break;
case 'transfer':
if (!isset($_SESSION['user'])) {
throw new Exception("Su sesión ha expirado. Inicie sesión nuevamente.");
}
$res = $transferModel->executeTransfer(
(int)$_SESSION['user']['account_id'],
(string)($input['target'] ?? ''),
(float)($input['amount'] ?? 0)
);
echo json_encode($res);
break;
case 'deposit':
if (!isset($_SESSION['user'])) {
throw new Exception("Su sesión ha expirado. Inicie sesión nuevamente.");
}
$res = $depositModel->makeDeposit(
(int)$_SESSION['user']['account_id'],
(float)($input['amount'] ?? 0),
(string)($input['code'] ?? '')
);
echo json_encode($res);
break;
case 'movements':
if (!isset($_SESSION['user'])) {
throw new Exception("Su sesión ha expirado.");
}
$targetAccId = (int)$_SESSION['user']['account_id'];
echo json_encode(['status' => true, 'movements' => $transferModel->getAccountMovements($targetAccId)]);

break;
case 'admin_clients':
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
throw new Exception("Acceso denegado. Se requieren privilegios de administrador.");
}
echo json_encode(['status' => true, 'clients' => $userModel->getAllClients()]);
break;
case 'admin_client_detail':
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
throw new Exception("Acceso denegado. Se requieren privilegios de administrador.");
}
$clientUserId = (int)($input['user_id'] ?? 0);
$clientInfo = $accountModel->getAccountByUserId($clientUserId);
if (!$clientInfo) {
throw new Exception("Cliente no encontrado.");
}
$movements = $transferModel->getAccountMovements((int)$clientInfo['account_id']);
echo json_encode(['status' => true, 'client' => $clientInfo, 'movements' => $movements]);
break;
default:
echo json_encode(['status' => false, 'message' => 'Acción API no válida.']);
break;
}
} catch (Exception $e) {
echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
