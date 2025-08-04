<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de tu orden</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .order-info {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-created { background-color: #dbeafe; color: #1e40af; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .status-delivered { background-color: #ecfdf5; color: #047857; }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table th {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            font-size: 18px;
            background-color: #f3f4f6;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>Actualización de tu orden</h2>
    </div>
    
    <div class="content">
        <p>Hola {{ $order->user->first_name }},</p>
        
        <p>Te escribimos para informarte que el estado de tu orden ha cambiado:</p>
        
        <div class="order-info">
            <h3>Orden #{{ $order->order_number }}</h3>
            <p><strong>Estado anterior:</strong> <span class="status-badge status-{{ $previousStatus }}">{{ $previousStatusLabel }}</span></p>
            <p><strong>Estado actual:</strong> <span class="status-badge status-{{ $newStatus }}">{{ $statusLabel }}</span></p>
            <p><strong>Fecha de la orden:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            @if($order->paid_at)
                <p><strong>Fecha de pago:</strong> {{ $order->paid_at->format('d/m/Y H:i') }}</p>
            @endif
            @if($order->delivered_at)
                <p><strong>Fecha de entrega:</strong> {{ $order->delivered_at->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        
        <h3>Detalles de la orden:</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_title }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total de la orden:</td>
                    <td>${{ number_format($order->total, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        @if($newStatus === 'paid')
            <p>🎉 <strong>¡Gracias por tu pago!</strong> Tu orden está siendo procesada y pronto recibirás información sobre el envío.</p>
        @elseif($newStatus === 'delivered')
            <p>📦 <strong>¡Tu orden ha sido entregada!</strong> Esperamos que disfrutes tu compra. Si tienes algún problema, no dudes en contactarnos.</p>
        @elseif($newStatus === 'cancelled')
            <p>❌ <strong>Tu orden ha sido cancelada.</strong> Si tienes alguna pregunta sobre la cancelación, por favor contacta nuestro servicio al cliente.</p>
        @endif
        
        <p>Si tienes alguna pregunta sobre tu orden, no dudes en contactarnos.</p>
        
        <p>¡Gracias por elegir {{ config('app.name') }}!</p>
        <p>El equipo de {{ config('app.name') }}</p>
    </div>
    
    <div class="footer">
        <p>Este es un email automático, por favor no respondas a este mensaje.</p>
        <p>Para consultas sobre tu orden, visita nuestro centro de ayuda o contacta nuestro soporte.</p>
    </div>
</body>
</html>

