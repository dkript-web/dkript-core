<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Reporte Corporativo' }}</title>
    <style>
        @page {
            margin: 18mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.4;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Encabezado Corporativo */
        .report-header {
            width: 100%;
            border-bottom: 2px solid #0062f5;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .report-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-container {
            width: 180px;
        }

        .logo-img {
            max-height: 48px;
            max-width: 170px;
        }

        .system-info {
            text-align: right;
        }

        .system-title {
            font-size: 14pt;
            font-weight: bold;
            color: #071026;
            margin: 0;
        }

        .report-name {
            font-size: 11pt;
            font-weight: bold;
            color: #0062f5;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-info {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 4px;
        }

        /* Resumen / Estadísticas */
        .summary-box {
            background-color: #f8fafd;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 8pt;
            color: #334155;
        }

        /* Tablas de Datos */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            page-break-inside: auto;
        }

        table.data-table th {
            background-color: #071026;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #071026;
            text-align: left;
        }

        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
            vertical-align: middle;
        }

        table.data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.data-table tr:nth-child(even) {
            background-color: #f8fafd;
        }

        /* Badges de Estado */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-active {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-inactive {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .badge-primary {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }

        /* Pie de página institucional */
        .report-footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 7pt;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <!-- Encabezado del Documento -->
    <div class="report-header">
        <table class="report-header-table">
            <tr>
                <td class="logo-container">
                    @if(!empty($reportLogoBase64))
                        <img src="{{ $reportLogoBase64 }}" class="logo-img" alt="Logo">
                    @else
                        <h2 style="margin:0; color:#0062f5; font-size:16pt;">{{ $systemParameter->system_name ?? config('app.name', 'Dkript Core') }}</h2>
                    @endif
                </td>
                <td class="system-info">
                    <h1 class="system-title">{{ $systemParameter->system_name ?? config('app.name', 'Dkript Core') }}</h1>
                    <h2 class="report-name">{{ $reportTitle ?? 'Reporte Oficial' }}</h2>
                    <div class="meta-info">
                        Emisión: <strong>{{ $reportGeneratedAt ?? now()->format('d/m/Y H:i:s') }}</strong> | 
                        Generado por: <strong>{{ $reportEmittedBy ?? 'Administrador del Sistema' }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Contenido del Reporte -->
    @yield('content')

    <!-- Pie de Página Institucional -->
    <div class="report-footer">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left; border: none; font-size: 7pt; color: #94a3b8;">
                    {{ $systemParameter->system_name ?? config('app.name', 'Dkript Core') }} &bull; Documento Oficial de Uso Interno &bull; Excluye Marcas de Tiempo
                </td>
                <td style="text-align: right; border: none; font-size: 7pt; color: #94a3b8;">
                    {{ config('app.name', 'Dkript Core') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Script DomPDF para conteo de páginas -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.58, 0.64, 0.72));
        }
    </script>
</body>
</html>
