<?php
require __DIR__ . '/db.php';
$pdo = getDb();
$shipmentId = (int)($_GET['shipment_id'] ?? 0);

$stmt = $pdo->prepare('SELECT s.*, c.name AS client_name, c.tax_id AS client_tax, c.address AS client_address, sup.name AS supplier_name FROM shipments s
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN suppliers sup ON s.supplier_id = sup.id
    WHERE s.id = ?');
$stmt->execute([$shipmentId]);
$shipment = $stmt->fetch();

if (!$shipment) {
    http_response_code(404);
    echo 'Embarque no encontrado';
    exit;
}

$invoice = $pdo->prepare('SELECT * FROM invoices WHERE shipment_id = ? ORDER BY created_at DESC LIMIT 1');
$invoice->execute([$shipmentId]);
$invoice = $invoice->fetch();

$revenueMxn = convertToMXN((float)$shipment['revenue_amount'], $shipment['revenue_currency'], (float)$shipment['fx_rate']);
$costMxn = convertToMXN((float)$shipment['cost_amount'], $shipment['cost_currency'], (float)$shipment['fx_rate']);
$margin = $revenueMxn - $costMxn;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura - <?= htmlspecialchars($shipment['reference']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { padding: 2rem; }
        .header { margin-bottom: 2rem; }
        .status { text-transform: capitalize; }
    </style>
</head>
<body>
<div class="container">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <h1>Factura / Orden de embarque</h1>
            <p class="text-muted">Plantilla lista para imprimir con datos del embarque.</p>
        </div>
        <div class="text-end">
            <p class="mb-0"><strong>Referencia:</strong> <?= htmlspecialchars($shipment['reference']) ?></p>
            <p class="mb-0"><strong>Estado factura:</strong> <span class="badge bg-info status"><?= htmlspecialchars($shipment['invoice_status']) ?></span></p>
            <p class="mb-0"><strong>Modo:</strong> <?= htmlspecialchars($shipment['mode']) ?></p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <h5>Cliente</h5>
            <p class="mb-0 fw-bold"><?= htmlspecialchars($shipment['client_name'] ?? 'N/A') ?></p>
            <p class="mb-0">RFC / EIN: <?= htmlspecialchars($shipment['client_tax'] ?? 'N/A') ?></p>
            <p class="mb-0">Dirección: <?= htmlspecialchars($shipment['client_address'] ?? 'N/A') ?></p>
        </div>
        <div class="col-md-6 text-end">
            <h5>Proveedor principal</h5>
            <p class="mb-0 fw-bold"><?= htmlspecialchars($shipment['supplier_name'] ?? 'N/A') ?></p>
            <p class="mb-0">Modalidad: <?= htmlspecialchars($shipment['mode']) ?></p>
            <p class="mb-0">Estado: <?= htmlspecialchars($shipment['status']) ?></p>
        </div>
    </div>

    <div class="mb-3">
        <h5>Trayecto</h5>
        <p class="mb-0">Origen: <?= htmlspecialchars($shipment['origin']) ?> | Destino: <?= htmlspecialchars($shipment['destination']) ?></p>
        <p class="mb-0">Salida: <?= htmlspecialchars($shipment['departure_date']) ?> | Llegada: <?= htmlspecialchars($shipment['arrival_date']) ?></p>
    </div>

    <div class="mb-3">
        <h5>Resumen financiero</h5>
        <table class="table table-bordered">
            <thead class="table-light"><tr><th>Concepto</th><th>Monto</th></tr></thead>
            <tbody>
                <tr><td>Ingreso</td><td><?= number_format((float)$shipment['revenue_amount'], 2) . ' ' . $shipment['revenue_currency'] ?></td></tr>
                <tr><td>Costo</td><td><?= number_format((float)$shipment['cost_amount'], 2) . ' ' . $shipment['cost_currency'] ?></td></tr>
                <tr><td>Utilidad estimada (MXN)</td><td class="fw-bold">$<?= number_format($margin, 2) ?></td></tr>
            </tbody>
        </table>
        <p class="text-muted">La utilidad convierte USD a MXN usando el tipo de cambio capturado (<?= number_format((float)$shipment['fx_rate'], 4) ?>).</p>
    </div>

    <div class="mb-3">
        <h5>Factura ligada</h5>
        <?php if ($invoice): ?>
            <table class="table table-striped">
                <thead><tr><th>Folio</th><th>Monto</th><th>Estado</th><th>Emisión</th><th>Vencimiento</th></tr></thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                        <td><?= number_format((float)$invoice['amount'], 2) . ' ' . $invoice['currency'] ?></td>
                        <td><span class="badge bg-primary status"><?= htmlspecialchars($invoice['status']) ?></span></td>
                        <td><?= htmlspecialchars($invoice['issue_date']) ?></td>
                        <td><?= htmlspecialchars($invoice['due_date']) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Aún no se captura una factura para este embarque. Vuelve a la pantalla principal y agrega una.</p>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-end">
        <button class="btn btn-outline-secondary" onclick="window.print()">Imprimir / Guardar en PDF</button>
        <a class="btn btn-link" href="index.php">Regresar</a>
    </div>
</div>
</body>
</html>
