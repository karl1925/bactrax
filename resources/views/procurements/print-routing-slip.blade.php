<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routing Slip - {{ $procurement->tracking_number }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 20px; color: #000; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 16px; font-weight: bold; }
        .subtitle { font-size: 11px; }
        .info-table, .routes-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td, .routes-table th, .routes-table td { border: 1px solid #000; padding: 6px; }
        .routes-table th { background-color: #f0f0f0; text-transform: uppercase; font-size: 10px; }
        .qr-section { text-align: center; margin-top: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 12px; font-weight: bold; cursor: pointer;">
            Print Routing Slip
        </button>
    </div>

    <div class="header">
        <div class="subtitle">REPUBLIC OF THE PHILIPPINES</div>
        <div class="title">DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY</div>
        <div class="subtitle">REGION 2 — BIDS AND AWARDS COMMITTEE</div>
        <br>
        <div style="font-size: 14px; font-weight: bold;">DOCUMENT PROCUREMENT ROUTING SLIP</div>
        <div>(Republic Act 12009 Compliance Registry)</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 25%;"><strong>TRACKING NUMBER:</strong></td>
            <td style="width: 25%;"><strong>{{ $procurement->tracking_number }}</strong></td>
            <td style="width: 25%;"><strong>DATE CREATED:</strong></td>
            <td style="width: 25%;">{{ $procurement->created_at->format('Y-m-d H:i') }}</td>
        </tr>
        <tr>
            <td><strong>PROJECT TITLE:</strong></td>
            <td colspan="3">{{ $procurement->title }}</td>
        </tr>
        <tr>
            <td><strong>ORIGIN OFFICE:</strong></td>
            <td>{{ $procurement->endUserOffice->name }} ({{ $procurement->endUserOffice->code }})</td>
            <td><strong>MODALITY:</strong></td>
            <td>{{ $procurement->mode->name }}</td>
        </tr>
        <tr>
            <td><strong>TOTAL ABC:</strong></td>
            <td>PHP {{ number_format($procurement->abc_amount, 2) }}</td>
            <td><strong>CURRENT STATUS:</strong></td>
            <td>{{ $procurement->status }}</td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">PHYSICAL MOVEMENT & ROUTING HISTORY:</div>
    <table class="routes-table">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>From Office</th>
                <th>To Office</th>
                <th>Action / Phase Stage</th>
                <th>Remarks</th>
                <th style="width: 15%;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse($procurement->routes as $route)
            <tr>
                <td>{{ $route->created_at->format('M d, Y H:i') }}</td>
                <td>{{ $route->fromOffice->code ?? 'N/A' }}</td>
                <td>{{ $route->toOffice->code ?? 'N/A' }}</td>
                <td>{{ $route->action_taken }}</td>
                <td>{{ $route->remarks ?? 'N/A' }}</td>
                <td></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No routing movements recorded yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Embedded QR Code Section -->
    <div class="qr-section">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($qrData) }}" alt="Tracking QR Code">
        <div style="font-size: 10px; margin-top: 4px;">Scan QR Code to view dynamic status on BACTrax Platform</div>
    </div>

</body>
</html>