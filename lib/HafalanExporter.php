<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

final class HafalanExporter
{
    public static function downloadKelas(array $kelas, array $ringkas, array $histori): never
    {
        $spreadsheet = new Spreadsheet();

        $sheetRingkas = $spreadsheet->getActiveSheet();
        $sheetRingkas->setTitle('Ringkas');
        $sheetRingkas->fromArray([
            ['Kelas', $kelas['nama']],
            ['Tahun ajaran', $kelas['tahun_ajaran']],
            ['Diekspor', date('Y-m-d H:i')],
            [],
            ['Nama', 'NISN', 'Surat terakhir (lancar)', 'Ayat', 'Tanggal lancar terakhir', 'Total setor', 'Lancar', 'Ulang'],
        ], null, 'A1');

        $row = 6;
        foreach ($ringkas as $item) {
            $ayat = $item['surat_nama']
                ? Hafalan::formatAyat((int) $item['ayat_awal'], (int) $item['ayat_akhir'])
                : '';
            $sheetRingkas->fromArray([[
                $item['nama'],
                $item['nisn'],
                $item['surat_nama'] ?? '',
                $ayat,
                $item['terakhir_lancar'] ? date('Y-m-d H:i', strtotime($item['terakhir_lancar'])) : '',
                (int) $item['total_setor'],
                (int) $item['total_lancar'],
                (int) $item['total_ulang'],
            ]], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheetRingkas->getColumnDimension($col)->setAutoSize(true);
        }
        $sheetRingkas->getStyle('A5:H5')->getFont()->setBold(true);

        $sheetHistori = $spreadsheet->createSheet();
        $sheetHistori->setTitle('Histori');
        $sheetHistori->fromArray([
            ['Tanggal', 'Nama', 'NISN', 'Surat', 'Ayat', 'Status', 'Catatan'],
        ], null, 'A1');

        $r = 2;
        foreach ($histori as $item) {
            $sheetHistori->fromArray([[
                date('Y-m-d H:i', strtotime($item['created_at'])),
                $item['siswa_nama'],
                $item['nisn'],
                $item['surat_nama'],
                Hafalan::formatAyat((int) $item['ayat_awal'], (int) $item['ayat_akhir']),
                $item['status'] === 'lancar' ? 'Lancar' : 'Ulang',
                $item['catatan'] ?? '',
            ]], null, 'A' . $r);
            $r++;
        }

        foreach (range('A', 'G') as $col) {
            $sheetHistori->getColumnDimension($col)->setAutoSize(true);
        }
        $sheetHistori->getStyle('A1:G1')->getFont()->setBold(true);
        $sheetHistori->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $kelas['nama']) ?: 'kelas';
        $filename = 'rekap_hafalan_' . $safe . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
