<?php
require __DIR__ . '/db.php';

$pdo = getDb();

function handlePost(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_client') {
        $stmt = $pdo->prepare('INSERT INTO clients (name, nickname, address, tax_id, credit_days, account_status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['nickname'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['tax_id'] ?? ''),
            (int)($_POST['credit_days'] ?? 0),
            $_POST['account_status'] ?? 'facturado',
        ]);
    }

    if ($action === 'add_supplier') {
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, nickname, address, tax_id, credit_days, account_status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['nickname'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['tax_id'] ?? ''),
            (int)($_POST['credit_days'] ?? 0),
            $_POST['account_status'] ?? 'facturado',
        ]);
    }

    if ($action === 'add_shipment') {
        $stmt = $pdo->prepare('INSERT INTO shipments (client_id, supplier_id, reference, mode, description, origin, destination, departure_date, arrival_date, revenue_amount, revenue_currency, cost_amount, cost_currency, fx_rate, status, invoice_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $_POST['client_id'] ?: null,
            $_POST['supplier_id'] ?: null,
            trim($_POST['reference'] ?? ''),
            $_POST['mode'] ?? 'aereo',
            trim($_POST['description'] ?? ''),
            trim($_POST['origin'] ?? ''),
            trim($_POST['destination'] ?? ''),
            $_POST['departure_date'] ?: null,
            $_POST['arrival_date'] ?: null,
            (float)($_POST['revenue_amount'] ?? 0),
            $_POST['revenue_currency'] ?? 'MXN',
            (float)($_POST['cost_amount'] ?? 0),
            $_POST['cost_currency'] ?? 'MXN',
            (float)($_POST['fx_rate'] ?? 1),
            $_POST['status'] ?? 'borrador',
            $_POST['invoice_status'] ?? 'sin_facturar',
        ]);
    }

    if ($action === 'add_payment') {
        $stmt = $pdo->prepare('INSERT INTO payments (shipment_id, type, amount, currency, fx_rate, notes) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            $_POST['shipment_id'] ?: null,
            $_POST['type'] ?? 'cobro',
            (float)($_POST['amount'] ?? 0),
            $_POST['currency'] ?? 'MXN',
            (float)($_POST['fx_rate'] ?? 1),
            trim($_POST['notes'] ?? ''),
        ]);
    }

    if ($action === 'add_invoice') {
        $stmt = $pdo->prepare('INSERT INTO invoices (shipment_id, invoice_number, amount, currency, status, issue_date, due_date) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $_POST['shipment_id'] ?: null,
            trim($_POST['invoice_number'] ?? ''),
            (float)($_POST['amount'] ?? 0),
            $_POST['currency'] ?? 'MXN',
            $_POST['status'] ?? 'borrador',
            $_POST['issue_date'] ?: null,
            $_POST['due_date'] ?: null,
        ]);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

handlePost($pdo);

$clients = $pdo->query('SELECT * FROM clients ORDER BY created_at DESC')->fetchAll();
$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY created_at DESC')->fetchAll();
$shipments = $pdo->query('SELECT * FROM shipments ORDER BY created_at DESC')->fetchAll();
$payments = $pdo->query('SELECT * FROM payments ORDER BY created_at DESC')->fetchAll();
$invoices = $pdo->query('SELECT * FROM invoices ORDER BY created_at DESC')->fetchAll();

function totals(array $shipments, array $payments): array
{
    $revenueMxn = 0;
    $costMxn = 0;
    foreach ($shipments as $shipment) {
        $revenueMxn += convertToMXN((float)$shipment['revenue_amount'], $shipment['revenue_currency'], (float)$shipment['fx_rate']);
        $costMxn += convertToMXN((float)$shipment['cost_amount'], $shipment['cost_currency'], (float)$shipment['fx_rate']);
    }

    $cobros = 0;
    $pagos = 0;
    foreach ($payments as $payment) {
        $amountMxn = convertToMXN((float)$payment['amount'], $payment['currency'], (float)$payment['fx_rate']);
        if ($payment['type'] === 'cobro') {
            $cobros += $amountMxn;
        } else {
            $pagos += $amountMxn;
        }
    }

    $bank = $cobros - $pagos;
    $porCobrar = $revenueMxn - $cobros;
    $porPagar = $costMxn - $pagos;
    $cashFlow = $bank + $porCobrar - $porPagar;

    return [
        'revenue_mxn' => $revenueMxn,
        'cost_mxn' => $costMxn,
        'bank' => $bank,
        'por_cobrar' => $porCobrar,
        'por_pagar' => $porPagar,
        'cash_flow' => $cashFlow,
    ];
}

$totals = totals($shipments, $payments);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración logística</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { padding: 2rem; }
        .card { margin-bottom: 1rem; }
        .section-title { margin-top: 2rem; }
        .badge-status { text-transform: capitalize; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Suite logística: clientes, proveedores y embarques</h1>
    <p class="text-muted">Captura clientes, proveedores, embarques internacionales (aéreo, marítimo, terrestre, HC y servicios locales), facturas y pagos para obtener rentabilidad y flujo de efectivo en tiempo real.</p>

    <h2 class="section-title">Clientes</h2>
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Registrar cliente</h5>
                    <form method="post">
                        <input type="hidden" name="action" value="add_client">
                        <div class="mb-2"><label class="form-label">Nombre</label><input required name="name" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Nickname</label><input name="nickname" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Dirección</label><textarea name="address" class="form-control"></textarea></div>
                        <div class="mb-2"><label class="form-label">RFC / EIN</label><input name="tax_id" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Días de crédito</label><input type="number" name="credit_days" class="form-control" min="0" value="0"></div>
                        <div class="mb-2"><label class="form-label">Estado de cuenta</label>
                            <select name="account_status" class="form-select">
                                <option value="facturado">Facturado</option>
                                <option value="pagado">Pagado</option>
                                <option value="mixto">Mixto</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Nombre</th><th>Nickname</th><th>RFC/EIN</th><th>Días crédito</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?= htmlspecialchars($client['name']) ?></td>
                            <td><?= htmlspecialchars($client['nickname']) ?></td>
                            <td><?= htmlspecialchars($client['tax_id']) ?></td>
                            <td><?= (int)$client['credit_days'] ?></td>
                            <td><span class="badge bg-info badge-status"><?= htmlspecialchars($client['account_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h2 class="section-title">Proveedores</h2>
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Registrar proveedor</h5>
                    <form method="post">
                        <input type="hidden" name="action" value="add_supplier">
                        <div class="mb-2"><label class="form-label">Nombre</label><input required name="name" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Nickname</label><input name="nickname" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Dirección</label><textarea name="address" class="form-control"></textarea></div>
                        <div class="mb-2"><label class="form-label">RFC / EIN</label><input name="tax_id" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Días de crédito</label><input type="number" name="credit_days" class="form-control" min="0" value="0"></div>
                        <div class="mb-2"><label class="form-label">Estado de cuenta</label>
                            <select name="account_status" class="form-select">
                                <option value="facturado">Facturado</option>
                                <option value="pagado">Pagado</option>
                                <option value="mixto">Mixto</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Nombre</th><th>Nickname</th><th>RFC/EIN</th><th>Días crédito</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?= htmlspecialchars($supplier['name']) ?></td>
                            <td><?= htmlspecialchars($supplier['nickname']) ?></td>
                            <td><?= htmlspecialchars($supplier['tax_id']) ?></td>
                            <td><?= (int)$supplier['credit_days'] ?></td>
                            <td><span class="badge bg-secondary badge-status"><?= htmlspecialchars($supplier['account_status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h2 class="section-title">Embarques y rentabilidad</h2>
    <div class="card">
        <div class="card-body">
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_shipment">
                <div class="col-md-3"><label class="form-label">Referencia</label><input required name="reference" class="form-control" placeholder="REF-001"></div>
                <div class="col-md-3"><label class="form-label">Modo</label>
                    <select name="mode" class="form-select">
                        <option value="aereo">Aéreo</option>
                        <option value="maritimo">Marítimo</option>
                        <option value="terrestre">Terrestre</option>
                        <option value="hc">HC</option>
                        <option value="servicio_local">Servicios locales</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Cliente</label>
                    <select name="client_id" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Proveedor</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Origen</label><input name="origin" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Destino</label><input name="destination" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Salida</label><input type="date" name="departure_date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Llegada</label><input type="date" name="arrival_date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Ingreso</label><input type="number" step="0.01" name="revenue_amount" class="form-control" value="0"></div>
                <div class="col-md-2"><label class="form-label">Moneda ingreso</label>
                    <select name="revenue_currency" class="form-select"><option>MXN</option><option>USD</option></select>
                </div>
                <div class="col-md-3"><label class="form-label">Costo</label><input type="number" step="0.01" name="cost_amount" class="form-control" value="0"></div>
                <div class="col-md-2"><label class="form-label">Moneda costo</label>
                    <select name="cost_currency" class="form-select"><option>MXN</option><option>USD</option></select>
                </div>
                <div class="col-md-2"><label class="form-label">Tipo de cambio</label><input type="number" step="0.0001" name="fx_rate" class="form-control" value="1"></div>
                <div class="col-md-3"><label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="borrador">Borrador</option>
                        <option value="activo">Activo</option>
                        <option value="en_transito">En tránsito</option>
                        <option value="entregado">Entregado</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Estatus de factura</label>
                    <select name="invoice_status" class="form-select">
                        <option value="sin_facturar">Sin facturar</option>
                        <option value="facturado">Facturado</option>
                        <option value="pagado">Pagado</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Notas / descripción</label><textarea name="description" class="form-control" placeholder="Detalles del embarque y servicios locales"></textarea></div>
                <div class="col-12"><button class="btn btn-success">Registrar embarque</button></div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
            <tr>
                <th>Referencia</th>
                <th>Modo</th>
                <th>Cliente</th>
                <th>Proveedor</th>
                <th>Ingreso</th>
                <th>Costo</th>
                <th>Margen (MXN)</th>
                <th>Estado</th>
                <th>Factura</th>
                <th>Plantilla</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $shipment):
                $revenue = convertToMXN((float)$shipment['revenue_amount'], $shipment['revenue_currency'], (float)$shipment['fx_rate']);
                $cost = convertToMXN((float)$shipment['cost_amount'], $shipment['cost_currency'], (float)$shipment['fx_rate']);
                $margin = $revenue - $cost;
                $clientName = array_values(array_filter($clients, fn($c) => $c['id'] === $shipment['client_id']))[0]['name'] ?? 'N/A';
                $supplierName = array_values(array_filter($suppliers, fn($s) => $s['id'] === $shipment['supplier_id']))[0]['name'] ?? 'N/A';
                ?>
                <tr>
                    <td><?= htmlspecialchars($shipment['reference']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($shipment['mode']) ?></td>
                    <td><?= htmlspecialchars($clientName) ?></td>
                    <td><?= htmlspecialchars($supplierName) ?></td>
                    <td><?= number_format((float)$shipment['revenue_amount'], 2) . ' ' . $shipment['revenue_currency'] ?></td>
                    <td><?= number_format((float)$shipment['cost_amount'], 2) . ' ' . $shipment['cost_currency'] ?></td>
                    <td class="fw-bold">$<?= number_format($margin, 2) ?></td>
                    <td><span class="badge bg-warning badge-status"><?= htmlspecialchars($shipment['status']) ?></span></td>
                    <td><span class="badge bg-primary badge-status"><?= htmlspecialchars($shipment['invoice_status']) ?></span></td>
                    <td><a class="btn btn-outline-secondary btn-sm" href="invoice.php?shipment_id=<?= $shipment['id'] ?>">Ver factura</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-title">Flujo y pagos</h2>
    <div class="card mb-3">
        <div class="card-body">
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_payment">
                <div class="col-md-3"><label class="form-label">Embarque</label>
                    <select name="shipment_id" class="form-select">
                        <option value="">No ligado</option>
                        <?php foreach ($shipments as $shipment): ?>
                            <option value="<?= $shipment['id'] ?>"><?= htmlspecialchars($shipment['reference']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="cobro">Cobro (cliente)</option>
                        <option value="pago">Pago (proveedor)</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Monto</label><input required type="number" step="0.01" name="amount" class="form-control"></div>
                <div class="col-md-1"><label class="form-label">Moneda</label>
                    <select name="currency" class="form-select"><option>MXN</option><option>USD</option></select>
                </div>
                <div class="col-md-2"><label class="form-label">Tipo cambio</label><input type="number" step="0.0001" name="fx_rate" class="form-control" value="1"></div>
                <div class="col-md-2"><label class="form-label">Notas</label><input name="notes" class="form-control" placeholder="Banco, método..."></div>
                <div class="col-12"><button class="btn btn-outline-success">Registrar flujo</button></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <h5>Movimientos</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Fecha</th><th>Embarque</th><th>Tipo</th><th>Monto</th><th>Notas</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $payment):
                        $shipmentRef = array_values(array_filter($shipments, fn($s) => $s['id'] === $payment['shipment_id']))[0]['reference'] ?? 'N/A';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['created_at']) ?></td>
                            <td><?= htmlspecialchars($shipmentRef) ?></td>
                            <td><?= $payment['type'] === 'cobro' ? 'Cobro' : 'Pago' ?></td>
                            <td><?= number_format((float)$payment['amount'], 2) . ' ' . $payment['currency'] ?></td>
                            <td><?= htmlspecialchars($payment['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <h5>Balance general</h5>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">Bancos<span class="fw-bold">$<?= number_format($totals['bank'], 2) ?> MXN</span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Cuentas por cobrar<span class="fw-bold">$<?= number_format($totals['por_cobrar'], 2) ?> MXN</span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Cuentas por pagar<span class="fw-bold">$<?= number_format($totals['por_pagar'], 2) ?> MXN</span></li>
                <li class="list-group-item d-flex justify-content-between align-items-center">Flujo estimado<span class="fw-bold text-success">$<?= number_format($totals['cash_flow'], 2) ?> MXN</span></li>
            </ul>
            <p class="mt-2 text-muted">El flujo considera ingresos y costos en MXN y USD, convirtiendo con el tipo de cambio capturado en cada registro.</p>
        </div>
    </div>

    <h2 class="section-title">Facturación</h2>
    <div class="card">
        <div class="card-body">
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_invoice">
                <div class="col-md-3"><label class="form-label">Embarque</label>
                    <select name="shipment_id" class="form-select">
                        <?php foreach ($shipments as $shipment): ?>
                            <option value="<?= $shipment['id'] ?>"><?= htmlspecialchars($shipment['reference']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Folio de factura</label><input required name="invoice_number" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Monto</label><input required type="number" step="0.01" name="amount" class="form-control"></div>
                <div class="col-md-1"><label class="form-label">Moneda</label>
                    <select name="currency" class="form-select"><option>MXN</option><option>USD</option></select>
                </div>
                <div class="col-md-3"><label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="borrador">Borrador</option>
                        <option value="emitida">Emitida</option>
                        <option value="pagada">Pagada</option>
                        <option value="vencida">Vencida</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Fecha emisión</label><input type="date" name="issue_date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Fecha vencimiento</label><input type="date" name="due_date" class="form-control"></div>
                <div class="col-12"><button class="btn btn-outline-primary">Agregar factura</button></div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Folio</th><th>Embarque</th><th>Monto</th><th>Estado</th><th>Vence</th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice):
                $shipmentRef = array_values(array_filter($shipments, fn($s) => $s['id'] === $invoice['shipment_id']))[0]['reference'] ?? 'N/A';
                ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                    <td><?= htmlspecialchars($shipmentRef) ?></td>
                    <td><?= number_format((float)$invoice['amount'], 2) . ' ' . $invoice['currency'] ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($invoice['status']) ?></span></td>
                    <td><?= htmlspecialchars($invoice['due_date'] ?: '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="mt-4 text-muted">Incluye flujo lógico: captura cliente/proveedor, registra embarque, agrega factura y pagos, y consulta el balance general con pérdidas-ganancias por embarque.</footer>
</div>
</body>
</html>
