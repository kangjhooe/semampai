<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class HafalanRekapExporter
{
    public static function download(array $kelas, array $matrix, string $view): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($view === 'surat' ? 'Per Surat' : 'Per Siswa');

        $sheet->fromArray([
            ['Kelas', $kelas['nama']],
            ['Tahun ajaran', $kelas['tahun_ajaran']],
            ['Target', $matrix['target']['judul']],
            ['Tampilan', $view === 'surat' ? 'Per surat' : 'Per siswa'],
            ['Diekspor', date('Y-m-d H:i')],
            [],
        ], null, 'A1');

        if ($view === 'surat') {
            self::writePivotSurat($sheet, $matrix);
        } else {
            self::writePivotSiswa($sheet, $matrix);
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $kelas['nama']) ?: 'kelas';
        $filename = 'rekap_target_hafalan_' . $safe . '_' . $view . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private static function writePivotSiswa($sheet, array $matrix): void
    {
        $header = ['Siswa', 'NISN', 'Progress'];
        foreach ($matrix['surat'] as $surat) {
            $header[] = $surat['surat_nama'];
        }
        $sheet->fromArray([$header], null, 'A7');

        $row = 8;
        foreach ($matrix['siswa'] as $siswa) {
            $sid = (int) $siswa['id'];
            $rs = $matrix['ringkas_siswa'][$sid];
            $line = [
                $siswa['nama'],
                $siswa['nisn'],
                $rs['tuntas'] . '/' . $rs['total_surat'],
            ];
            foreach ($matrix['surat'] as $surat) {
                $sn = (int) $surat['surat_nomor'];
                $cell = $matrix['cells'][$sid][$sn];
                $line[] = self::cellText($cell);
            }
            $sheet->fromArray([$line], null, 'A' . $row);
            $row++;
        }
        $sheet->getStyle('A7:' . $sheet->getHighestColumn() . '7')->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function writePivotSurat($sheet, array $matrix): void
    {
        $header = ['Surat', 'Ayat', 'Tuntas kelas'];
        foreach ($matrix['siswa'] as $siswa) {
            $header[] = $siswa['nama'];
        }
        $sheet->fromArray([$header], null, 'A7');

        $row = 8;
        foreach ($matrix['surat'] as $surat) {
            $sn = (int) $surat['surat_nomor'];
            $rs = $matrix['ringkas_surat'][$sn];
            $line = [
                $surat['surat_nama'],
                (int) $surat['jumlah_ayat'],
                $rs['tuntas'] . '/' . $rs['total_siswa'],
            ];
            foreach ($matrix['siswa'] as $siswa) {
                $sid = (int) $siswa['id'];
                $cell = $matrix['cells'][$sid][$sn];
                $line[] = self::cellText($cell);
            }
            $sheet->fromArray([$line], null, 'A' . $row);
            $row++;
        }
        $sheet->getStyle('A7:' . $sheet->getHighestColumn() . '7')->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function cellText(array $cell): string
    {
        if ($cell['status'] === 'tuntas') {
            return 'Tuntas';
        }
        if ($cell['status'] === 'belum') {
            return 'Belum mulai';
        }
        $missing = HafalanProgress::formatMissing($cell['missing'], 12);
        return 'Kurang ' . $cell['kurang'] . ' ayat' . ($missing !== '' ? ' (' . $missing . ')' : '');
    }
}
