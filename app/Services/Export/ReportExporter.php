<?php

namespace App\Services\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class ReportExporter
{
    public function toCsv(array $headers, array $rows, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Response::streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, is_array($row) ? array_values($row) : [$row]);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function toPdf(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @param  array<string, mixed>  $dashboardData
     */
    public function dashboardTableRows(array $dashboardData, string $tableKey): array
    {
        $table = $dashboardData['tables'][$tableKey] ?? [];

        return collect($table)->map(function ($row) {
            return is_array($row) ? array_values($row) : [$row];
        })->all();
    }
}
