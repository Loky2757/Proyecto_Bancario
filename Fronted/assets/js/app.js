let currentUser = null;
/**
* Función genérica para enviar peticiones AJAX en formato JSON al controlador PHP.
*/
async function apiRequest(action, data = {}) {
try {
const response = await fetch(`controllers/ApiController.php?
action=${encodeURIComponent(action)}`, {

method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify(data)
});
return await response.json();
} catch (error) {
return { status: false, message: 'Error de comunicación con el servidor PHP.' };
}
}
function showAlert(msg) {
const box = document.getElementById('global-alert');
box.textContent = msg;
box.style.display = 'block';
setTimeout(() => { box.style.display = 'none'; }, 5500);
}
function showView(viewId) {
document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
document.getElementById(viewId).classList.add('active');
}
function toggleAdminField() {
const checked = document.getElementById('reg-is-admin').checked;
const codeGroup = document.getElementById('admin-code-group');
if (checked) {
codeGroup.classList.remove('hidden');
} else {
codeGroup.classList.add('hidden');
document.getElementById('reg-admin-code').value = '';
}
}
async function registerUser() {
const payload = {
username: document.getElementById('reg-username').value,
password: document.getElementById('reg-password').value,
phone: document.getElementById('reg-phone').value,
is_admin: document.getElementById('reg-is-admin').checked,
admin_code: document.getElementById('reg-admin-code').value
};
const res = await apiRequest('register', payload);
showAlert(res.message);
if (res.status) {
document.getElementById('reg-username').value = '';
document.getElementById('reg-password').value = '';
document.getElementById('reg-phone').value = '';
document.getElementById('reg-is-admin').checked = false;
toggleAdminField();
showView('view-login');
}
}
async function login() {
const res = await apiRequest('login', {
username: document.getElementById('login-username').value,
password: document.getElementById('login-password').value
});
if (!res.status) { showAlert(res.message); return; }
currentUser = res.user;
document.getElementById('login-username').value = '';
document.getElementById('login-password').value = '';
document.getElementById('btn-logout').classList.remove('hidden');
if (currentUser.role === 'admin') { loadAdminClients(); } else { renderUserDashboard(); }
}
async function logout() {
await apiRequest('logout');
currentUser = null;
document.getElementById('btn-logout').classList.add('hidden');
showView('view-login');
}
function renderUserDashboard() {
document.getElementById('usr-name').textContent = currentUser.username;
document.getElementById('usr-account').textContent = currentUser.account_number;
document.getElementById('usr-balance').textContent = parseFloat(currentUser.balance).toFixed(2);
showView('view-user');
}
async function refreshAndGoHome() {
const res = await apiRequest('session_info');
if (res.status) { currentUser = res.user; renderUserDashboard(); } else { showView('view-login');
}
}
async function searchRecipients() {
const q = document.getElementById('tr-target').value.trim();
const box = document.getElementById('tr-suggestions');
if (q.length < 1) { box.classList.add('hidden'); box.innerHTML = ''; return; }
const res = await apiRequest('search_clients', { query: q });
box.innerHTML = '';
if (res.status && res.results.length > 0) {
box.classList.remove('hidden');
res.results.forEach(item => {
const div = document.createElement('div');
div.className = 'autocomplete-item';
div.textContent = `${item.username} — Cuenta: ${item.account_number}`;
div.onclick = () => {
document.getElementById('tr-target').value = item.account_number;
box.classList.add('hidden');
};
box.appendChild(div);
});
} else { box.classList.add('hidden'); }
}
async function submitTransfer() {
const target = document.getElementById('tr-target').value;
const amount = parseFloat(document.getElementById('tr-amount').value);
const res = await apiRequest('transfer', { target, amount });
showAlert(res.message);
if (res.status) {
document.getElementById('tr-target').value = '';
document.getElementById('tr-amount').value = '';
document.getElementById('tr-suggestions').classList.add('hidden');
refreshAndGoHome();
}
}
async function submitDeposit() {
const amount = parseFloat(document.getElementById('dep-amount').value);
const code = document.getElementById('dep-code').value;
const res = await apiRequest('deposit', { amount, code });
showAlert(res.message);
if (res.status) {
document.getElementById('dep-amount').value = '';
document.getElementById('dep-code').value = '';
refreshAndGoHome();
}
}
async function openUserMovements() {
const res = await apiRequest('movements');
const tbody = document.getElementById('movements-tbody');
tbody.innerHTML = '';
if (!res.status || res.movements.length === 0) {
tbody.innerHTML = '<tr><td colspan="3">No existen movimientos registrados en esta cuenta.</td></tr>';
} else {
res.movements.forEach(m => {
let sign = (m.tipo_movimiento === 'ENVIADA') ? '-' : '+';
let destLabel = (m.tipo_movimiento === 'ENVIADA')
? `${m.destinatario_usuario} (${m.destinatario_cuenta})`
: (m.tipo_movimiento === 'RECIBIDA'
? `${m.destinatario_usuario} (Recibido de ${m.origen_usuario})`
: `${m.destinatario_usuario} (Depósito código ${m.origen_cuenta})`);
tbody.innerHTML += `<tr><td>${m.fecha}</td>

<td>${sign}$${parseFloat(m.cantidad).toFixed(2)} MXN</td><td>${destLabel}</td></tr>`;
});
}
showView('view-movements');
}
async function loadAdminClients() {
const res = await apiRequest('admin_clients');
const tbody = document.getElementById('admin-clients-tbody');
tbody.innerHTML = '';
if (!res.status || res.clients.length === 0) {
tbody.innerHTML = '<tr><td colspan="5">No hay clientes registrados todavía.</td></tr>';
} else {
res.clients.forEach(c => {
tbody.innerHTML += `<tr><td>${c.username}</td><td>${c.phone}</td><td>${c.account_number}

</td>

<td>$${parseFloat(c.balance).toFixed(2)} MXN</td>
<td><button onclick="viewClientDetail(${c.id})">Detalles de cliente</button></td>

</tr>`;
});
}
showView('view-admin');
}
async function viewClientDetail(userId) {
const res = await apiRequest('admin_client_detail', { user_id: userId });
if (!res.status) { showAlert(res.message); return; }
document.getElementById('det-username').textContent = res.client.username;
document.getElementById('det-account').textContent = res.client.account_number;
document.getElementById('det-balance').textContent = parseFloat(res.client.balance).toFixed(2);
const tbody = document.getElementById('det-movements-tbody');
tbody.innerHTML = '';
if (res.movements.length === 0) {
tbody.innerHTML = '<tr><td colspan="3">El cliente no registra movimientos.</td></tr>';
} else {
res.movements.forEach(m => {
const sign = (m.tipo_movimiento === 'ENVIADA') ? '-' : '+';
tbody.innerHTML += `<tr><td>${m.fecha}</td>
<td>${sign}$${parseFloat(m.cantidad).toFixed(2)} MXN</td>

<td>${m.destinatario_usuario} (${m.destinatario_cuenta}) [${m.tipo_movimiento}]</td>

</tr>`;
});
}
showView('view-client-detail');
}
window.addEventListener('DOMContentLoaded', async () => {
const res = await apiRequest('session_info');
if (res.status && res.user) {
currentUser = res.user;
document.getElementById('btn-logout').classList.remove('hidden');
if (currentUser.role === 'admin') { loadAdminClients(); } else { renderUserDashboard(); }
}
});