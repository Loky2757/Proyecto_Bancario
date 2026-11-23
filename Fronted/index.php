<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema Bancario - Consola de Asociados</title>
<link rel="stylesheet" href="assets/css/console.css">
</head>
<body>
<div class="console-wrapper">
<div class="console-header">
<span>SISTEMA BANCARIO CORPORATIVO // TERMINAL DE ASOCIADOS</span>
<button id="btn-logout" class="btn-secondary hidden" onclick="logout()">[ CERRAR SESIÓN ]
</button>
</div>
<div style="padding: 14px 20px 0 20px;">
<div id="global-alert" class="alert-box"></div>
</div>
<!-- PANTALLA 1: LOGIN -->
<section id="view-login" class="view-section active">
<h2>1. Inicio de Sesión</h2>
<div class="form-group">
<label for="login-username">Usuario:</label>
<input type="text" id="login-username" placeholder="Ingrese su nombre de usuario">
</div>
<div class="form-group">
<label for="login-password">Contraseña:</label>
<input type="password" id="login-password" placeholder="Ingrese su contraseña">
</div>
<button onclick="login()">INICIAR SESIÓN</button>
<button class="btn-secondary" onclick="showView('view-register')">CREAR CUENTA NUEVA</button>
</section>
<!-- PANTALLA 2: VISTA DE REGISTRO -->
<section id="view-register" class="view-section">
<h2>2. Registro de Usuario</h2>

<div class="form-group"><label for="reg-username">Usuario:</label><input type="text" id="reg-
username"></div>

<div class="form-group"><label for="reg-password">Contraseña:</label><input type="password"
id="reg-password"></div>
<div class="form-group"><label for="reg-phone">Número telefónico:</label><input type="text"
id="reg-phone"></div>
<div class="form-group">
<label><input type="checkbox" id="reg-is-admin" onchange="toggleAdminField()"> Registrar

como Administrador</label>
</div>
<div id="admin-code-group" class="form-group hidden">
<label for="reg-admin-code">Código de verificación (Administrador):</label>
<input type="text" id="reg-admin-code" placeholder="Ej: ADM-2026-MASTER">
</div>
<button onclick="registerUser()">REGISTRARSE</button>
<button class="btn-secondary" onclick="showView('view-login')">VOLVER A LOGIN</button>
</section>
<!-- PANTALLA 3: VISTA DE USUARIO -->
<section id="view-user" class="view-section">
<h2>3. Consola de Usuario Asociado</h2>
<div class="summary-box">
<div><strong>Titular:</strong> <span id="usr-name">-</span></div>
<div><strong>Número de cuenta:</strong> <span id="usr-account">-</span></div>
<div><strong>Saldo actual:</strong> $<span id="usr-balance">0.00</span> MXN</div>
</div>
<button onclick="showView('view-transfer')">REALIZAR TRANSFERENCIAS</button>
<button class="btn-secondary" onclick="openUserMovements()">CONSULTAR MOVIMIENTOS</button>
<button class="btn-secondary" onclick="showView('view-deposit')">DEPOSITAR DINERO</button>
</section>
<!-- PANTALLA 4: VISTA DE ADMINISTRADOR -->
<section id="view-admin" class="view-section">
<h2>4. Consola de Administrador - Ver Clientes</h2>
<table>
<thead>
<tr><th>Usuario</th><th>Teléfono</th><th>Número de Cuenta</th><th>Saldo</th>

<th>Acciones</th></tr>
</thead>
<tbody id="admin-clients-tbody"></tbody>
</table>
</section>
<!-- PANTALLA 5: VISTA DE MOVIMIENTOS -->
<section id="view-movements" class="view-section">
<h2>5. Historial de Movimientos</h2>
<table>
<thead>
<tr><th>Fecha del movimiento</th><th>Cantidad transferida</th><th>Destinatario</th>

</tr>

</thead>
<tbody id="movements-tbody"></tbody>
</table>
<br>
<button class="btn-secondary" onclick="refreshAndGoHome()">VOLVER AL PANEL</button>
</section>
<!-- PANTALLA 6: VISTA DE TRANSFERENCIAS -->
<section id="view-transfer" class="view-section">
<h2>6. Realizar Transferencia</h2>
<div class="form-group">
<label for="tr-target">Cuenta o cliente a buscar:</label>
<input type="text" id="tr-target" onkeyup="searchRecipients()" autocomplete="off"

placeholder="Usuario o cuenta...">

<div id="tr-suggestions" class="autocomplete-list hidden"></div>
</div>
<div class="form-group">
<label for="tr-amount">Cantidad a depositar / transferir ($ MXN):</label>
<input type="number" id="tr-amount" step="0.01" min="0.01">
</div>
<button onclick="submitTransfer()">TRANSFERIR</button>
<button class="btn-secondary" onclick="refreshAndGoHome()">CANCELAR / VOLVER</button>
</section>
<!-- PANTALLA 7: VISTA DE DEPÓSITO -->
<section id="view-deposit" class="view-section">
<h2>7. Depositar Dinero a la Cuenta</h2>
<div class="form-group"><label for="dep-amount">Cantidad a ingresar a la cuenta ($ MXN):
</label><input type="number" id="dep-amount" step="0.01" min="0.01"></div>
<div class="form-group"><label for="dep-code">Código de validación:</label><input type="text"
id="dep-code" placeholder="Ej: DEP-1000-MXN"></div>
<button onclick="submitDeposit()">DEPOSITAR DINERO</button>
<button class="btn-secondary" onclick="refreshAndGoHome()">VOLVER AL PANEL</button>
</section>
<!-- PANTALLA 8: VISTA DE DETALLE DE CLIENTE (ADMIN) -->
<section id="view-client-detail" class="view-section">
<h2>8. Detalle de Cliente en Consulta</h2>
<div class="summary-box">
<div><strong>Usuario:</strong> <span id="det-username">-</span></div>
<div><strong>Número de cuenta:</strong> <span id="det-account">-</span></div>
<div><strong>Saldo:</strong> $<span id="det-balance">0.00</span> MXN</div>
</div>
<h3>Movimientos del cliente en consulta:</h3>
<table>
<thead><tr><th>Fecha del movimiento</th><th>Cantidad transferida</th><th>Destinatario</th>

</tr></thead>

<tbody id="det-movements-tbody"></tbody>
</table>
<br>
<button class="btn-secondary" onclick="loadAdminClients()">VOLVER A CLIENTES</button>
</section>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>