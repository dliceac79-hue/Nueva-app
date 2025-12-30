# Nueva-app logística

Aplicación ligera en PHP + MySQL para administrar clientes, proveedores, embarques internacionales y flujo de efectivo en una empresa de logística.

## Requisitos
- PHP 8.x con extensión PDO para MySQL
- Servidor MySQL accesible (define credenciales en variables de entorno)

Variables de entorno:
- `DB_HOST` (por defecto `localhost`)
- `DB_PORT` (por defecto `3306`)
- `DB_NAME` (por defecto `logistica`)
- `DB_USER` (por defecto `root`)
- `DB_PASS` (por defecto vacío)

Al iniciar la aplicación se crean automáticamente las tablas necesarias.

## Puesta en marcha
1. Configura las variables de entorno según tu servidor MySQL.
2. Inicia un servidor PHP local:
   ```bash
   php -S localhost:8000
   ```
3. Abre `http://localhost:8000/index.php`.

## Funcionalidades
- **Clientes y proveedores:** captura de nombre, nickname, dirección, RFC/EIN, días de crédito y estado de cuenta.
- **Embarques:** soporta aéreo, marítimo, terrestre, HC y servicios locales. Calcula margen (pérdida/ganancia) por embarque considerando ingresos y costos en MXN/USD.
- **Facturación:** crea facturas ligadas a embarques con estados (borrador, emitida, pagada, vencida) y genera una plantilla imprimible.
- **Flujo de efectivo y balance general:** registra cobros y pagos con tipo de cambio, muestra bancos, cuentas por cobrar/pagar y flujo estimado.

## Plantilla de factura
Usa `invoice.php?shipment_id=ID` para imprimir o guardar como PDF la orden de embarque con los datos capturados.
