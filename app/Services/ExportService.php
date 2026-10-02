<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Exporta datos a una hoja de cálculo Excel (.xls) compatible con Microsoft Excel, Google Sheets y LibreOffice.
     * Incluye encabezado corporativo Dkript, estilos de celdas, bordes y formato limpio.
     *
     * @param string $title Título del reporte / documento
     * @param array $headers Nombres de las columnas visibles
     * @param array|\Traversable $rows Matriz de filas con los valores de cada columna
     * @param string $filename Nombre sugerido para el archivo (sin extensión o con .xls)
     * @return StreamedResponse
     */
    public function downloadExcel(string $title, array $headers, $rows, string $filename = 'reporte'): StreamedResponse
    {
        $cleanFilename = str_ends_with(strtolower($filename), '.xls') ? $filename : ($filename . '.xls');
        $parameter = \App\Models\Parameter::getSystemSettings();
        $systemName = \App\Services\BrandingService::name();
        $userName = auth()->user()?->name ?? 'Sistema';
        $date = now()->format('d/m/Y H:i:s');

        return response()->streamDownload(function () use ($title, $headers, $rows, $systemName, $userName, $date) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' . htmlspecialchars(substr($title, 0, 31)) . '</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'body { font-family: Arial, sans-serif; font-size: 11pt; }';
            echo '.report-title { font-size: 15pt; font-weight: bold; color: #071026; }';
            echo '.report-subtitle { font-size: 9pt; color: #64748b; }';
            echo 'th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #0f172a; padding: 8px 12px; text-align: left; }';
            echo 'td { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10pt; vertical-align: middle; }';
            echo '.zebra { background-color: #f8fafd; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<table>';
            echo '<tr><td colspan="' . count($headers) . '" class="report-title" style="border:none;">' . htmlspecialchars($systemName . ' — ' . $title) . '</td></tr>';
            echo '<tr><td colspan="' . count($headers) . '" class="report-subtitle" style="border:none;">Generado el: ' . htmlspecialchars($date) . ' | Emitido por: ' . htmlspecialchars($userName) . '</td></tr>';
            echo '<tr><td colspan="' . count($headers) . '" style="border:none; height: 10px;"></td></tr>';
            
            // Fila de encabezados
            echo '<tr>';
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';

            // Filas de datos
            $rowIndex = 0;
            foreach ($rows as $row) {
                $zebraClass = ($rowIndex++ % 2 === 1) ? ' class="zebra"' : '';
                echo '<tr' . $zebraClass . '>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
                }
                echo '</tr>';
            }

            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $cleanFilename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $cleanFilename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Genera un reporte en PDF mediante DomPDF con diseño corporativo optimizado para impresión y archivo.
     *
     * @param string $view Nombre de la vista Blade del reporte (ej. 'reports.users')
     * @param array $data Datos a enviar a la vista
     * @param string $filename Nombre sugerido para el archivo PDF
     * @param string $action 'download' para forzar descarga, 'print' o 'stream' para abrir en navegador
     * @param string $orientation 'portrait' o 'landscape'
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(string $view, array $data, string $filename = 'reporte', string $action = 'download', string $orientation = 'portrait')
    {
        $cleanFilename = str_ends_with(strtolower($filename), '.pdf') ? $filename : ($filename . '.pdf');

        // Obtener logo corporativo en base64 para evitar problemas de resolución en DomPDF
        $parameter = \App\Models\Parameter::getSystemSettings();
        $logo = \App\Services\BrandingService::logo();
        $logoBase64 = '';
        if (!empty($logo)) {
            $logoPath = public_path($logo);
            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($logoData);
            }
        }

        $data['reportLogoBase64'] = $logoBase64;
        $data['systemParameter'] = $parameter;
        $data['reportGeneratedAt'] = now()->format('d/m/Y H:i:s');
        $data['reportEmittedBy'] = auth()->user()?->name ?? 'Administrador del Sistema';

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        if ($action === 'print' || $action === 'stream') {
            return $pdf->stream($cleanFilename);
        }

        return $pdf->download($cleanFilename);
    }
}
