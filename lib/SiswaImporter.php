<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class SiswaImporter
{
    public const HEADERS = ['Nama', 'NISN', 'Tempat Lahir', 'Tanggal Lahir'];

    public static function downloadTemplate(string $kelasNama): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Siswa');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            ['Ahmad Fauzi', '0012345678', 'Bandung', '2012-03-15'],
            ['Siti Aminah', '0012345679', 'Jakarta', '15/04/2012'],
        ], null, 'A2');

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $kelasNama) ?: 'kelas';
        $filename = 'template_siswa_' . $safeName . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * @return array{ok: bool, imported: int, skipped: int, errors: list<string>}
     */
    public static function import(int $kelasId, string $tmpPath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return [
                'ok' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File harus berformat .xlsx, .xls, atau .csv.'],
            ];
        }

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Gagal membaca file: ' . $e->getMessage()],
            ];
        }

        if ($rows === []) {
            return [
                'ok' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File kosong.'],
            ];
        }

        $header = array_map(static fn ($v) => mb_strtolower(trim((string) $v)), $rows[0]);
        $map = self::mapHeaders($header);
        if ($map === null) {
            return [
                'ok' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Header wajib: Nama, NISN, Tempat Lahir, Tanggal Lahir.'],
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $seenNisn = [];

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $line = $i + 1;

            $nama = trim((string) ($row[$map['nama']] ?? ''));
            $nisn = self::normalizeNisn($row[$map['nisn']] ?? '');
            $tempatLahir = trim((string) ($row[$map['tempat_lahir']] ?? ''));
            $tanggalRaw = $row[$map['tanggal_lahir']] ?? '';

            if ($nama === '' && $nisn === '' && $tempatLahir === '' && ($tanggalRaw === null || $tanggalRaw === '')) {
                continue;
            }

            $tanggalLahir = Siswa::normalizeDate($tanggalRaw);
            $data = [
                'nama' => $nama,
                'nisn' => $nisn,
                'tempat_lahir' => $tempatLahir,
                'tanggal_lahir' => $tanggalLahir ?? '',
            ];

            $validation = Siswa::validate($data);
            if ($validation !== []) {
                $skipped++;
                $errors[] = "Baris {$line}: " . implode(' ', array_values($validation));
                continue;
            }

            if (isset($seenNisn[$nisn])) {
                $skipped++;
                $errors[] = "Baris {$line}: NISN duplikat di file yang sama.";
                continue;
            }

            $result = Siswa::create($kelasId, $data);
            if (!$result['ok']) {
                $skipped++;
                $errors[] = "Baris {$line}: " . implode(' ', array_values($result['errors']));
                continue;
            }

            $seenNisn[$nisn] = true;
            $imported++;
        }

        return [
            'ok' => $imported > 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /** @param list<string> $header */
    private static function mapHeaders(array $header): ?array
    {
        $aliases = [
            'nama' => ['nama', 'nama siswa', 'name'],
            'nisn' => ['nisn'],
            'tempat_lahir' => ['tempat lahir', 'tempat_lahir', 'ttl tempat'],
            'tanggal_lahir' => ['tanggal lahir', 'tanggal_lahir', 'tgl lahir', 'lahir'],
        ];

        $map = [];
        foreach ($aliases as $key => $names) {
            $index = null;
            foreach ($header as $i => $label) {
                if (in_array($label, $names, true)) {
                    $index = $i;
                    break;
                }
            }
            if ($index === null) {
                return null;
            }
            $map[$key] = $index;
        }

        return $map;
    }

    private static function normalizeNisn(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            $digits = preg_replace('/\D+/', '', sprintf('%.0f', $value)) ?? '';
        } else {
            $digits = only_digits((string) $value);
        }

        if (strlen($digits) === 8 || strlen($digits) === 9) {
            $digits = str_pad($digits, 10, '0', STR_PAD_LEFT);
        }

        return $digits;
    }
}
