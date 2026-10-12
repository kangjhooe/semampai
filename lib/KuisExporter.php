<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

final class KuisExporter
{
    public static function downloadHasil(array $kuis, array $hasil): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil');

        $sheet->fromArray([
            ['Judul', $kuis['judul'] ?? ''],
            ['ID kuis', $kuis['kode'] ?? ''],
            ['Diekspor', date('Y-m-d H:i')],
            ['Jumlah pengerjaan', count($hasil)],
            [],
            ['Peringkat', 'Nama', 'Skor', 'Total', 'Persen', 'Waktu'],
        ], null, 'A1');

        $row = 7;
        $rank = 1;
        foreach ($hasil as $item) {
            $total = max(1, (int) $item['total']);
            $skor = (int) $item['skor'];
            $persen = (int) round(($skor / $total) * 100);
            $sheet->fromArray([[
                (int) ($item['peringkat'] ?? $rank),
                $item['nama_peserta'],
                $skor,
                (int) $item['total'],
                $persen,
                date('Y-m-d H:i', strtotime((string) $item['created_at'])),
            ]], null, 'A' . $row);
            $row++;
            $rank++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A6:F6')->getFont()->setBold(true);
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($kuis['kode'] ?? 'kuis')) ?: 'kuis';
        $filename = 'hasil_kuis_' . $safe . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
