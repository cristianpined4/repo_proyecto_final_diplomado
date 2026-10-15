<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta #{{ $venta->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0;
            padding: 5px;
            color: #000;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
        .header {
            margin-bottom: 15px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
        }
        .table-items th, .table-items td {
            font-size: 11px;
            padding: 3px 0;
        }
        .totals {
            margin-top: 10px;
            width: 100%;
        }
        .totals td {
            padding: 2px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 11px;
        }
        @media print {
            body {
                width: 80mm;
            }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="header text-center">
        @if(!empty($venta->sucursal) && !empty($venta->sucursal->logo))
            <img src="http://localhost:8000/storage/{{ $venta->sucursal->logo }}" alt="Logo Sucursal" style="max-height: 50px; max-width: 150px; display: block; margin: 0 auto 5px;"><br>
        @elseif(!empty($settings['empresa_logo']))
            <img src="http://localhost:8000/storage/{{ $settings['empresa_logo'] }}" alt="Logo Empresa" style="max-height: 50px; max-width: 150px; display: block; margin: 0 auto 5px;"><br>
        @endif

        <span class="bold" style="font-size: 13px;">{{ !empty($venta->sucursal->nombre) ? $venta->sucursal->nombre : ($settings['empresa_nombre'] ?? 'Mi Empresa') }}</span><br>
        
        @if(!empty($venta->sucursal) && (!empty($venta->sucursal->direccion) || !empty($venta->sucursal->telefono)))
            @if(!empty($venta->sucursal->telefono))
                <span>Teléfono: {{ $venta->sucursal->telefono }}</span><br>
            @elseif(!empty($settings['empresa_telefono']))
                <span>Teléfono: {{ $settings['empresa_telefono'] }}</span><br>
            @endif
            @if(!empty($venta->sucursal->direccion))
                <span>Dirección: {{ $venta->sucursal->direccion }}</span><br>
            @elseif(!empty($settings['empresa_direccion']))
                <span>Dirección: {{ $settings['empresa_direccion'] }}</span><br>
            @endif
            @if(!empty($venta->sucursal->ciudad))
                <span>{{ $venta->sucursal->ciudad }}, {{ $venta->sucursal->estado }}, {{ $venta->sucursal->pais }}</span><br>
            @endif
        @else
            @if(!empty($settings['empresa_telefono']))
                <span>Teléfono: {{ $settings['empresa_telefono'] }}</span><br>
            @endif
            @if(!empty($settings['empresa_direccion']))
                <span>Dirección: {{ $settings['empresa_direccion'] }}</span><br>
            @endif
        @endif
        <span>RFC/NIT: {{ $settings['empresa_nit'] ?? '0000-000000-000-0' }}</span>
    </div>

    <div class="divider"></div>

    <div>
        <span>TICKET: {{ $venta->serie }}-{{ $venta->correlativo }}</span><br>
        <span>FECHA: {{ $venta->created_at->format('d/m/Y H:i') }}</span><br>
        <span>CAJERO: {{ $venta->user->name ?? 'N/A' }}</span><br>
        <span>CLIENTE: {{ $venta->cliente->nombre ?? 'Consumidor Final' }}</span><br>
        @if(!empty($venta->cliente->dui))
            <span>DUI/ID: {{ $venta->cliente->dui }}</span><br>
        @endif
    </div>

    <div class="divider"></div>

    <table class="table-items">
        <thead>
            <tr>
                <th align="left" width="50%">Descripción</th>
                <th align="center" width="10%">Cant</th>
                <th align="right" width="20%">P.Unit</th>
                <th align="right" width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detallesVenta as $detalle)
                <tr>
                    <td align="left">{{ $detalle->producto->nombre }}</td>
                    <td align="center">{{ $detalle->cantidad }}</td>
                    <td align="right">${{ number_format($detalle->precio_venta, 2) }}</td>
                    <td align="right">${{ number_format($detalle->cantidad * $detalle->precio_venta, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals">
        <tr>
            <td width="60%">SUBTOTAL:</td>
            <td align="right" width="40%">${{ number_format($venta->subtotal, 2) }}</td>
        </tr>
        @if($venta->monto_iva > 0)
        <tr>
            <td>IVA ({{ $venta->porcentaje_iva_aplicado }}%):</td>
            <td align="right">${{ number_format($venta->monto_iva, 2) }}</td>
        </tr>
        @endif
        <tr class="bold">
            <td>TOTAL A PAGAR:</td>
            <td align="right">${{ number_format($venta->total_venta, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2"><div class="divider"></div></td>
        </tr>
        <tr>
            <td>FORMA DE PAGO:</td>
            <td align="right">{{ strtoupper(str_replace('_', ' ', $venta->metodo_pago ?? 'efectivo')) }}</td>
        </tr>
        @if(($venta->metodo_pago ?? 'efectivo') === 'efectivo' && !is_null($venta->monto_recibido))
        <tr>
            <td>EFECTIVO RECIBIDO:</td>
            <td align="right">${{ number_format($venta->monto_recibido, 2) }}</td>
        </tr>
        <tr class="bold">
            <td>CAMBIO:</td>
            <td align="right">${{ number_format($venta->vuelto ?? 0, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <div class="footer text-center">
        <span class="bold">¡GRACIAS POR SU COMPRA!</span><br>
        <span>No se aceptan devoluciones sin su ticket</span><br>
        <span>Desarrollado por InventaryApp</span>
    </div>

</body>
</html>
