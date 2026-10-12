<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class KuisSoalImporter
{
    public const HEADERS = [
        'Pertanyaan',
        'Opsi A',
        'Opsi B',
        'Opsi C',
        'Opsi D',
        'Opsi E',
        'Opsi F',
        'Jawaban',
    ];

    public static function downloadTemplate(string $judulKuis): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            [
                'Ibu kota Indonesia adalah…',
                'Jakarta',
                'Bandung',
                'Surabaya',
                'Medan',
                '',
                '',
                'A',
            ],
            [
                '2 + 2 = ?',
                '3',
                '4',
                '5',
                '22',
                '',
                '',
                'B',
            ],
        ], null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $judulKuis) ?: 'kuis';
        $filename = 'template_soal_kuis_' . $safe . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * @return array{ok: bool, imported: int, skipped: int, errors: list<string>}
     */
    public static function import(int $kuisId, int $userId, string $tmpPath, string $originalName): array
    {
        if (!Kuis::findForUser($kuisId, $userId)) {
            return [
                'ok' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Kuis tidak ditemukan.'],
            ];
        }

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
                'errors' => ['Header wajib: Pertanyaan, Opsi A, Opsi B, Jawaban. Opsional: Opsi C–F.'],
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $line = $i + 1;
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $allEmpty = true;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $pertanyaan = trim((string) ($row[$map['pertanyaan']] ?? ''));
            $slots = ['', '', '', '', '', ''];
            $filled = 0;
            foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $i => $key) {
                if (!isset($map[$key])) {
                    continue;
                }
                $teks = trim((string) ($row[$map[$key]] ?? ''));
                $slots[$i] = $teks;
                if ($teks !== '') {
                    $filled++;
                }
            }

            $jawabanRaw = trim((string) ($row[$map['jawaban']] ?? ''));
            $jawabanIndex = self::parseJawabanSlot($jawabanRaw, $slots);

            if ($pertanyaan === '') {
                $skipped++;
                $errors[] = "Baris {$line}: pertanyaan kosong.";
                continue;
            }
            if ($filled < 2) {
                $skipped++;
                $errors[] = "Baris {$line}: minimal 2 opsi terisi.";
                continue;
            }
            if ($jawabanIndex < 0) {
                $skipped++;
                $errors[] = "Baris {$line}: jawaban tidak valid (A–F pada opsi yang terisi).";
                continue;
            }

            $payload = [
                'pertanyaan' => $pertanyaan,
                'opsi' => $slots,
                'jawaban_index' => $jawabanIndex,
            ];
            if (isset($map['poin'])) {
                $payload['poin'] = trim((string) ($row[$map['poin']] ?? ''));
            }
            $result = Kuis::addSoal($kuisId, $userId, $payload);

            if ($result['ok']) {
                $imported++;
            } else {
                $skipped++;
                $msg = $result['errors']['_form']
                    ?? $result['errors']['pertanyaan']
                    ?? $result['errors']['opsi']
                    ?? $result['errors']['jawaban_index']
                    ?? $result['errors']['poin']
                    ?? 'gagal disimpan';
                $errors[] = "Baris {$line}: {$msg}";
            }
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
            'pertanyaan' => ['pertanyaan', 'soal', 'question'],
            'a' => ['opsi a', 'a', 'pilihan a', 'option a'],
            'b' => ['opsi b', 'b', 'pilihan b', 'option b'],
            'c' => ['opsi c', 'c', 'pilihan c', 'option c'],
            'd' => ['opsi d', 'd', 'pilihan d', 'option d'],
            'e' => ['opsi e', 'e', 'pilihan e', 'option e'],
            'f' => ['opsi f', 'f', 'pilihan f', 'option f'],
            'jawaban' => ['jawaban', 'kunci', 'benar', 'answer', 'kunci jawaban'],
            'poin' => ['poin', 'skor', 'nilai', 'points'],
        ];

        $map = [];
        foreach ($aliases as $key => $names) {
            foreach ($header as $idx => $label) {
                if (in_array($label, $names, true)) {
                    $map[$key] = $idx;
                    break;
                }
            }
        }

        if (!isset($map['pertanyaan'], $map['a'], $map['b'], $map['jawaban'])) {
            return null;
        }

        return $map;
    }

    /** @param list<string> $slots A–F */
    private static function parseJawabanSlot(string $raw, array $slots): int
    {
        if ($raw === '') {
            return -1;
        }

        $raw = trim($raw);
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        if (ctype_digit($raw)) {
            $n = (int) $raw;
            // 1–6 mengacu huruf A–F
            if ($n >= 1 && $n <= 6) {
                $idx = $n - 1;
                return trim((string) ($slots[$idx] ?? '')) !== '' ? $idx : -1;
            }

            return -1;
        }

        $letter = mb_strtoupper(mb_substr($raw, 0, 1));
        $idx = array_search($letter, $letters, true);
        if ($idx === false) {
            return -1;
        }

        return trim((string) ($slots[(int) $idx] ?? '')) !== '' ? (int) $idx : -1;
    }
}
